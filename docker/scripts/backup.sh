#!/bin/sh
set -e

# Go up two levels to get to the project root
BASEDIR="$(cd "$(dirname "$0")/../.." && pwd)"

# Load .env from project root
if [ -f "$BASEDIR/.env" ]; then
  set -a
  . "$BASEDIR/.env"
  set +a
else
  echo "❌ Missing .env file in $BASEDIR"
  exit 1
fi

# Set backup directory under project root (or change as needed)
BACKUP_DIR="$BASEDIR/backup"
DATE=$(date +%F)
mkdir -p "$BACKUP_DIR"

# Function to dump a single DB
dump_database() {
  DB_NAME="$1"
  DB_HOST="$2"
  DB_PORT="$3"
  DB_USER="$4"
  DB_PASS="$5"

  OUTFILE="$BACKUP_DIR/backup_${DB_NAME}_${DATE}.sql"

  echo "📦 Backing up $DB_NAME to $OUTFILE"
  export MYSQL_PWD="$DB_PASS"
  mysqldump --no-tablespaces -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" > "$OUTFILE"
  unset MYSQL_PWD
}

# Backup all 3 DBs
dump_database "$DB_MAIN_DATABASE" "$DB_MAIN_HOST" "$DB_MAIN_PORT" "$DB_MAIN_USERNAME" "$DB_MAIN_PASSWORD"
dump_database "$DB_2_DATABASE" "$DB_2_HOST" "$DB_2_PORT" "$DB_2_USERNAME" "$DB_2_PASSWORD"
dump_database "$DB_3_DATABASE" "$DB_3_HOST" "$DB_3_PORT" "$DB_3_USERNAME" "$DB_3_PASSWORD"
