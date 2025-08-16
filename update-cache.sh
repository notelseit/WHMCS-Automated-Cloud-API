#!/bin/bash

# Hetzner VPS Module Auto-Update Script
# This script automatically updates the cached API data
# Run this via cron job every 6 hours: 0 */6 * * * /path/to/update_cache.sh

# Configuration
MODULE_PATH="/path/to/your/whmcs/modules/servers/hetznervps"
LOG_FILE="$MODULE_PATH/logs/cache_update.log"

# Create log directory if it doesn't exist
mkdir -p "$(dirname "$LOG_FILE")"

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

log_message "Starting cache update process"

# Check if PHP is available
if ! command -v php &> /dev/null; then
    log_message "ERROR: PHP is not available"
    exit 1
fi

# Run PHP script to update cache
php -f "$MODULE_PATH/scripts/update_cache.php"

if [ $? -eq 0 ]; then
    log_message "Cache update completed successfully"
else
    log_message "ERROR: Cache update failed"
    exit 1
fi

# Clean up old log files (keep last 30 days)
find "$MODULE_PATH/logs" -name "*.log" -mtime +30 -delete

log_message "Cache update process finished"