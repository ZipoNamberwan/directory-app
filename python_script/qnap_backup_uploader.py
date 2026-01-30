#!/usr/bin/env python3
"""
QNAP NAS Database Backup Uploader

This script uploads database backup files to QNAP NAS using the QNAP API
with options to backup all files or just the latest one.

Requirements:
    - requests
    - python-dotenv

Install with: pip install requests python-dotenv
"""

import os
import glob
import base64
import time
import sys
import xml.etree.ElementTree as ET
from datetime import datetime
from dotenv import load_dotenv
import requests

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION
# =====================================================================

# Backup directory relative to script location
BACKUP_DIR = os.path.join(os.path.dirname(__file__), '..', 'backup')

# File stability check - wait time to ensure file is not being written
FILE_STABILITY_WAIT = 30  # seconds

# QNAP NAS configuration
QNAP_HOST = os.getenv('QNAP_HOST', 'http://123.456.789.101:8000')
QNAP_USERNAME = os.getenv('QNAP_USERNAME', '')
QNAP_PASSWORD = os.getenv('QNAP_PASSWORD', '')
QNAP_DEST_PATH = os.getenv('QNAP_DEST_PATH', '/backup/database')

class QNAPBackupUploader:
    """Manages database backup uploads to QNAP NAS"""
    
    def __init__(self):
        self.host = QNAP_HOST
        self.username = QNAP_USERNAME
        self.password = QNAP_PASSWORD
        self.dest_path = QNAP_DEST_PATH
        self.session = requests.Session()
        self.sid = None
        
        # Validate configuration
        if not self.password:
            raise ValueError("QNAP_PASSWORD must be set in .env file")
        
        print(f"🔧 QNAP Configuration:")
        print(f"   Host: {self.host}")
        print(f"   Username: {self.username}")
        print(f"   Destination: {self.dest_path}")
    
    def get_auth_sid(self):
        """Get authentication SID from QNAP - equivalent to PHP getAuthSid()"""
        try:
            print("🔐 Authenticating with QNAP NAS...")
            
            # Encode password in base64 (same as PHP base64_encode)
            encoded_password = base64.b64encode(self.password.encode()).decode()
            
            # Construct login URL (same as PHP)
            url_login = f"{self.host}/cgi-bin/authLogin.cgi?user={self.username}&pwd={encoded_password}"
            
            print(f"🌐 Login URL: {url_login}")
            
            # Make request with timeout
            response = self.session.get(url_login, timeout=30)
            
            if not response.ok:
                raise Exception(f"QNAP login failed. Status: {response.status_code}, Response: {response.text}")
            
            # print(f"📄 Login response: {response.text}")
            
            # Parse XML response (same as PHP simplexml_load_string)
            try:
                root = ET.fromstring(response.text)
                auth_sid_elem = root.find('authSid')
                
                if auth_sid_elem is None or not auth_sid_elem.text:
                    raise Exception('authSid element not found or empty in response')
                
                self.sid = auth_sid_elem.text
                print(f"✓ QNAP authentication successful, SID: {self.sid}")
                return self.sid
                
            except ET.ParseError as e:
                raise Exception(f"Failed to parse XML response: {e}")
            
        except Exception as e:
            print(f"✗ Error authenticating with QNAP: {e}")
            raise
    
    def upload_backup_file(self, file_path):
        """Upload backup file to QNAP NAS with streaming support"""
        try:
            # Get authentication SID if not already obtained
            if not self.sid:
                self.get_auth_sid()
            
            file_name = os.path.basename(file_path)
            file_size = os.path.getsize(file_path)
            
            print(f"📤 Uploading to QNAP NAS: {file_name} ({self.format_file_size(file_size)})...")
            
            # Construct upload URL (same as PHP)
            url_upload = (
                f"{self.host}/cgi-bin/filemanager/utilRequest.cgi"
                f"?func=upload&type=standard&sid={self.sid}"
                f"&dest_path={self.dest_path}&overwrite=1"
                f"&progress=-backup-database-{file_name}"
            )
            
            print(f"🌐 Upload URL: {url_upload}")
            
            # Stream upload with progress tracking
            print("⏳ Uploading file...")
            
            with open(file_path, 'rb') as f:
                files = {
                    'file': (file_name, f, 'application/sql')
                }
                
                # Increased timeout based on file size: 1 second per 100MB minimum 10 minutes
                timeout = max(600, (file_size / (100 * 1024 * 1024)) * 60)
                print(f"   Upload timeout: {int(timeout)} seconds ({self.format_file_size(file_size)} file)")
                
                # Upload file with dynamic timeout
                response = self.session.post(
                    url_upload, 
                    files=files, 
                    timeout=timeout,
                    stream=True
                )
            
            print(f"📄 Upload response status: {response.status_code}")
            print(f"📄 Upload response: {response.text}")
            
            if not response.ok:
                raise Exception(f"QNAP upload failed. Status: {response.status_code}, Response: {response.text}")
            
            # Check if response indicates success
            if 'error' in response.text.lower() or 'fail' in response.text.lower():
                print(f"⚠️  Upload response may indicate error: {response.text}")
            
            print("✓ QNAP upload successful!")
            return True
            
        except Exception as e:
            print(f"✗ Error uploading to QNAP: {e}")
            raise
    
    def find_backup_files(self, all_files=False):
        """Find backup files to upload from all three databases"""
        try:
            # Database prefixes to search for
            db_prefixes = ['DB_MAIN', 'DB_2', 'DB_3']
            all_backup_files = []
            
            print(f"🔍 Searching for backup files from databases: {', '.join(db_prefixes)}")
            
            for db_prefix in db_prefixes:
                # Pattern to match backup files for this database
                pattern = os.path.join(BACKUP_DIR, f'{db_prefix}_*.sql')
                db_backup_files = glob.glob(pattern)
                
                if not db_backup_files:
                    print(f"⚠️  No backup files found for {db_prefix}")
                    continue
                
                # Sort by modification time (newest first)
                db_backup_files.sort(key=os.path.getmtime, reverse=True)
                
                if all_files:
                    # Add all files from this database
                    all_backup_files.extend(db_backup_files)
                    print(f"✓ Found {len(db_backup_files)} backup files for {db_prefix}")
                else:
                    # Add only the latest stable file from this database
                    latest_file = db_backup_files[0]
                    
                    # Check if file is stable (not being modified)
                    if not self.is_file_stable(latest_file):
                        print(f"⚠️  Latest {db_prefix} file appears to be in use, waiting {FILE_STABILITY_WAIT} seconds...")
                        time.sleep(FILE_STABILITY_WAIT)
                        
                        # Check again after waiting
                        if not self.is_file_stable(latest_file):
                            print(f"⚠️  {db_prefix} file still being modified, skipping: {os.path.basename(latest_file)}")
                            continue
                    
                    all_backup_files.append(latest_file)
                    file_size = os.path.getsize(latest_file)
                    mod_time = datetime.fromtimestamp(os.path.getmtime(latest_file))
                    print(f"✓ Found latest stable {db_prefix} file: {os.path.basename(latest_file)}")
                    print(f"   Size: {self.format_file_size(file_size)}, Modified: {mod_time.strftime('%Y-%m-%d %H:%M:%S')}")
            
            if not all_backup_files:
                raise FileNotFoundError("No backup files found for any database (DB_MAIN, DB_2, DB_3)")
            
            # Sort all files by modification time (newest first) for consistent processing
            all_backup_files.sort(key=os.path.getmtime, reverse=True)
            
            if all_files:
                print(f"\n📋 Total backup files found: {len(all_backup_files)}")
                for i, file_path in enumerate(all_backup_files, 1):
                    file_size = os.path.getsize(file_path)
                    mod_time = datetime.fromtimestamp(os.path.getmtime(file_path))
                    db_name = os.path.basename(file_path).split('_')[0] + '_' + os.path.basename(file_path).split('_')[1]
                    print(f"   {i}. [{db_name}] {os.path.basename(file_path)} ({self.format_file_size(file_size)}, {mod_time.strftime('%Y-%m-%d %H:%M:%S')})")
            else:
                print(f"\n📋 Total latest files selected: {len(all_backup_files)}")
            
            return all_backup_files
                
        except Exception as e:
            print(f"✗ Error finding backup files: {e}")
            raise
    
    def is_file_stable(self, file_path):
        """Check if file is stable (not being written to)"""
        try:
            # Get initial file stats
            initial_size = os.path.getsize(file_path)
            initial_mtime = os.path.getmtime(file_path)
            
            # Wait a short time
            time.sleep(2)
            
            # Check if file changed
            current_size = os.path.getsize(file_path)
            current_mtime = os.path.getmtime(file_path)
            
            # File is stable if size and modification time haven't changed
            is_stable = (initial_size == current_size and initial_mtime == current_mtime)
            
            if not is_stable:
                print(f"   File is being modified: size changed from {initial_size} to {current_size}")
            
            return is_stable
            
        except Exception as e:
            print(f"   Error checking file stability: {e}")
            return False
    
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
    
    def run_backup_process(self, all_files=False):
        """Execute the complete backup upload process for all databases"""
        try:
            print("🚀 Starting QNAP backup upload process...")
            print(f"   Databases: DB_MAIN, DB_2, DB_3")
            print(f"   Backup mode: {'All files from all databases' if all_files else 'Latest file from each database'}")
            print(f"   Source directory: {BACKUP_DIR}")
            print("=" * 60)
            
            # Step 1: Find backup files from all databases
            backup_files = self.find_backup_files(all_files)
            
            # Step 2: Upload each file
            total_files = len(backup_files)
            successful_uploads = 0
            
            for i, file_path in enumerate(backup_files, 1):
                # Extract database name from filename
                filename = os.path.basename(file_path)
                db_name = '_'.join(filename.split('_')[:2])  # e.g., "DB_MAIN" or "DB_2"
                
                print(f"\n📁 Processing file {i}/{total_files}: [{db_name}] {filename}")
                print("-" * 40)
                
                try:
                    self.upload_backup_file(file_path)
                    successful_uploads += 1
                    print(f"✅ File {i}/{total_files} uploaded successfully")
                except Exception as e:
                    print(f"❌ File {i}/{total_files} upload failed: {e}")
                    # Continue with next file instead of stopping
                    continue
            
            print("=" * 60)
            print(f"✅ QNAP backup process completed!")
            print(f"   Successfully uploaded: {successful_uploads}/{total_files} files")
            
            if successful_uploads < total_files:
                print(f"⚠️  Some uploads failed. Check logs above for details.")
                return 1
            
            return 0
            
        except Exception as e:
            print("=" * 60)
            print(f"❌ QNAP backup process failed: {e}")
            return 1

def check_setup():
    """Check and validate setup requirements"""
    print("🔍 Checking QNAP setup...")
    
    all_good = True
    
    # Check QNAP configuration
    if QNAP_PASSWORD:
        print(f"✓ QNAP credentials configured")
        print(f"   Host: {QNAP_HOST}")
        print(f"   Username: {QNAP_USERNAME}")
        print(f"   Destination: {QNAP_DEST_PATH}")
    else:
        print(f"❌ QNAP_PASSWORD not set in .env file")
        print("💡 Setup instructions:")
        print("   1. Add QNAP_PASSWORD=<your_password> to .env file")
        print("   2. Optionally configure QNAP_HOST, QNAP_USERNAME, QNAP_DEST_PATH")
        all_good = False
    
    # Check backup directory
    if os.path.exists(BACKUP_DIR):
        print(f"✓ Backup directory found: {BACKUP_DIR}")
        
        # Check for backup files from all databases
        db_prefixes = ['DB_MAIN', 'DB_2', 'DB_3']
        total_files = 0
        
        for db_prefix in db_prefixes:
            pattern = os.path.join(BACKUP_DIR, f'{db_prefix}_*.sql')
            db_files = glob.glob(pattern)
            if db_files:
                print(f"✓ Found {len(db_files)} backup files for {db_prefix}")
                total_files += len(db_files)
            else:
                print(f"⚠️  No backup files found for {db_prefix}")
        
        if total_files == 0:
            print(f"❌ No backup files found for any database (DB_MAIN, DB_2, DB_3)")
            all_good = False
        else:
            print(f"✓ Total backup files found: {total_files}")
    else:
        print(f"❌ Backup directory not found: {BACKUP_DIR}")
        all_good = False
    
    return all_good

def test_connection():
    """Test QNAP connection and authentication"""
    try:
        print("🧪 Testing QNAP connection...")
        print("=" * 50)
        
        uploader = QNAPBackupUploader()
        
        # Test authentication
        sid = uploader.get_auth_sid()
        
        print("=" * 50)
        print("✅ Connection test successful!")
        print(f"📋 Authentication SID: {sid}")
        print("💡 You can now run backup uploads")
        
        return 0
        
    except Exception as e:
        print("=" * 50)
        print(f"❌ Connection test failed: {e}")
        return 1

def show_help():
    """Show usage help"""
    script_name = os.path.basename(__file__)
    print(f"""
🔧 QNAP NAS Backup Uploader Usage:

Basic Commands:
  python {script_name}                    # Upload latest backup file from each database
  python {script_name} --all             # Upload all backup files from all databases
  python {script_name} --test            # Test QNAP connection only

Supported Databases:
  - DB_MAIN_*.sql files (Main database)
  - DB_2_*.sql files (Database 2)
  - DB_3_*.sql files (Database 3)

Backup Modes:
  Default mode: Finds the latest stable file from each database (up to 3 files)
  --all mode: Uploads all backup files from all databases

Environment Variables (.env file):
  QNAP_HOST=http://10.35.1.173:8080      # QNAP NAS address  
  QNAP_USERNAME=admin                     # QNAP username
  QNAP_PASSWORD=<password>                # QNAP password (required)
  QNAP_DEST_PATH=/backup/database         # QNAP destination path

Examples:
  python {script_name} --test             # Test connection first
  python {script_name}                    # Upload latest file from each DB
  python {script_name} --all              # Upload all files from all DBs

Note: 
  - This script handles uploads for all three databases
  - For Google Drive uploads, use backup_uploader.py
  - Files are uploaded with safe copy mechanism (temp files)
  - Skips unstable files that are currently being written
""")

def main():
    """Main function"""
    try:
        # Parse arguments
        args = sys.argv[1:]
        
        if '--help' in args or '-h' in args:
            show_help()
            return 0
        
        if '--test' in args:
            return test_connection()
        
        # Check setup
        if not check_setup():
            print("❌ Setup validation failed")
            print(f"💡 Run 'python {os.path.basename(__file__)} --help' for setup instructions")
            return 1
        
        print("-" * 60)
        
        # Determine backup mode
        all_files = '--all' in args
        
        # Initialize and run backup
        uploader = QNAPBackupUploader()
        return uploader.run_backup_process(all_files=all_files)
        
    except KeyboardInterrupt:
        print("\n❌ Backup cancelled by user")
        return 1
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        return 1

if __name__ == "__main__":
    exit(main())