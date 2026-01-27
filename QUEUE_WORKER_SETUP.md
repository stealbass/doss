# Configuration Queue Worker - Sans PHP Artisan Direct

## 🎯 Options d'Exécution

### Option 1: Script Batch avec Boucle (Simple) ✅

**Fichier**: `start-queue-worker.bat`

#### Utilisation
```bash
# Double-clic sur le fichier ou depuis cmd:
start-queue-worker.bat
```

#### Avantages
- ✅ Aucune installation requise
- ✅ Redémarrage automatique en cas d'erreur
- ✅ Timeout intégré (1h max, puis redémarre)
- ✅ Logs visibles dans la console

#### Inconvénients
- ⚠️ Console doit rester ouverte
- ⚠️ Arrêt si fermeture de la fenêtre
- ⚠️ Pas de démarrage automatique au boot

---

### Option 2: Service Windows avec NSSM (Production) ✅✅✅

**Fichier**: `install-queue-service.bat`

#### Installation

1. **Télécharger NSSM**
   - Site: https://nssm.cc/download
   - Télécharger `nssm-2.24.zip`
   - Extraire `win64/nssm.exe` vers `C:\Windows\System32\`

2. **Configurer le chemin PHP**
   Éditer `install-queue-service.bat` ligne 15:
   ```batch
   set PHP_PATH=C:\php\php.exe
   ```
   Remplacer par votre chemin PHP (utilisez `where php` pour le trouver)

3. **Exécuter en tant qu'Administrateur**
   - Clic droit sur `install-queue-service.bat`
   - "Exécuter en tant qu'administrateur"

#### Commandes de Gestion
```bash
# Vérifier le statut
nssm status DossyQueueWorker

# Démarrer le service
nssm start DossyQueueWorker

# Arrêter le service
nssm stop DossyQueueWorker

# Redémarrer le service
nssm restart DossyQueueWorker

# Désinstaller le service
nssm remove DossyQueueWorker confirm
```

#### Configuration du Service
- **Nom**: DossyQueueWorker
- **Démarrage**: Automatique au boot Windows
- **Redémarrage**: Automatique en cas d'erreur (5s délai)
- **Timeout**: 300s par job
- **Retries**: 3 tentatives
- **Logs**: 
  - `storage/logs/queue-worker-stdout.log`
  - `storage/logs/queue-worker-stderr.log`

#### Avantages
- ✅ Service Windows natif
- ✅ Démarrage automatique au boot
- ✅ Redémarrage automatique en cas d'erreur
- ✅ Logs persistants
- ✅ Gestion via services.msc
- ✅ Pas de console visible

---

### Option 3: Tâche Planifiée Windows (Alternative)

#### Création Manuelle

1. **Ouvrir le Planificateur de Tâches**
   - Win+R → `taskschd.msc`

2. **Créer une Tâche de Base**
   - Action → Créer une tâche...
   - Nom: `Dossy Queue Worker`
   - Exécuter même si l'utilisateur n'est pas connecté
   - Démarrer avec les privilèges les plus élevés

3. **Déclencheur**
   - Au démarrage du système
   - Répéter toutes les: 5 minutes
   - Pendant: Indéfiniment

4. **Action**
   - Programme: `C:\php\php.exe`
   - Arguments: `artisan queue:work database --tries=3 --timeout=300 --max-time=3600 --stop-when-empty`
   - Dossier: `C:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer`

5. **Conditions**
   - ☑ Démarrer uniquement si l'ordinateur est branché: NON
   - ☑ Arrêter si l'ordinateur bascule sur batterie: NON

#### Avantages
- ✅ Intégré à Windows
- ✅ Aucun outil externe
- ✅ Démarrage automatique

#### Inconvénients
- ⚠️ Redémarre toutes les 5 min (batch processing)
- ⚠️ Moins réactif que le service continu

---

## 🗄️ Migration SQL

### Exécution Directe (Sans Artisan)

**Fichier**: `migrations_anonymization.sql`

#### Option 1: phpMyAdmin
1. Connexion à phpMyAdmin
2. Sélectionner la base de données
3. Onglet "SQL"
4. Copier-coller le contenu de `migrations_anonymization.sql`
5. Cliquer "Exécuter"

#### Option 2: Ligne de commande MySQL
```bash
mysql -u root -p dossy_database < migrations_anonymization.sql
```

#### Option 3: HeidiSQL / MySQL Workbench
1. Ouvrir l'outil
2. Connexion à la base
3. Menu "Fichier" → "Ouvrir SQL..."
4. Sélectionner `migrations_anonymization.sql`
5. Exécuter (F9)

### Vérification

```sql
-- Voir la structure de la table
DESCRIBE submitted_documents;

-- Vérifier les nouvelles colonnes
SHOW COLUMNS FROM submitted_documents 
WHERE Field IN ('anonymization_detections', 'has_sensitive_data', 'detections_count');

-- Tester avec des données
SELECT id, original_filename, has_sensitive_data, detections_count 
FROM submitted_documents 
WHERE has_sensitive_data = 1
LIMIT 10;
```

---

## 🔍 Monitoring des Jobs

### Via Base de Données

```sql
-- Jobs en attente
SELECT * FROM jobs ORDER BY created_at DESC;

-- Jobs échoués
SELECT * FROM failed_jobs ORDER BY failed_at DESC;

-- Compteur de jobs par statut
SELECT 
  'pending' AS status, 
  COUNT(*) AS count 
FROM jobs
UNION ALL
SELECT 
  'failed' AS status, 
  COUNT(*) AS count 
FROM failed_jobs;
```

### Via Logs

```bash
# Logs du queue worker
type storage\logs\queue-worker-stdout.log

# Logs Laravel
type storage\logs\laravel.log | findstr "ProcessDocumentForRAG"
```

---

## 🚀 Recommandation Production

**Utilisez Option 2 (Service Windows avec NSSM)** car:
- ✅ Service professionnel et stable
- ✅ Redémarrage automatique
- ✅ Logs persistants
- ✅ Gestion simplifiée
- ✅ Démarrage au boot

**Configuration optimale**:
```batch
php artisan queue:work database --tries=3 --timeout=300 --sleep=3
```

**Monitoring**:
```batch
# Statut en temps réel
nssm status DossyQueueWorker

# Logs
type storage\logs\queue-worker-stdout.log
```

---

## 📊 Performance

### Paramètres Ajustables

- `--tries=3`: Nombre de tentatives avant échec définitif
- `--timeout=300`: Timeout par job (5 min = temps max pour traiter un PDF)
- `--sleep=3`: Pause entre checks de la queue (3s)
- `--max-time=3600`: Durée max du worker avant redémarrage (1h)

### Pour Documents Volumineux

Si vous traitez des PDFs de 50+ pages:
```batch
--timeout=600 --max-time=7200
```

### Pour Haute Fréquence

Si vous recevez beaucoup d'uploads:
```batch
--sleep=1 --processes=3
```
(Lance 3 workers en parallèle)

---

## 🔧 Dépannage

### Le Service Ne Démarre Pas

```bash
# Vérifier les logs
type storage\logs\queue-worker-stderr.log

# Vérifier la config PHP
nssm get DossyQueueWorker AppDirectory
nssm get DossyQueueWorker Application

# Redémarrer manuellement
nssm stop DossyQueueWorker
nssm start DossyQueueWorker
```

### Jobs Restent Bloqués

```sql
-- Réinitialiser les jobs bloqués
DELETE FROM jobs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Relancer les failed jobs
php artisan queue:retry all
```

### Problème de Permissions

Le service s'exécute sous `SYSTEM` par défaut. Pour changer:
```bash
nssm set DossyQueueWorker ObjectName ".\VotreUtilisateur" "MotDePasse"
nssm restart DossyQueueWorker
```
