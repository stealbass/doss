#!/bin/bash
# ============================================
# Daemon Queue Worker pour AlwaysData
# ============================================

# Configuration
PHP_VERSION="8.2"  # Ajuster selon votre version PHP AlwaysData
APP_DIR="$HOME/www"
LOG_FILE="$APP_DIR/storage/logs/queue-daemon.log"

# Fonction de log
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_message "========================================="
log_message "Starting Dossy Queue Worker Daemon"
log_message "PHP Version: $PHP_VERSION"
log_message "App Directory: $APP_DIR"
log_message "========================================="

# Vérifier que le répertoire existe
if [ ! -d "$APP_DIR" ]; then
    log_message "ERROR: Directory $APP_DIR does not exist"
    exit 1
fi

# Aller dans le répertoire de l'application
cd "$APP_DIR" || {
    log_message "ERROR: Cannot change to directory $APP_DIR"
    exit 1
}

# Boucle infinie avec redémarrage automatique
RESTART_COUNT=0
while true; do
    log_message "Starting worker (restart count: $RESTART_COUNT)..."
    
    # Exécuter le queue worker
    # --max-time=3600 : Le worker redémarre automatiquement après 1h
    /usr/bin/php$PHP_VERSION artisan queue:work database \
        --sleep=3 \
        --tries=3 \
        --max-time=3600 \
        --timeout=300 \
        --memory=512 \
        >> "$LOG_FILE" 2>&1
    
    EXIT_CODE=$?
    RESTART_COUNT=$((RESTART_COUNT + 1))
    
    if [ $EXIT_CODE -eq 0 ]; then
        log_message "Worker stopped gracefully (code: $EXIT_CODE)"
    else
        log_message "WARNING: Worker exited with error code $EXIT_CODE"
    fi
    
    # Attendre 5 secondes avant de redémarrer
    log_message "Restarting in 5 seconds..."
    sleep 5
done
