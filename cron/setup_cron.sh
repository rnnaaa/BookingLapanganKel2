#!/bin/bash
# Quick Setup Script untuk Cron Job - Release Expired Bookings
# Untuk Linux/Mac users

# ===================== SETUP CRON JOB =====================

# 1. Tentukan path PHP
PHP_PATH=$(which php)
echo "📍 PHP Path: $PHP_PATH"

# 2. Tentukan path script
SCRIPT_PATH="/var/www/BookingLapanganKel2/cron/cron_release_expired_bookings.php"
LOG_PATH="/var/log/cron_release_bookings.log"

# 3. Set permissions
chmod 755 $SCRIPT_PATH
echo "✅ File permissions set"

# 4. Buat log file jika belum ada
touch $LOG_PATH
chmod 666 $LOG_PATH
echo "✅ Log file created"

# 5. Tambah ke crontab
(crontab -l 2>/dev/null; echo "*/2 * * * * $PHP_PATH $SCRIPT_PATH >> $LOG_PATH 2>&1") | crontab -
echo "✅ Cron job added (every 2 minutes)"

# 6. Verify
echo ""
echo "📋 Current crontab entries:"
crontab -l | grep cron_release

echo ""
echo "✨ Setup complete! Cron job is now running."
echo "📊 Monitor log: tail -f $LOG_PATH"
