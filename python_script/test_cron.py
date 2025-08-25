#!/usr/bin/env python3
import datetime

# Log file path
log_file = "/var/www/backup/cron.log"

# Current timestamp
now = datetime.datetime.now()
timestamp = now.strftime("%Y-%m-%d %H:%M:%S")

# Append timestamp to log
with open(log_file, "a") as f:
    f.write(f"Cron job executed at {timestamp}\n")
