#!/bin/bash
# PURPOSE: Nightly cron script to handle back-end tasks for CC-Plus
# -----------------------------------------------------------------
echo "CC-Plus Nightly Process : "`date +%d-%b-%y\ %H:%M`
#
# Process all SUSHI targets due to be run today
#
cd "$(dirname "$0")"
echo "CC-Plus Sushi Ingest"
echo "--------------------"
php ./Sushi_ingest.php
#
# Check for and retry failed ingests
#
echo "CC-Plus Ingest Retries"
echo "----------------------"
php ./Retry_Failed_Ingest.php
#
# Update system alerts
#
php ./Update_Alerts.php
#
# Send email notices
#
php ./Mail_Alerts.php
#
# Clean up any old temporary data or files
#
# php ./HouseKeeping.php
#
# zap all files older than 3 days from temp directory
find /usr/local/stats_reports/temp -mtime +3 -type f -delete
#
echo "--------------------------------------------"
echo "CC-Plus Nightly Script Done: "`date +%d-%b-%y\ %H:%M`
echo "--------------------------------------------"
