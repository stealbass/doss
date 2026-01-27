# 🖼️ Configuration OCR - Support Images dans ProcessDocumentForRAG

## 📋 Vue d'ensemble

ProcessDocumentForRAG a été configuré pour supporter l'extraction de texte depuis les **images** en utilisant **Tesseract-OCR**.

### ✅ Formats maintenant supportés:

| Type | Formats | Methode |
|------|---------|--------|
| **Documents** | PDF, Word, Excel, PowerPoint, Texte | Extraction native |
| **Images** | JPG, PNG, GIF, BMP, WEBP, TIFF | Tesseract OCR |
| **Taille max** | 30 MB | ~15 minutes d'extraction |

---

## 🚀 Installation des Packages

### Option 1️⃣: Linux / macOS / WSL

```bash
# Naviguer au répertoire du projet
cd /chemin/vers/doss-genspark_ai_developer

# Rendre le script exécutable
chmod +x install_ocr_packages.sh

# Exécuter l'installation
./install_ocr_packages.sh
```

**Durée estimée**: 3-5 minutes

### Option 2️⃣: Windows (CMD ou PowerShell)

```cmd
# Lancer le script en tant qu'administrateur
install_ocr_packages.bat
```

**Durée estimée**: 5-10 minutes (installation Tesseract)

### Option 3️⃣: Installation manuelle (si scripts échouent)

#### A. Installer Tesseract-OCR système

**Linux (Ubuntu/Debian)**:
```bash
sudo apt update
sudo apt install -y tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng imagemagick
```

**macOS**:
```bash
brew install tesseract imagemagick
```

**Windows**:
1. Téléchargez: https://github.com/UB-Mannheim/tesseract/wiki
2. Installez le programme `.exe`
3. ✅ Sélectionnez "Additional language data" → **French**

#### B. Installer Packages Python

```bash
# Créer répertoire packages partagés
mkdir -p /home/threesixty/yyy/Dossy/python-packages

# Installer les packages
pip3 install --target=/home/threesixty/yyy/Dossy/python-packages \
  pytesseract>=0.3.10 \
  Pillow>=9.0.0 \
  pdfplumber>=0.9.0 \
  PyPDF2>=3.0.0 \
  python-docx>=0.8.11 \
  openpyxl>=3.10.0 \
  python-pptx>=0.6.21 \
  boto3>=1.26.0 \
  mysql-connector-python>=8.0.33
```

---

## 🔧 Changements apportés

### 1. **DocumentController.php** - Limite de taille

```php
// AVANT: 50 MB
'file' => 'required|file|mimes:pdf,doc,docx,...|max:51200'

// APRÈS: 30 MB + images supportées
'file' => 'required|file|mimes:pdf,...,jpg,jpeg,png,gif,bmp,webp|max:30720'
```

✅ Messages d'erreur améliorés pour les fichiers trop volumineux.

### 2. **ProcessDocumentForRAG.php** - Timeout augmenté

```php
// AVANT: 5 minutes
public $timeout = 300;

// APRÈS: 15 minutes (pour 30MB + OCR)
public $timeout = 900;
```

### 3. **extract_documents.py** - Support OCR ajouté

```python
def _extract_image(self):
    """Extrait le texte des images via Tesseract OCR"""
    # Utilise pytesseract pour OCR
    # Supporte: JPG, PNG, GIF, BMP, WEBP, TIFF
    # Langues: Français + Anglais
```

---

## 🧪 Test de l'installation

### Vérifier Tesseract

```bash
# Affiche la version
tesseract --version

# Affiche les langues disponibles
tesseract --list-langs
```

**Résultat attendu**:
```
tesseract 5.x.x
...
fra
eng
```

### Vérifier les Packages Python

```bash
# Définir PYTHONPATH
export PYTHONPATH="/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/yyy/Dossy/.local/lib/python3.7/site-packages"

# Tester l'import
python3 << 'EOF'
import pytesseract
from PIL import Image
print("✅ OCR packages OK")
EOF
```

### Tester avec une image réelle

```python
import pytesseract
from PIL import Image

# Ouvrir une image
img = Image.open('test.png')

# Extraire le texte
text = pytesseract.image_to_string(img, lang='fra')
print(text)
```

---

## ⚙️ Configuration dans ProcessDocumentForRAG

La configuration PYTHONPATH est **automatiquement définie** dans `ProcessDocumentForRAG.php`:

```php
$pythonPath = '/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/yyy/Dossy/.local/lib/python3.7/site-packages';

// Exécution du script avec PYTHONPATH
$process = new Process([
    'bash', '-c', "PYTHONPATH={$pythonPath}:\$PYTHONPATH python3 {$scriptPath} --document-id {$document->id}"
]);
```

Aucune configuration supplémentaire nécessaire. ✅

---

## 📊 Performances attendues

| Taille fichier | Temps d'extraction | Notes |
|----------------|-------------------|-------|
| < 5 MB | 30-60 secondes | Documents texte ou images simples |
| 5-15 MB | 1-3 minutes | Gros PDFs ou images complexes |
| 15-30 MB | 5-15 minutes | Fichiers limites (timeout: 15 min) |
| > 30 MB | ❌ Rejeté | Validation DocumentController |

---

## 🚨 Dépannage

### ❌ "pytesseract module not found"

**Cause**: Packages Python non installés dans le bon répertoire

**Solution**:
```bash
# Installer à nouveau
pip3 install --target=/home/threesixty/yyy/Dossy/python-packages pytesseract
```

### ❌ "tesseract is not installed"

**Cause**: Tesseract-OCR système n'est pas dans PATH

**Cause**: 
```bash
# Vérifier l'installation
which tesseract
# ou
tesseract --version
```

**Solution Linux**:
```bash
sudo apt install tesseract-ocr
```

**Solution macOS**:
```bash
brew install tesseract
```

**Solution Windows**:
- Réinstaller Tesseract depuis https://github.com/UB-Mannheim/tesseract/wiki
- Vérifier que le PATH est défini (ajouter `C:\Program Files\Tesseract-OCR`)

### ❌ "Language data not found (fra)"

**Cause**: Les données françaises ne sont pas installées

**Solution**:
```bash
# Linux
sudo apt install tesseract-ocr-fra

# macOS
brew install tesseract-lang  # inclut toutes les langues
```

**Windows**:
- Réinstaller Tesseract
- Cocher "Additional language data" et sélectionner "French"

### ❌ "File too large" (30 MB exceeded)

**Cause**: Le fichier dépasse 30 MB

**Message attendu**:
```json
{
  "success": false,
  "errors": {
    "file": ["Fichier trop volumineux. Maximum : 30 MB. Fichier fourni : 35.5 MB"]
  }
}
```

**Solution**: Réduire la taille du fichier ou fractionner le document.

### ⚠️ "Job timeout (15 minutes exceeded)"

**Cause**: L'extraction a pris plus de 15 minutes

**Risque**: Fichier > 30 MB ou serveur surchargé

**Solution**:
- Augmenter `$timeout = 1800` (30 min) dans `ProcessDocumentForRAG.php`
- Compresser/réduire la taille du fichier
- Vérifier les ressources serveur

---

## 📝 Utilisation dans l'API

### Upload avec image

```bash
curl -X POST http://api/documents/upload \
  -H "Authorization: Bearer {token}" \
  -F "file=@mon_image.png" \
  -F "title=Ma facture scannée"
```

### Réponse

```json
{
  "success": true,
  "message": "Document uploaded successfully. Processing in background.",
  "data": {
    "document": {
      "id": 42,
      "title": "mon_image.png",
      "file_type": "image",
      "mime_type": "image/png",
      "file_size": 2048576,
      "processing_status": "pending"
    }
  }
}
```

L'extraction OCR se fera automatiquement en arrière-plan. ✅

---

## 🎯 Cas d'usage

### ✅ Images supportées

- 📸 Photos de factures/reçus
- 📄 Documents scannés (JPG)
- 🖼️ Captures d'écran (PNG)
- 📊 Tableaux photographiés
- 🎫 Billets/tickets scannés
- 📋 Formulaires manuscrits (si écriture claire)

### ❌ Non supporté

- ✋ Texte manuscrit illisible
- 🌫️ Images très floues ou dégradées
- 🔐 Documents cryptés (PDF/Office)
- 📱 Vidéos ou animations

---

## 📚 Ressources

- **Tesseract OCR**: https://github.com/tesseract-ocr/tesseract
- **pytesseract**: https://github.com/madmaze/pytesseract
- **Pillow**: https://python-pillow.org/
- **Données de langue**: https://github.com/tesseract-ocr/tessdata

---

## ✅ Checklist d'installation

- [ ] Script d'installation exécuté (`.sh` ou `.bat`)
- [ ] Tesseract-OCR installé (`tesseract --version` OK)
- [ ] Langues disponibles (`tesseract --list-langs` montre `fra` et `eng`)
- [ ] Packages Python installés dans `/home/threesixty/yyy/Dossy/python-packages`
- [ ] Fichier `extract_documents.py` contient `_extract_image()`
- [ ] `ProcessDocumentForRAG.php` a `timeout = 900`
- [ ] `DocumentController.php` accepte images et limite à 30MB
- [ ] Test d'upload réussi avec une image

---

**Date d'installation**: 19 janvier 2026  
**Version**: 1.0  
**Support**: Linux, macOS, Windows (WSL)
