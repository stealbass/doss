# 🔐 Installation OCR sans SUDO - Guide complet

## 📋 Contexte

Installation sur serveur partagé **sans accès root/sudo**. Tout est installé dans les répertoires utilisateur.

---

## 🚀 Installation rapide (30 min)

### Étape 1: SSH sur le serveur

```bash
ssh user@votre-serveur.com
cd ~
```

### Étape 2: Télécharger et exécuter le script

```bash
# Télécharger le script
wget https://votre-repo/install_ocr_no_sudo.sh

# Ou copier-coller le contenu depuis install_ocr_no_sudo.sh

# Rendre exécutable
chmod +x install_ocr_no_sudo.sh

# Exécuter (prend 5-15 min)
./install_ocr_no_sudo.sh
```

### Étape 3: Appliquer configuration

```bash
# Recharger le shell pour les variables d'environnement
source ~/.bashrc

# Ou se déconnecter/reconnecter
exit
# Puis: ssh user@votre-serveur.com
```

### Étape 4: Vérifier

```bash
# Tesseract
tesseract --version

# Python packages
python3 -c "import pytesseract, PIL; print('✅ OK')"
```

---

## 🔧 Installation manuelle (détaillée)

### 1️⃣ Créer les répertoires

```bash
# Base d'installation
mkdir -p ~/.local/opt/tesseract
mkdir -p ~/.local/opt/python-packages

# Espace de travail
mkdir -p /tmp/ocr-build
cd /tmp/ocr-build
```

### 2️⃣ Installer Tesseract (2 options)

#### Option A: Tesseract système (si disponible)

```bash
# Vérifier
which tesseract
tesseract --version

# Si OK, passer à l'étape 3
```

#### Option B: Compiler depuis source

```bash
cd /tmp/ocr-build

# Télécharger (dernière version)
curl -L https://github.com/UB-Mannheim/tesseract/archive/refs/tags/5.3.3.tar.gz \
     -o tesseract-5.3.3.tar.gz

# Décompresser
tar -xzf tesseract-5.3.3.tar.gz
cd tesseract-5.3.3

# Configuration
./configure --prefix=$HOME/.local/opt/tesseract \
            --disable-shared \
            --enable-static \
            --disable-graphics

# Compilation (peut prendre 5-10 min)
make -j$(nproc)

# Installation
make install

# Vérifier
$HOME/.local/opt/tesseract/bin/tesseract --version
```

### 3️⃣ Installer données de langue

```bash
# Créer répertoire
mkdir -p ~/.local/opt/tesseract/tessdata

# Français
wget -P ~/.local/opt/tesseract/tessdata \
  https://github.com/tesseract-ocr/tessdata/raw/main/fra.traineddata

# Anglais
wget -P ~/.local/opt/tesseract/tessdata \
  https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata

# Vérifier
ls ~/.local/opt/tesseract/tessdata/
```

### 4️⃣ Installer packages Python

```bash
# Sans sudo: utiliser --user
pip3 install --user pytesseract>=0.3.10
pip3 install --user Pillow>=9.0.0
pip3 install --user pdfplumber>=0.9.0
pip3 install --user PyPDF2>=3.0.0
pip3 install --user python-docx>=0.8.11
pip3 install --user openpyxl>=3.10.0
pip3 install --user python-pptx>=0.6.21
pip3 install --user boto3>=1.26.0
pip3 install --user mysql-connector-python>=8.0.33

# Ou tout d'un coup:
pip3 install --user pytesseract Pillow pdfplumber PyPDF2 python-docx \
    openpyxl python-pptx boto3 mysql-connector-python
```

**Note**: Les packages vont dans `~/.local/lib/python3.x/site-packages/`

### 5️⃣ Configurer variables d'environnement

Créer `~/.dossy_ocr_env.sh`:

```bash
cat > ~/.dossy_ocr_env.sh << 'EOF'
#!/bin/bash
# Dossy OCR - Sans sudo

# Paths
export PATH="$HOME/.local/opt/tesseract/bin:$HOME/.local/bin:$PATH"
export TESSDATA_PREFIX="$HOME/.local/opt/tesseract/tessdata"

# PYTHONPATH - TRÈS IMPORTANT pour ProcessDocumentForRAG!
export PYTHONPATH="$HOME/.local/lib/python3.9/site-packages:$HOME/.local/lib/python3.8/site-packages:$HOME/.local/lib/python3.7/site-packages:$PYTHONPATH"

# Vérification rapide
echo "✅ Dossy OCR configured"
EOF

chmod +x ~/.dossy_ocr_env.sh
```

Ajouter à `~/.bashrc`:

```bash
# À la fin du fichier
echo "" >> ~/.bashrc
echo "# Dossy OCR (no sudo)" >> ~/.bashrc
echo "source ~/.dossy_ocr_env.sh" >> ~/.bashrc
```

Appliquer:

```bash
source ~/.bashrc
```

---

## 🔌 Configuration pour ProcessDocumentForRAG

### Important: PYTHONPATH dans ProcessDocumentForRAG.php

Le fichier **doit** être configuré pour trouver les packages:

```php
// File: app/Jobs/ProcessDocumentForRAG.php

private function extractTextFromDocument(SubmittedDocument $document): string
{
    try {
        Log::info("Extracting text via Python script for document {$document->id}");
        
        // 🔧 SANS SUDO: Utiliser le répertoire utilisateur
        $pythonPath = getenv('HOME') . '/.local/lib/python3.9/site-packages:' .
                      getenv('HOME') . '/.local/lib/python3.8/site-packages:' .
                      getenv('HOME') . '/.local/lib/python3.7/site-packages';
        
        $scriptPath = base_path('scripts/extract_documents.py');
        
        // Essayer plusieurs versions Python
        $commands = [
            ['bash', '-c', "PYTHONPATH={$pythonPath}:\$PYTHONPATH python3 {$scriptPath} --document-id {$document->id}"],
            ['bash', '-c', "PYTHONPATH={$pythonPath}:\$PYTHONPATH python {$scriptPath} --document-id {$document->id}"],
        ];
        
        $success = false;
        $lastError = '';
        
        foreach ($commands as $cmd) {
            $process = new Process($cmd);
            $process->setTimeout(900); // 15 minutes
            $process->run();
            
            if ($process->isSuccessful()) {
                $success = true;
                break;
            }
            
            $lastError = $process->getErrorOutput();
            Log::warning('Python extraction attempt failed', [
                'command' => implode(' ', $cmd),
                'error' => $lastError,
            ]);
        }
        
        if (!$success) {
            Log::error('Python extraction failed', ['error' => $lastError]);
            return '';
        }
        
        $document->refresh();
        return $document->extracted_text ?? '';
        
    } catch (\Exception $e) {
        Log::error('Text extraction error: ' . $e->getMessage());
        return '';
    }
}
```

### OU: Configuration via Variable d'environnement

Ajouter au `.env` du projet Laravel:

```bash
# .env
PYTHON_PATHS="/home/user/.local/lib/python3.9/site-packages:/home/user/.local/lib/python3.8/site-packages:/home/user/.local/lib/python3.7/site-packages"
```

Puis dans ProcessDocumentForRAG:

```php
$pythonPath = config('app.python_paths', 
    getenv('HOME') . '/.local/lib/python3.9/site-packages'
);
```

---

## ✅ Vérifications après installation

### 1. Tesseract

```bash
# Version
tesseract --version

# Langues disponibles
tesseract --list-langs
# Doit afficher: fra, eng, osd

# Test rapide
echo "Test texte" > test.txt
tesseract test.txt output
cat output.txt
```

### 2. Python + pytesseract

```bash
python3 << 'EOF'
import sys
print(f"Python: {sys.version}")

import pytesseract
from PIL import Image
print("✅ pytesseract OK")

# Vérifier PYTHONPATH
print(f"PYTHONPATH: {sys.path}")
EOF
```

### 3. OCR complet

```bash
# Créer une image de test
python3 << 'EOF'
from PIL import Image, ImageDraw, ImageFont
import pytesseract

# Créer image avec texte
img = Image.new('RGB', (200, 100), color='white')
draw = ImageDraw.Draw(img)
draw.text((10, 40), "Test OCR", fill='black')
img.save('test_ocr.png')

# Extraire texte
text = pytesseract.image_to_string(img, lang='fra')
print(f"Texte extrait: {text}")
EOF

# Vérifier le fichier
ls -la test_ocr.png
```

### 4. Test avec ProcessDocumentForRAG

```bash
# Depuis le dossier Laravel
cd /path/to/doss-genspark_ai_developer

# Tester directement le script
source ~/.dossy_ocr_env.sh

python3 scripts/extract_documents.py --document-id 1
```

---

## 🚨 Dépannage

### ❌ "tesseract: command not found"

**Solution 1**: Vérifier PATH

```bash
# Afficher PATH
echo $PATH

# Doit contenir: /home/user/.local/opt/tesseract/bin

# Réappliquer la config
source ~/.dossy_ocr_env.sh
```

**Solution 2**: Compiler n'a pas marché

```bash
# Vérifier le binaire
ls ~/.local/opt/tesseract/bin/tesseract

# Si absent, récompiler:
cd /tmp/ocr-build/tesseract-5.3.3
make clean
./configure --prefix=$HOME/.local/opt/tesseract --disable-graphics
make -j$(nproc) 2>&1 | tee make.log
make install
```

### ❌ "pytesseract module not found"

**Solution**: PYTHONPATH incorrect

```bash
# Vérifier
echo $PYTHONPATH

# Doit contenir: /home/user/.local/lib/python3.x/site-packages

# Recharger
source ~/.dossy_ocr_env.sh

# Tester
python3 -c "import pytesseract; print('OK')"
```

### ❌ "fra.traineddata not found"

**Solution**: Télécharger manuellement

```bash
mkdir -p ~/.local/opt/tesseract/tessdata

cd ~/.local/opt/tesseract/tessdata

wget https://github.com/tesseract-ocr/tessdata/raw/main/fra.traineddata
wget https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata

# Vérifier
ls -lh fra.traineddata eng.traineddata
```

### ❌ Job ProcessDocumentForRAG échoue

**Vérifier les logs**:

```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep -i "extraction\|python\|pytesseract"

# Ou regarder directement
grep "extraction error" storage/logs/laravel.log
```

**Tester manellement**:

```bash
# Charger l'env
source ~/.dossy_ocr_env.sh

# Lancer le script Python directement
cd /path/to/doss-genspark_ai_developer
python3 scripts/extract_documents.py --document-id 1

# Vérifier la base de données
mysql -e "SELECT id, extracted_text_length FROM submitted_documents WHERE id=1;"
```

---

## 📊 Espace disque utilisé

```bash
# Taille totale installation
du -sh ~/.local/opt/tesseract ~/.local/lib/python*/site-packages

# Estimé:
# - Tesseract: 150-300 MB (compilé)
# - Python packages: 200-400 MB
# Total: 350-700 MB
```

Libérer l'espace:

```bash
# Supprimer les données de langue inutilisées
rm ~/.local/opt/tesseract/tessdata/chi_* ~/.local/opt/tesseract/tessdata/jpn_*

# Nettoyer cache pip
pip3 cache purge
```

---

## 🎯 Résumé - Path pour ProcessDocumentForRAG

À utiliser dans **ProcessDocumentForRAG.php**:

```php
// Pour serveur sans sudo (installation user-space):
$pythonPath = getenv('HOME') . '/.local/lib/python3.9/site-packages:' .
              getenv('HOME') . '/.local/lib/python3.8/site-packages:' .
              getenv('HOME') . '/.local/lib/python3.7/site-packages';

// Ou depuis variable d'environnement (~/.dossy_ocr_env.sh):
$pythonPath = getenv('PYTHONPATH');
```

**Tesseract binary**:
```bash
/home/user/.local/opt/tesseract/bin/tesseract
# Ou si système:
/usr/bin/tesseract
```

---

## 📝 Checklist

- [ ] Script `install_ocr_no_sudo.sh` exécuté OU installation manuelle complétée
- [ ] `tesseract --version` OK
- [ ] `tesseract --list-langs` affiche `fra` et `eng`
- [ ] `python3 -c "import pytesseract"` OK
- [ ] Fichier `~/.dossy_ocr_env.sh` créé et source dans `~/.bashrc`
- [ ] Vérifications réussies
- [ ] **ProcessDocumentForRAG.php configuré avec le bon PYTHONPATH**
- [ ] Test upload document/image réussi

---

**Créé**: 19 janvier 2026  
**Support**: Linux, macOS, serveurs partagés  
**Sans sudo**: ✅ Oui
