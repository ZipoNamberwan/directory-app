#!/usr/bin/env python3
"""
Database Backup Cleanup Script

This script deletes backup files older than X days for all three databases:
DB_MAIN, DB_2, and DB_3.

Requirements:
    - python-dotenv (optional, for configuration)

Install with: pip install python-dotenv
"""

import os
import glob
import time
import sys
import argparse
from datetime import datetime, timedelta
from dotenv import load_dotenv

# Load environment variables (optional)
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION
# =====================================================================

# Backup directory relative to script location
BACKUP_DIR = os.path.join(os.path.dirname(__file__), '..', 'backup')

# Default retention days (can be overridden by command line or environment)
DEFAULT_RETENTION_DAYS = int(os.getenv('BACKUP_RETENTION_DAYS', 4))

# Database prefixes to clean
DATABASE_PREFIXES = ['DB_MAIN', 'DB_2', 'DB_3']

class BackupCleaner:
    """Manages cleanup of old database backup files"""
    
    def __init__(self, retention_days=DEFAULT_RETENTION_DAYS, dry_run=False):
        self.retention_days = retention_days
        self.dry_run = dry_run
        self.backup_dir = BACKUP_DIR
        
        # Calculate cutoff date
        self.cutoff_date = datetime.now() - timedelta(days=retention_days)
        
        print(f"🧹 Backup Cleanup Configuration:")
        print(f"   Backup directory: {self.backup_dir}")
        print(f"   Retention period: {self.retention_days} days")
        print(f"   Cutoff date: {self.cutoff_date.strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"   Mode: {'DRY RUN (no files will be deleted)' if self.dry_run else 'LIVE (files will be deleted)'}")
        print(f"   Databases: {', '.join(DATABASE_PREFIXES)}")
        
        if not os.path.exists(self.backup_dir):
            raise FileNotFoundError(f"Backup directory not found: {self.backup_dir}")
    
    def find_old_backup_files(self, db_prefix):
        """Find backup files older than retention period for a specific database"""
        try:
            # Pattern to match backup files for this database
            pattern = os.path.join(self.backup_dir, f'{db_prefix}_*.sql')
            all_files = glob.glob(pattern)
            
            if not all_files:
                print(f"   No backup files found for {db_prefix}")
                return []
            
            old_files = []
            current_files = []
            
            for file_path in all_files:
                # Get file modification time
                file_mtime = datetime.fromtimestamp(os.path.getmtime(file_path))
                file_age_days = (datetime.now() - file_mtime).days
                
                if file_mtime < self.cutoff_date:
                    old_files.append({
                        'path': file_path,
                        'name': os.path.basename(file_path),
                        'mtime': file_mtime,
                        'age_days': file_age_days,
                        'size': os.path.getsize(file_path)
                    })
                else:
                    current_files.append({
                        'path': file_path,
                        'name': os.path.basename(file_path),
                        'mtime': file_mtime,
                        'age_days': file_age_days,
                        'size': os.path.getsize(file_path)
                    })
            
            # Sort by age (oldest first)
            old_files.sort(key=lambda x: x['mtime'])
            current_files.sort(key=lambda x: x['mtime'], reverse=True)
            
            print(f"   📊 {db_prefix} Analysis:")
            print(f"      Files to delete: {len(old_files)}")
            print(f"      Files to keep: {len(current_files)}")
            
            if old_files:
                total_size = sum(f['size'] for f in old_files)
                print(f"      Space to be freed: {self.format_file_size(total_size)}")
                
                # Show oldest and newest files to be deleted
                oldest = old_files[0]
                newest = old_files[-1]
                print(f"      Oldest file to delete: {oldest['name']} ({oldest['age_days']} days old)")
                print(f"      Newest file to delete: {newest['name']} ({newest['age_days']} days old)")
            
            if current_files:
                newest_kept = current_files[0]
                oldest_kept = current_files[-1]
                print(f"      Newest file to keep: {newest_kept['name']} ({newest_kept['age_days']} days old)")
                print(f"      Oldest file to keep: {oldest_kept['name']} ({oldest_kept['age_days']} days old)")
            
            return old_files
            
        except Exception as e:
            print(f"   ✗ Error analyzing {db_prefix} files: {e}")
            return []
    
    def delete_old_files(self, old_files, db_prefix):
        """Delete old backup files for a specific database"""
        if not old_files:
            print(f"   ✓ No files to delete for {db_prefix}")
            return 0, 0
        
        deleted_count = 0
        deleted_size = 0
        failed_count = 0
        
        print(f"   🗑️  Deleting {len(old_files)} old files for {db_prefix}...")
        
        for file_info in old_files:
            try:
                file_path = file_info['path']
                file_name = file_info['name']
                file_size = file_info['size']
                age_days = file_info['age_days']
                
                if self.dry_run:
                    print(f"      [DRY RUN] Would delete: {file_name} ({age_days} days, {self.format_file_size(file_size)})")
                    deleted_count += 1
                    deleted_size += file_size
                else:
                    os.remove(file_path)
                    print(f"      ✓ Deleted: {file_name} ({age_days} days, {self.format_file_size(file_size)})")
                    deleted_count += 1
                    deleted_size += file_size
                    
            except Exception as e:
                print(f"      ✗ Failed to delete {file_info['name']}: {e}")
                failed_count += 1
        
        if failed_count > 0:
            print(f"   ⚠️  {failed_count} files failed to delete for {db_prefix}")
        
        return deleted_count, deleted_size
    
    def clean_database_backups(self, db_prefix):
        """Clean backup files for a specific database"""
        print(f"\n📁 Processing {db_prefix} backups...")
        print("-" * 40)
        
        # Find old files
        old_files = self.find_old_backup_files(db_prefix)
        
        # Delete old files
        deleted_count, deleted_size = self.delete_old_files(old_files, db_prefix)
        
        return deleted_count, deleted_size
    
    def run_cleanup(self):
        """Execute cleanup for all databases"""
        try:
            print("🚀 Starting backup cleanup process...")
            print("=" * 60)
            
            total_deleted = 0
            total_size_freed = 0
            
            # Process each database
            for db_prefix in DATABASE_PREFIXES:
                deleted_count, deleted_size = self.clean_database_backups(db_prefix)
                total_deleted += deleted_count
                total_size_freed += deleted_size
            
            # Summary
            print("\n" + "=" * 60)
            print("✅ Cleanup process completed!")
            print(f"   Total files processed: {total_deleted}")
            print(f"   Total space freed: {self.format_file_size(total_size_freed)}")
            
            if self.dry_run:
                print("   📝 This was a DRY RUN - no files were actually deleted")
                print("   💡 Run without --dry-run to perform actual deletion")
            
            return 0
            
        except Exception as e:
            print("=" * 60)
            print(f"❌ Cleanup process failed: {e}")
            return 1
    
    def format_file_size(self, size_bytes):
        """Format file size in human readable format"""
        if size_bytes == 0:
            return "0 B"
        
        size_names = ["B", "KB", "MB", "GB", "TB"]
        import math
        i = int(math.floor(math.log(size_bytes, 1024)))
        p = math.pow(1024, i)
        s = round(size_bytes / p, 2)
        return f"{s} {size_names[i]}"

def show_backup_summary():
    """Show summary of all backup files"""
    try:
        print("📊 Current Backup File Summary")
        print("=" * 60)
        
        total_files = 0
        total_size = 0
        
        for db_prefix in DATABASE_PREFIXES:
            pattern = os.path.join(BACKUP_DIR, f'{db_prefix}_*.sql')
            files = glob.glob(pattern)
            
            if not files:
                print(f"{db_prefix}: No files found")
                continue
            
            files.sort(key=os.path.getmtime, reverse=True)
            db_size = sum(os.path.getsize(f) for f in files)
            
            oldest_file = files[-1] if files else None
            newest_file = files[0] if files else None
            
            oldest_date = datetime.fromtimestamp(os.path.getmtime(oldest_file)) if oldest_file else None
            newest_date = datetime.fromtimestamp(os.path.getmtime(newest_file)) if newest_file else None
            
            print(f"{db_prefix}:")
            print(f"   Files: {len(files)}")
            print(f"   Total size: {BackupCleaner(1).format_file_size(db_size)}")
            if oldest_date and newest_date:
                age_span = (newest_date - oldest_date).days
                print(f"   Date range: {oldest_date.strftime('%Y-%m-%d')} to {newest_date.strftime('%Y-%m-%d')} ({age_span} days)")
                print(f"   Oldest: {os.path.basename(oldest_file)} ({(datetime.now() - oldest_date).days} days old)")
                print(f"   Newest: {os.path.basename(newest_file)} ({(datetime.now() - newest_date).days} days old)")
            print()
            
            total_files += len(files)
            total_size += db_size
        
        print(f"📋 Grand Total:")
        print(f"   All files: {total_files}")
        print(f"   All databases size: {BackupCleaner(1).format_file_size(total_size)}")
        
    except Exception as e:
        print(f"❌ Error generating summary: {e}")

def show_help():
    """Show usage help"""
    script_name = os.path.basename(__file__)
    print(f"""
🧹 Database Backup Cleanup Script Usage:

Basic Commands:
  python {script_name} --days 7                    # Delete files older than 7 days
  python {script_name} --days 30 --dry-run        # Preview what would be deleted
  python {script_name} --summary                   # Show backup file summary

Options:
  --days N          Number of days to retain (default: {DEFAULT_RETENTION_DAYS})
  --dry-run         Preview mode - show what would be deleted without deleting
  --summary         Show summary of all backup files
  --help, -h        Show this help message

Supported Databases:
  - DB_MAIN_*.sql files
  - DB_2_*.sql files  
  - DB_3_*.sql files

Environment Variables (.env file):
  BACKUP_RETENTION_DAYS=7                         # Default retention period

Examples:
  python {script_name} --summary                  # Check current backups
  python {script_name} --days 14 --dry-run       # Preview 14-day cleanup
  python {script_name} --days 7                  # Delete files older than 7 days
  python {script_name} --days 30                 # Delete files older than 30 days

Safety Features:
  - Dry run mode for testing
  - Detailed logging of all operations
  - File age verification before deletion
  - Per-database processing with error isolation
  - Summary reporting of space freed

Note: 
  - Files are identified by modification time
  - Only .sql files matching the database patterns are processed
  - The script preserves files within the retention period
""")

def main():
    """Main function"""
    try:
        # Parse command line arguments
        parser = argparse.ArgumentParser(description='Clean up old database backup files')
        parser.add_argument('--days', type=int, default=DEFAULT_RETENTION_DAYS,
                          help=f'Number of days to retain backups (default: {DEFAULT_RETENTION_DAYS})')
        parser.add_argument('--dry-run', action='store_true',
                          help='Preview mode - show what would be deleted without deleting')
        parser.add_argument('--summary', action='store_true',
                          help='Show summary of all backup files')
        parser.add_argument('--help-extended', action='store_true',
                          help='Show extended help')
        
        args = parser.parse_args()
        
        # Show help if requested
        if args.help_extended:
            show_help()
            return 0
        
        # Show summary if requested
        if args.summary:
            show_backup_summary()
            return 0
        
        # Validate arguments
        if args.days <= 0:
            print("❌ Error: --days must be a positive number")
            return 1
        
        # Confirmation for live runs
        if not args.dry_run and args.days < 7:
            print(f"⚠️  Warning: You're about to delete files older than {args.days} days")
            print("   This is a relatively short retention period.")
            response = input("   Continue? (y/N): ").lower().strip()
            if response not in ['y', 'yes']:
                print("❌ Operation cancelled by user")
                return 1
        
        # Initialize and run cleaner
        cleaner = BackupCleaner(
            retention_days=args.days,
            dry_run=args.dry_run
        )
        
        return cleaner.run_cleanup()
        
    except KeyboardInterrupt:
        print("\n❌ Cleanup cancelled by user")
        return 1
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        return 1

if __name__ == "__main__":
    exit(main())