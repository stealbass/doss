# Queue Worker - Configuration Production (Serveur en Ligne)

## 🌐 Architecture Déployée

```
┌─────────────────────┐
│  Play Store         │
│  (App Flutter)      │ ──────┐
└─────────────────────┘       │
                              │ API Calls
┌─────────────────────┐       │
│  Utilisateurs       │       │
│  (Navigateur Web)   │ ──────┤
└─────────────────────┘       ↓
                        ┌─────────────────────┐
                        │ Serveur Laravel     │
                        │ (Hébergement Web)   │
                        │                     │
                        │ ✅ API REST         │
                        │ ✅ Admin Panel      │
                        │ ⚠️ Queue Worker ?   │
                        └─────────────────────┘
```

**Question Clé**: Comment faire tourner le Queue Worker 24/7 sur le serveur ?

---

## 🎯 Solutions par Type d'Hébergement

### Option 1: VPS/Serveur Dédié avec SSH (RECOMMANDÉ) ⭐⭐⭐⭐⭐

**Type**: DigitalOcean, Linode, AWS EC2, OVH VPS, Hetzner, etc.

#### A. Installation Supervisor (Daemon Manager)

```bash
# 1. Installer Supervisor
sudo apt update
sudo apt install supervisor

# 2. Créer le fichier de configuration
sudo nano /etc/supervisor/conf.d/dossy-queue-worker.conf
```

**Contenu du fichier** (`dossy-queue-worker.conf`):
```ini
[program:dossy-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/dossy/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/dossy/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Paramètres Expliqués**:
- `command`: Chemin ABSOLU vers PHP et artisan
- `numprocs=2`: Lance 2 workers en parallèle (ajustable selon charge)
- `user=www-data`: Utilisateur Linux qui exécute le worker (même que Apache/Nginx)
- `autostart=true`: Démarre au boot du serveur
- `autorestart=true`: Redémarre automatiquement en cas d'erreur
- `max-time=3600`: Worker redémarre après 1h (évite memory leaks)
- `stdout_logfile`: Logs du worker

```bash
# 3. Recharger Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# 4. Démarrer le worker
sudo supervisorctl start dossy-queue-worker:*

# 5. Vérifier le statut
sudo supervisorctl status dossy-queue-worker:*
```

**Commandes Utiles**:
```bash
# Arrêter tous les workers
sudo supervisorctl stop dossy-queue-worker:*

# Redémarrer tous les workers
sudo supervisorctl restart dossy-queue-worker:*

# Voir les logs en temps réel
tail -f /var/www/dossy/storage/logs/queue-worker.log

# Relancer après modification du code (déploiement)
sudo supervisorctl restart dossy-queue-worker:*
php artisan queue:restart  # Alternative Laravel
```

**Avantages**:
- ✅ Solution professionnelle standard Laravel
- ✅ Redémarrage automatique
- ✅ Scalabilité (multiple workers)
- ✅ Logs centralisés
- ✅ Contrôle total

---

### Option 2: Hébergement Mutualisé (Shared Hosting) ⭐⭐⭐

**Type**: AlwaysData, OVH mutualisé, Hostinger, o2switch, etc.

**Limitation**: Pas d'accès root, pas de Supervisor, **seulement des cron jobs**

#### Solution: Cron Job avec Lock

**Problème**: Les crons ne sont pas des daemons permanents.

**Workaround**: Exécuter le worker toutes les minutes avec un verrou.

```bash
# Créer un cron job via le panel d'hébergement
* * * * * cd /home/username/www && /usr/bin/php artisan queue:work database --stop-when-empty --tries=3 --timeout=300 >> storage/logs/queue-worker.log 2>&1
```

**Explication**:
- `* * * * *`: Toutes les minutes
- `--stop-when-empty`: Traite tous les jobs disponibles puis s'arrête (important pour cron)
- `>> storage/logs/queue-worker.log`: Stocke les logs

**Alternative avec Timeout**:
```bash
# Exécute pendant 55 secondes max
* * * * * cd /home/username/www && timeout 55s /usr/bin/php artisan queue:work database --sleep=3 --tries=3 --timeout=300 >> storage/logs/queue-worker.log 2>&1
```

**Avantages**:
- ✅ Fonctionne sur hébergement mutualisé
- ✅ Aucune installation requise

**Inconvénients**:
- ⚠️ Délai de traitement jusqu'à 1 minute (moins réactif)
- ⚠️ Plusieurs instances peuvent se lancer en même temps (risque de doublons)

#### Amélioration avec Lock File

Créer un script `queue-worker-cron.php`:
```php
<?php
// Fichier: /home/username/www/queue-worker-cron.php

$lockFile = __DIR__ . '/storage/framework/queue.lock';

// Vérifier si un worker est déjà en cours
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    // Si le lock a plus de 5 minutes, considérer comme mort
    if (time() - $lockTime < 300) {
        echo "Worker already running\n";
        exit(0);
    }
}

// Créer le lock
touch($lockFile);

// Exécuter le worker
$output = [];
$returnVar = 0;
exec('cd ' . __DIR__ . ' && /usr/bin/php artisan queue:work database --stop-when-empty --tries=3 --timeout=300 2>&1', $output, $returnVar);

// Supprimer le lock
unlink($lockFile);

// Afficher les logs
echo implode("\n", $output);
exit($returnVar);
```

**Cron à configurer**:
```bash
* * * * * /usr/bin/php /home/username/www/queue-worker-cron.php >> /home/username/www/storage/logs/queue-worker-cron.log 2>&1
```

---

### Option 3: AlwaysData (Configuration Spécifique) ⭐⭐⭐⭐

**AlwaysData permet les daemons SSH personnalisés !**

#### Via Interface Web

1. **Se connecter à AlwaysData**
2. **Menu "Advanced" → "Daemons"**
3. **Cliquer "Add a daemon"**

**Configuration**:
- **Command**: `/usr/bin/php8.2 artisan queue:work database --sleep=3 --tries=3 --timeout=300`
- **Working directory**: `/home/USERNAME/www/`
- **Auto-restart**: ✅ Oui
- **Environment**: Production

#### Via SSH (Alternative)

```bash
# Se connecter en SSH
ssh username@ssh-username.alwaysdata.net

# Créer un script de démarrage
nano ~/queue-worker.sh
```

**Contenu du script**:
```bash
#!/bin/bash
cd ~/www
while true; do
    /usr/bin/php8.2 artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=300
    echo "Worker stopped. Restarting in 5 seconds..."
    sleep 5
done
```

```bash
# Rendre le script exécutable
chmod +x ~/queue-worker.sh

# Créer le daemon via l'interface AlwaysData:
# Command: /home/USERNAME/queue-worker.sh
# Working directory: /home/USERNAME/www
```

**Avantages AlwaysData**:
- ✅ Daemon natif (pas besoin de cron hack)
- ✅ Interface graphique simple
- ✅ Redémarrage automatique
- ✅ Logs intégrés

---

### Option 4: Laravel Forge / Vapor ⭐⭐⭐⭐⭐

**Laravel Forge** (Laravel officiel pour VPS):
1. Connecter votre serveur (DigitalOcean, Linode, AWS, etc.)
2. Aller dans l'onglet "Queue"
3. Cliquer "New Worker"
4. Forge configure automatiquement Supervisor

**Laravel Vapor** (Serverless AWS):
- Les queues sont gérées automatiquement via AWS SQS
- Aucune configuration manuelle requise

---

## 📋 Configuration par Hébergeur Populaire

| Hébergeur | Méthode Recommandée | Difficulté |
|-----------|---------------------|------------|
| **AlwaysData** | Daemon SSH (Interface Web) | ⭐ Facile |
| **OVH VPS** | Supervisor | ⭐⭐ Moyen |
| **OVH Mutualisé** | Cron Job + Lock | ⭐⭐ Moyen |
| **DigitalOcean** | Supervisor | ⭐⭐ Moyen |
| **AWS EC2** | Supervisor | ⭐⭐ Moyen |
| **Laravel Forge** | Interface Forge | ⭐ Facile |
| **Hostinger** | Cron Job + Lock | ⭐⭐⭐ Difficile |
| **Heroku** | Procfile Worker | ⭐⭐ Moyen |

---

## 🚀 Déploiement - Checklist Complète

### 1. Avant le Déploiement (Local)

```bash
# Tester le job localement
php artisan queue:work --once

# Vérifier les migrations
php artisan migrate:status

# Tester un upload et vérifier les logs
tail -f storage/logs/laravel.log
```

### 2. Sur le Serveur

```bash
# 1. Pull du code
git pull origin main

# 2. Installer les dépendances
composer install --no-dev --optimize-autoloader

# 3. Exécuter les migrations
php artisan migrate --force

# 4. Clear cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 3. Démarrer le Queue Worker

**VPS avec Supervisor**:
```bash
sudo supervisorctl restart dossy-queue-worker:*
```

**Hébergement Mutualisé**:
```bash
# Configurer le cron job via le panel
* * * * * cd /home/username/www && /usr/bin/php artisan queue:work database --stop-when-empty
```

**AlwaysData**:
```
Interface Web → Daemons → Restart
```

### 4. Vérification

```bash
# Voir les jobs en attente
php artisan queue:monitor database

# Vérifier les logs
tail -f storage/logs/laravel.log
tail -f storage/logs/queue-worker.log

# Tester un upload depuis Flutter
# → Vérifier que le processing_status passe de pending → processing → completed
```

---

## 🔄 Redéploiement (Après Modifications Code)

**Important**: Redémarrer le worker après chaque déploiement !

### Méthode Laravel (Graceful Restart)
```bash
# Cette commande termine les jobs en cours puis redémarre
php artisan queue:restart
```

### Méthode Supervisor (Force Restart)
```bash
sudo supervisorctl restart dossy-queue-worker:*
```

### Script de Déploiement Automatique
```bash
#!/bin/bash
# deploy.sh

echo "Pulling code..."
git pull origin main

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Restarting queue workers..."
php artisan queue:restart

# Si Supervisor
sudo supervisorctl restart dossy-queue-worker:* 2>/dev/null || true

echo "Deployment complete!"
```

---

## 📊 Monitoring Production

### Via Base de Données

```sql
-- Jobs en attente (devrait être ~0 si worker fonctionne)
SELECT COUNT(*) FROM jobs;

-- Jobs échoués récents
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;

-- Documents en traitement
SELECT id, original_filename, processing_status, created_at
FROM submitted_documents
WHERE processing_status IN ('pending', 'processing')
ORDER BY created_at DESC;
```

### Via Laravel Horizon (Optionnel mais Recommandé)

**Installation**:
```bash
composer require laravel/horizon

php artisan horizon:install

php artisan migrate
```

**Accès**: `https://votredomaine.com/horizon`

**Interface Web avec**:
- Statistiques en temps réel
- Historique des jobs
- Failed jobs avec retry
- Monitoring des performances

---

## ⚠️ Problèmes Courants

### 1. Jobs Ne Se Traitent Pas

**Vérification**:
```bash
# Le worker tourne-t-il ?
ps aux | grep "queue:work"

# Y a-t-il des jobs en attente ?
php artisan queue:monitor database

# Erreurs dans les logs ?
tail -n 100 storage/logs/laravel.log
```

**Solutions**:
```bash
# Redémarrer le worker
php artisan queue:restart

# Relancer les failed jobs
php artisan queue:retry all

# Nettoyer les jobs bloqués (plus de 1h)
php artisan queue:flush
```

### 2. Worker Se Stop Aléatoirement

**Causes**:
- Timeout trop court
- Memory leak
- Pas de redémarrage automatique

**Solutions**:
```bash
# Augmenter timeout et max-time
queue:work --timeout=600 --max-time=7200

# Vérifier Supervisor auto-restart
sudo supervisorctl status

# Ajouter memory limit
queue:work --memory=512
```

### 3. Permissions Erreurs

```bash
# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Vérifier l'utilisateur du worker
ps aux | grep "queue:work"
# Doit être www-data (ou utilisateur web)
```

---

## 🎯 Recommandation Finale

### Pour Production (Serveur Laravel en Ligne)

**1. Si VPS/Dédié (accès root)**:
- ✅ **Utiliser Supervisor** (solution standard Laravel)
- ✅ 2-3 workers en parallèle
- ✅ Logs centralisés
- ✅ Monitoring avec Horizon

**2. Si AlwaysData**:
- ✅ **Daemon SSH natif** (interface web)
- ✅ Simple et efficace
- ✅ Redémarrage automatique

**3. Si Hébergement Mutualisé Basique**:
- ✅ **Cron Job + Lock File** (workaround)
- ⚠️ Moins performant mais fonctionne
- ⚠️ Délai jusqu'à 1 minute

### Pour Développement Local (Windows)

- ✅ **NSSM Service** (déjà fourni dans install-queue-service.bat)
- ✅ Ou simplement: `php artisan queue:work` dans un terminal

---

## 📞 Support et Ressources

**Documentation Laravel**:
- Queue: https://laravel.com/docs/10.x/queues
- Supervisor: https://laravel.com/docs/10.x/queues#supervisor-configuration
- Horizon: https://laravel.com/docs/10.x/horizon

**Exemples de Configuration**:
- Voir `/docs/supervisor-example.conf`
- Voir `/docs/cron-example.sh`
- Voir `/docs/deploy.sh`

**Quel est votre hébergeur actuel ?** (pour recommandation précise)
