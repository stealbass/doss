# Configuration du Queue Worker pour AlwaysData

## ✅ Configuration Actuelle
- **QUEUE_CONNECTION=database** (correct dans .env)
- **Table jobs:** À vérifier avec check_queue_status.php
- **Queue worker:** Non actif (problème principal)

## 🎯 Problème Identifié
Les documents uploadés ne sont pas traités automatiquement car le **worker de queue Laravel n'est pas en cours d'exécution** sur le serveur AlwaysData.

---

## 📋 Solution: Configurer un Cron Job

Puisque AlwaysData ne supporte pas Supervisor, utilisez un cron job pour maintenir le worker actif.

### Étape 1: Vérifier la Table Jobs

Sur le serveur, exécutez:
```bash
cd /home/threesixty/yyy/Dossy
php check_queue_status.php
```

Si la table `jobs` n'existe pas:
```bash
php artisan queue:table
php artisan migrate
```

### Étape 2: Créer le Script de Worker

Créez `/home/threesixty/yyy/Dossy/queue-worker.sh`:

```bash
#!/bin/bash

# Chemin vers le projet Laravel
cd /home/threesixty/yyy/Dossy

# Vérifier si le worker est déjà en cours d'exécution
WORKER_PID=$(ps aux | grep "queue:work" | grep -v grep | awk '{print $2}')

if [ -z "$WORKER_PID" ]; then
    echo "[$(date)] Démarrage du queue worker..."
    nohup php artisan queue:work --tries=3 --timeout=300 >> storage/logs/queue-worker.log 2>&1 &
    echo "[$(date)] Queue worker démarré avec PID: $!"
else
    echo "[$(date)] Queue worker déjà actif avec PID: $WORKER_PID"
fi
```

Rendez-le exécutable:
```bash
chmod +x /home/threesixty/yyy/Dossy/queue-worker.sh
```

### Étape 3: Configurer le Cron Job dans AlwaysData

1. Connectez-vous à votre **panel AlwaysData**
2. Allez dans **Tâches programmées** (Scheduled tasks / Cron)
3. Créez une nouvelle tâche:

**Commande:**
```bash
/home/threesixty/yyy/Dossy/queue-worker.sh
```

**Fréquence:** Toutes les 5 minutes
```
*/5 * * * *
```

---

## 🔧 Commandes de Gestion

### Traiter les Documents en Attente

```bash
# Document spécifique (ex: document 48)
php artisan documents:process-pending --document=48

# Forcer le retraitement
php artisan documents:process-pending --document=48 --force

# Tous les documents non indexés
php artisan documents:process-pending
```

### Vérification et Diagnostic

```bash
# Vérifier l'état de la queue
php check_queue_status.php

# Vérifier si le worker est actif
ps aux | grep queue:work

# Voir les logs du worker
tail -f storage/logs/queue-worker.log

# Voir les logs Laravel
tail -f storage/logs/laravel.log

# Script complet de diagnostic et correction
bash scripts/fix_automatic_processing.sh
```

### Gestion du Worker

```bash
# Démarrer manuellement (pour tests)
php artisan queue:work --tries=3 --timeout=300

# Traiter un seul job (test)
php artisan queue:work --once

# Redémarrer le worker
php artisan queue:restart

# Voir les jobs échoués
php artisan queue:failed

# Retry d'un job échoué
php artisan queue:retry all
```

---

## 🧪 Test du Pipeline Complet

### 1. Diagnostic Initial
```bash
cd /home/threesixty/yyy/Dossy
php check_queue_status.php
```

### 2. Traiter le Document 48
```bash
php artisan documents:process-pending --document=48 --force
```

### 3. Vérifier le Résultat
```bash
php -r "
require 'bootstrap/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
\$doc = DB::table('submitted_documents')->where('id', 48)->first();
echo 'Texte extrait: ' . (strlen(\$doc->extracted_text ?? '') > 0 ? 'OUI' : 'NON') . PHP_EOL;
echo 'Indexé Pinecone: ' . (\$doc->is_indexed ?? 0 ? 'OUI' : 'NON') . PHP_EOL;
"
```

### 4. Tester dans Flutter
- Ouvrez l'app mobile
- Allez dans le chat
- Posez une question sur le contenu du document ARRETE 004

---

## 🚨 Alternative: Traitement Synchrone (Temporaire)

Si vous voulez tester rapidement SANS worker, modifiez `.env`:

```env
QUEUE_CONNECTION=sync
```

⚠️ **Attention:** 
- Traitement synchrone = bloquant pendant l'upload
- À utiliser uniquement pour tests
- Pas recommandé en production

---

## 📊 Monitoring

Créez `monitor-queue.sh`:

```bash
#!/bin/bash
cd /home/threesixty/yyy/Dossy

echo "=== Queue Status ==="
php artisan queue:work --once --quiet && echo "✅ Queue working" || echo "❌ Queue issue"

echo ""
echo "=== Pending Jobs ==="
php -r "
require 'bootstrap/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
echo 'Pending: ' . DB::table('jobs')->count() . ' jobs' . PHP_EOL;
"

echo ""
echo "=== Worker Process ==="
ps aux | grep queue:work | grep -v grep || echo "❌ No worker running"
```

---

## ✅ Checklist de Configuration

- [ ] `.env` a `QUEUE_CONNECTION=database`
- [ ] Table `jobs` créée (`php artisan queue:table && migrate`)
- [ ] Script `queue-worker.sh` créé et exécutable
- [ ] Cron job configuré dans AlwaysData (*/5 * * * *)
- [ ] Document 48 traité avec `documents:process-pending`
- [ ] Chat Flutter teste et fonctionne
- [ ] Logs `queue-worker.log` monitored

---

## 🎯 Résumé

**Cause du problème:** Worker de queue non actif → jobs non exécutés

**Solution:**
1. Créer script `queue-worker.sh`
2. Configurer cron job AlwaysData (*/5 * * * *)
3. Traiter documents existants: `php artisan documents:process-pending`

**Vérification:**
```bash
bash scripts/fix_automatic_processing.sh
```

**Résultat attendu:**
- Documents uploadés → extraction automatique → indexation Pinecone → chat fonctionne ✅
