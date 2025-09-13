#!/usr/bin/env python3
"""
Database Backup Uploader to Google Drive

This script uploads the latest database backup file to Google Drive
and maintains only a specified number of backup files to save space.

Requirements:
    - google-api-python-client
    - google-auth
    - python-dotenv

Install with: pip install google-api-python-client google-auth python-dotenv
"""

import os
import glob
import json
from datetime import datetime
from dotenv import load_dotenv
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload
from google.auth.transport.requests import Request
from google.oauth2.service_account import Credentials

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION
# =====================================================================

# Number of backup files to retain in Google Drive
MAX_BACKUP_FILES = 2

# Backup directory relative to script location
BACKUP_DIR = os.path.join(os.path.dirname(__file__), '..', 'backup')

# Environment variables for Google Drive
GOOGLE_DRIVE_FOLDER_ID = os.getenv('GOOGLE_DRIVE_BACKUP_FOLDER_ID')
GOOGLE_SERVICE_ACCOUNT_KEY = os.getenv('GOOGLE_SERVICE_ACCOUNT_KEY')  # JSON string or file path

# Google Drive API scope
SCOPES = ['https://www.googleapis.com/auth/drive']

class GoogleDriveBackupManager:
    """Manages database backup uploads to Google Drive"""
    
    def __init__(self):
        self.service = None
        self.folder_id = GOOGLE_DRIVE_FOLDER_ID
        self.setup_drive_service()
    
    def setup_drive_service(self):
        """Initialize Google Drive API service"""
        try:
            # Handle service account credentials
            if not GOOGLE_SERVICE_ACCOUNT_KEY:
                raise ValueError("GOOGLE_SERVICE_ACCOUNT_KEY not found in environment variables")
            
            # Check if it's a file path or JSON string
            if os.path.isfile(GOOGLE_SERVICE_ACCOUNT_KEY):
                # It's a file path
                credentials = Credentials.from_service_account_file(
                    GOOGLE_SERVICE_ACCOUNT_KEY, 
                    scopes=SCOPES
                )
            else:
                # It's a JSON string
                service_account_info = json.loads(GOOGLE_SERVICE_ACCOUNT_KEY)
                credentials = Credentials.from_service_account_info(
                    service_account_info, 
                    scopes=SCOPES
                )
            
            # Build the service
            self.service = build('drive', 'v3', credentials=credentials)
            print("✓ Google Drive API service initialized successfully")
            
        except Exception as e:
            print(f"✗ Error setting up Google Drive service: {e}")
            raise
    
    def find_latest_backup_file(self):
        """Find the latest backup file in the backup directory"""
        try:
            # Pattern to match backup files: DB_MAIN_{date}.sql
            pattern = os.path.join(BACKUP_DIR, 'DB_MAIN_*.sql')
            backup_files = glob.glob(pattern)
            
            if not backup_files:
                raise FileNotFoundError("No backup files found matching pattern DB_MAIN_*.sql")
            
            # Sort by modification time (newest first)
            backup_files.sort(key=os.path.getmtime, reverse=True)
            latest_file = backup_files[0]
            
            print(f"✓ Found latest backup file: {os.path.basename(latest_file)}")
            return latest_file
            
        except Exception as e:
            print(f"✗ Error finding backup file: {e}")
            raise
    
    def upload_backup_file(self, file_path):
        """Upload backup file to Google Drive"""
        try:
            file_name = os.path.basename(file_path)
            file_size = os.path.getsize(file_path)
            
            print(f"📤 Uploading {file_name} ({self.format_file_size(file_size)})...")
            
            # File metadata
            file_metadata = {
                'name': file_name,
                'parents': [self.folder_id] if self.folder_id else None
            }
            
            # Media upload
            media = MediaFileUpload(
                file_path,
                mimetype='application/sql',
                resumable=True
            )
            
            # Execute upload
            file = self.service.files().create(
                body=file_metadata,
                media_body=media,
                fields='id,name,size,createdTime'
            ).execute()
            
            print(f"✓ Upload successful!")
            print(f"  - File ID: {file.get('id')}")
            print(f"  - Name: {file.get('name')}")
            print(f"  - Size: {self.format_file_size(int(file.get('size', 0)))}")
            print(f"  - Created: {file.get('createdTime')}")
            
            return file.get('id')
            
        except Exception as e:
            print(f"✗ Error uploading file: {e}")
            raise
    
    def get_backup_files_in_drive(self):
        """Get list of backup files in Google Drive folder"""
        try:
            # Query to find backup files in the folder
            query = f"'{self.folder_id}' in parents and name contains 'DB_MAIN_' and name contains '.sql' and trashed=false"
            
            results = self.service.files().list(
                q=query,
                fields="files(id,name,size,createdTime)",
                orderBy="createdTime desc"
            ).execute()
            
            files = results.get('files', [])
            print(f"📁 Found {len(files)} backup files in Google Drive")
            
            return files
            
        except Exception as e:
            print(f"✗ Error listing files in Google Drive: {e}")
            raise
    
    def cleanup_old_backups(self):
        """Remove old backup files to maintain only MAX_BACKUP_FILES"""
        try:
            files = self.get_backup_files_in_drive()
            
            if len(files) <= MAX_BACKUP_FILES:
                print(f"✓ No cleanup needed. Current files: {len(files)}, Max allowed: {MAX_BACKUP_FILES}")
                return
            
            # Files to delete (keep only the newest MAX_BACKUP_FILES)
            files_to_delete = files[MAX_BACKUP_FILES:]
            
            print(f"🗑️  Deleting {len(files_to_delete)} old backup files...")
            
            for file in files_to_delete:
                try:
                    self.service.files().delete(fileId=file['id']).execute()
                    print(f"  ✓ Deleted: {file['name']} ({self.format_file_size(int(file.get('size', 0)))})")
                except Exception as e:
                    print(f"  ✗ Failed to delete {file['name']}: {e}")
            
            print(f"✓ Cleanup complete. Retained {MAX_BACKUP_FILES} backup files")
            
        except Exception as e:
            print(f"✗ Error during cleanup: {e}")
            raise
    
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
    
    def run_backup_process(self):
        """Execute the complete backup upload and cleanup process"""
        try:
            print("🚀 Starting database backup upload process...")
            print(f"   Max backup files to retain: {MAX_BACKUP_FILES}")
            print(f"   Target folder ID: {self.folder_id}")
            print("-" * 60)
            
            # Step 1: Find latest backup file
            latest_backup = self.find_latest_backup_file()
            
            # Step 2: Upload to Google Drive
            file_id = self.upload_backup_file(latest_backup)
            
            # Step 3: Cleanup old backups
            self.cleanup_old_backups()
            
            print("-" * 60)
            print("✅ Backup process completed successfully!")
            
        except Exception as e:
            print("-" * 60)
            print(f"❌ Backup process failed: {e}")
            raise

def main():
    """Main function"""
    try:
        # Validate environment variables
        if not GOOGLE_DRIVE_FOLDER_ID:
            raise ValueError("GOOGLE_DRIVE_BACKUP_FOLDER_ID not found in environment variables")
        
        # Initialize and run backup manager
        backup_manager = GoogleDriveBackupManager()
        backup_manager.run_backup_process()
        
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        return 1
    
    return 0

if __name__ == "__main__":
    exit(main())