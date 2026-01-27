#!/bin/bash
# ============================================
# Queue Worker avec boucle de redémarrage
# Pour hébergement mutualisé via CRON
# ============================================

# Configuration
LOCK_FILE="/home/username/www/storage/framework/queue.lock"
LOG_FILE="/home/username/www/storage/logs/queue-worker-cron.log"
PHP_PATH="/usr/bin/php"
APP_DIR="/home/username/www"

# Fonction de log
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Vérifier si un worker est déjà en cours
if [ -f "$LOCK_FILE" ]; then
    LOCK_TIME=$(stat -c %Y "$LOCK_FILE" 2>/dev/null || stat -f %m "$LOCK_FILE" 2>/dev/null)
    CURRENT_TIME=$(date +%s)
    TIME_DIFF=$((CURRENT_TIME - LOCK_TIME))
    
    # Si le lock a moins de 5 minutes, considérer le worker comme actif
    if [ $TIME_DIFF -lt 300 ]; then
        log_message "Worker already running (lock age: ${TIME_DIFF}s)"
        exit 0
    else
        log_message "Stale lock detected (age: ${TIME_DIFF}s), removing..."
        rm -f "$LOCK_FILE"
    fi
fi

# Créer le lock
touch "$LOCK_FILE"
log_message "Starting queue worker..."

# Se déplacer dans le répertoire de l'application
cd "$APP_DIR" || {
    log_message "ERROR: Cannot change to directory $APP_DIR"
    rm -f "$LOCK_FILE"
    exit 1
}

# Exécuter le queue worker
$PHP_PATH artisan queue:work database \
    --stop-when-empty \
    --tries=3 \
    --timeout=300 \
    --sleep=3 \
    >> "$LOG_FILE" 2>&1

EXIT_CODE=$?

# Supprimer le lock
rm -f "$LOCK_FILE"

if [ $EXIT_CODE -eq 0 ]; then
    log_message "Queue worker completed successfully"
else
    log_message "Queue worker exited with code $EXIT_CODE"
fi

exit $EXIT_CODE
