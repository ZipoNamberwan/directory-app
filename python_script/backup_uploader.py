#!/usr/bin/env python3
"""
Database Backup Uploader to Google Drive

This script uploads the latest database backup file to Google Drive using OAuth2 authentication
and maintains only a specified number of backup files to save space.

Requirements:
    - google-api-python-client
    - google-auth
    - google-auth-oauthlib
    - python-dotenv

Install with: pip install google-api-python-client google-auth google-auth-oauthlib python-dotenv
"""

import os
import glob
import json
import time
import shutil
import sys
from datetime import datetime
from dotenv import load_dotenv
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload
from google.auth.transport.requests import Request
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION
# =====================================================================

# Number of backup files to retain in Google Drive
MAX_BACKUP_FILES = 3  # Keep three latest backups

# Backup directory relative to script location
BACKUP_DIR = os.path.join(os.path.dirname(__file__), '..', 'backup')

# Temporary directory for safe copying (within backup folder)
TEMP_DIR = os.path.join(BACKUP_DIR, 'temp')

# File stability check - wait time to ensure file is not being written
FILE_STABILITY_WAIT = 30  # seconds

# Minimum time between uploads of same file (to avoid duplicate uploads)
MIN_UPLOAD_INTERVAL = 21600  # 6 hours in seconds

# Environment variables for Google Drive
GOOGLE_DRIVE_FOLDER_ID = os.getenv('GOOGLE_DRIVE_BACKUP_FOLDER_ID')

# OAuth2 authentication files
GOOGLE_CREDENTIALS_FILE = os.path.join(os.path.dirname(__file__), '..', 'gdrivecredentials.json')
GOOGLE_TOKEN_FILE = os.path.join(os.path.dirname(__file__), '..', 'token.json')

# Google Drive API scope
SCOPES = ['https://www.googleapis.com/auth/drive']

class GoogleDriveBackupManager:
    """Manages database backup uploads to Google Drive"""
    
    def __init__(self):
        self.service = None
        self.folder_id = GOOGLE_DRIVE_FOLDER_ID
        self.setup_drive_service()
    
    def setup_drive_service(self):
        """Initialize Google Drive API service using OAuth2 authentication"""
        try:
            print("🔐 Using OAuth2 authentication")
            
            creds = None
            
            # Load existing token if available
            if os.path.exists(GOOGLE_TOKEN_FILE):
                print(f"📄 Loading existing token from: {GOOGLE_TOKEN_FILE}")
                creds = Credentials.from_authorized_user_file(GOOGLE_TOKEN_FILE, SCOPES)
            
            # If there are no (valid) credentials available, let the user log in
            if not creds or not creds.valid:
                if creds and creds.expired and creds.refresh_token:
                    print("🔄 Refreshing expired token...")
                    try:
                        creds.refresh(Request())
                        print("✓ Token refreshed successfully")
                    except Exception as e:
                        print(f"⚠️  Token refresh failed: {e}")
                        print("🔑 Need to re-authenticate...")
                        creds = None
                
                if not creds:
                    # Check if credentials file exists
                    if not os.path.exists(GOOGLE_CREDENTIALS_FILE):
                        raise FileNotFoundError(f"OAuth2 credentials file not found: {GOOGLE_CREDENTIALS_FILE}")
                    
                    print(f"� Starting OAuth2 authentication flow...")
                    print(f"📄 Using credentials from: {GOOGLE_CREDENTIALS_FILE}")
                    
                    flow = InstalledAppFlow.from_client_secrets_file(
                        GOOGLE_CREDENTIALS_FILE, SCOPES
                    )
                    
                    # Run local server for OAuth flow
                    creds = flow.run_local_server(port=0)
                    print("✓ Authentication successful!")
                
                # Save the credentials for the next run
                print(f"💾 Saving token to: {GOOGLE_TOKEN_FILE}")
                with open(GOOGLE_TOKEN_FILE, 'w') as token:
                    token.write(creds.to_json())
                print("✓ Token saved successfully")
            else:
                print("✓ Using existing valid token")
            
            # Build the service
            self.service = build('drive', 'v3', credentials=creds)
            print("✓ Google Drive API service initialized successfully")
            
        except Exception as e:
            print(f"✗ Error setting up Google Drive service: {e}")
            print("💡 Setup instructions:")
            print("   1. Go to Google Cloud Console (https://console.cloud.google.com/)")
            print("   2. Create/select project → Enable Google Drive API")
            print("   3. Create OAuth2 credentials (Desktop application)")
            print(f"   4. Download credentials as {GOOGLE_CREDENTIALS_FILE}")
            print("   5. Run this script to complete authentication")
            raise
    
    def find_latest_backup_file(self):
        """Find the latest backup file and ensure it's stable (not being written)"""
        try:
            # Pattern to match backup files: DB_MAIN_{date}.sql
            pattern = os.path.join(BACKUP_DIR, 'DB_MAIN_*.sql')
            backup_files = glob.glob(pattern)
            
            if not backup_files:
                raise FileNotFoundError("No backup files found matching pattern DB_MAIN_*.sql")
            
            # Sort by modification time (newest first)
            backup_files.sort(key=os.path.getmtime, reverse=True)
            latest_file = backup_files[0]
            
            # Check if file is stable (not being modified)
            if not self.is_file_stable(latest_file):
                print(f"⚠️  File {os.path.basename(latest_file)} appears to be in use, waiting...")
                time.sleep(FILE_STABILITY_WAIT)
                
                # Check again after waiting
                if not self.is_file_stable(latest_file):
                    raise Exception(f"File {latest_file} is still being modified, aborting upload")
            
            print(f"✓ Found stable backup file: {os.path.basename(latest_file)}")
            return latest_file
            
        except Exception as e:
            print(f"✗ Error finding backup file: {e}")
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
    
    def create_safe_copy(self, source_file):
        """Create a safe copy of the backup file to avoid upload conflicts"""
        try:
            # Ensure temp directory exists
            os.makedirs(TEMP_DIR, exist_ok=True)
            
            # Create temp file name with timestamp
            filename = os.path.basename(source_file)
            name, ext = os.path.splitext(filename)
            timestamp = datetime.now().strftime("%H%M%S")
            temp_filename = f"{name}_upload_{timestamp}{ext}"
            temp_file = os.path.join(TEMP_DIR, temp_filename)
            
            print(f"📋 Creating safe copy: {temp_filename}")
            
            # Copy file
            shutil.copy2(source_file, temp_file)
            
            # Verify copy
            if os.path.getsize(source_file) != os.path.getsize(temp_file):
                raise Exception("File copy size mismatch")
            
            print(f"✓ Safe copy created: {self.format_file_size(os.path.getsize(temp_file))}")
            return temp_file
            
        except Exception as e:
            print(f"✗ Error creating safe copy: {e}")
            raise
    
    def cleanup_temp_file(self, temp_file):
        """Clean up temporary file after upload"""
        try:
            if os.path.exists(temp_file):
                os.remove(temp_file)
                print(f"🗑️  Cleaned up temp file: {os.path.basename(temp_file)}")
        except Exception as e:
            print(f"⚠️  Warning: Could not clean up temp file {temp_file}: {e}")
    
    def should_upload_file(self, file_path):
        """Check if file should be uploaded based on last upload time"""
        try:
            filename = os.path.basename(file_path)
            
            # Check if we've uploaded this file recently
            files = self.get_backup_files_in_drive()
            
            for drive_file in files:
                if drive_file['name'] == filename:
                    # Parse creation time
                    created_time = datetime.fromisoformat(drive_file['createdTime'].replace('Z', '+00:00'))
                    current_time = datetime.now(created_time.tzinfo)
                    time_diff = (current_time - created_time).total_seconds()
                    
                    if time_diff < MIN_UPLOAD_INTERVAL:
                        print(f"⏱️  File {filename} was uploaded {time_diff/3600:.1f} hours ago, skipping")
                        return False
            
            return True
            
        except Exception as e:
            print(f"⚠️  Warning: Could not check upload history: {e}")
            return True  # Upload anyway if we can't determine
    
    def upload_backup_file(self, file_path):
        """Upload backup file to Google Drive using safe copy approach"""
        temp_file = None
        try:
            # Check if we should upload this file
            if not self.should_upload_file(file_path):
                return None
            
            # Create safe copy
            temp_file = self.create_safe_copy(file_path)
            
            file_name = os.path.basename(file_path)  # Use original filename
            file_size = os.path.getsize(temp_file)
            
            print(f"📤 Uploading {file_name} ({self.format_file_size(file_size)})...")
            
            # File metadata
            file_metadata = {
                'name': file_name,
                'parents': [self.folder_id] if self.folder_id else None
            }
            
            # Media upload
            media = MediaFileUpload(
                temp_file,
                mimetype='application/sql',
                resumable=True
            )
            
            # Execute upload
            file = self.service.files().create(
                body=file_metadata,
                media_body=media,
                fields='id,name,size,createdTime',
                supportsAllDrives=True
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
        finally:
            # Always cleanup temp file
            if temp_file:
                self.cleanup_temp_file(temp_file)

    def get_backup_files_in_drive(self, name_filter=None):
        """Get list of backup files in Google Drive folder"""
        try:
            # Query to find backup files in the folder
            if self.folder_id:
                query = f"'{self.folder_id}' in parents and name contains 'DB_MAIN_' and name contains '.sql' and trashed=false"
            else:
                query = "name contains 'DB_MAIN_' and name contains '.sql' and trashed=false"
            
            if name_filter:
                query += f" and name = '{name_filter}'"
            
            results = self.service.files().list(
                q=query,
                fields="files(id,name,size,createdTime)",
                orderBy="createdTime desc",
                supportsAllDrives=True,
                includeItemsFromAllDrives=True
            ).execute()
            
            files = results.get('files', [])
            if not name_filter:
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
                    self.service.files().delete(
                        fileId=file['id'],
                        supportsAllDrives=True
                    ).execute()
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
            print(f"   Authentication: OAuth2")
            print(f"   Max backup files to retain: {MAX_BACKUP_FILES}")
            print(f"   File stability wait time: {FILE_STABILITY_WAIT} seconds")
            print(f"   Minimum upload interval: {MIN_UPLOAD_INTERVAL/3600:.1f} hours")
            print(f"   Target folder ID: {self.folder_id or 'Root folder'}")
            print("-" * 60)
            
            # Step 1: Find latest backup file
            latest_backup = self.find_latest_backup_file()
            
            # Step 2: Upload to Google Drive (may skip if recently uploaded)
            file_id = self.upload_backup_file(latest_backup)
            
            if file_id:
                print("✓ New backup uploaded successfully")
            else:
                print("ℹ️  No upload needed (file recently uploaded)")
            
            # Step 3: Cleanup old backups
            self.cleanup_old_backups()
            
            print("-" * 60)
            print("✅ Backup process completed successfully!")
            
        except Exception as e:
            print("-" * 60)
            print(f"❌ Backup process failed: {e}")
            raise

def check_setup():
    """Check and validate setup requirements"""
    print("🔍 Checking setup...")
    
    # Check GOOGLE_DRIVE_FOLDER_ID (optional)
    if not GOOGLE_DRIVE_FOLDER_ID:
        print("ℹ️  GOOGLE_DRIVE_BACKUP_FOLDER_ID not set - will upload to root folder")
    else:
        print(f"✓ GOOGLE_DRIVE_BACKUP_FOLDER_ID: {GOOGLE_DRIVE_FOLDER_ID}")
    
    return check_oauth2_setup()

def check_oauth2_setup():
    """Check OAuth2 setup"""
    # Check if credentials file exists
    if not os.path.exists(GOOGLE_CREDENTIALS_FILE):
        print(f"❌ OAuth2 credentials file not found: {GOOGLE_CREDENTIALS_FILE}")
        print("💡 Setup instructions:")
        print("   1. Go to Google Cloud Console (https://console.cloud.google.com/)")
        print("   2. Create/select project → Enable Google Drive API")
        print("   3. Create OAuth2 credentials (Desktop application)")
        print(f"   4. Download credentials as {GOOGLE_CREDENTIALS_FILE}")
        print("   5. Run this script to complete authentication")
        return False
    else:
        file_size = os.path.getsize(GOOGLE_CREDENTIALS_FILE)
        print(f"✓ OAuth2 credentials file found: {GOOGLE_CREDENTIALS_FILE} ({file_size} bytes)")
        
        # Basic validation
        try:
            with open(GOOGLE_CREDENTIALS_FILE, 'r') as f:
                credentials_data = json.load(f)
            
            # Check if it's the right type of credentials
            if 'installed' not in credentials_data and 'web' not in credentials_data:
                print(f"❌ Invalid OAuth2 credentials file format")
                return False
            
            client_data = credentials_data.get('installed') or credentials_data.get('web')
            required_fields = ['client_id', 'client_secret']
            missing_fields = [field for field in required_fields if field not in client_data]
            
            if missing_fields:
                print(f"❌ OAuth2 credentials missing required fields: {missing_fields}")
                return False
            
            print("✓ OAuth2 credentials file appears to be valid")
            print(f"   Client ID: {client_data.get('client_id')}")
            
        except json.JSONDecodeError as e:
            print(f"❌ OAuth2 credentials file contains invalid JSON: {e}")
            return False
        except Exception as e:
            print(f"❌ Error reading OAuth2 credentials file: {e}")
            return False
    
    # Check token file
    if os.path.exists(GOOGLE_TOKEN_FILE):
        print(f"✓ Token file found: {GOOGLE_TOKEN_FILE}")
        print("   (Will use existing authentication)")
    else:
        print(f"ℹ️  No token file found: {GOOGLE_TOKEN_FILE}")
        print("   (Will need to authenticate via browser)")
    
    return True

def login_only():
    """Perform OAuth2 login and save token without running backup"""
    try:
        print("🔑 OAuth2 Login Setup")
        print("=" * 50)
        
        # Check credentials file
        if not check_oauth2_setup():
            print("❌ OAuth2 setup validation failed")
            return 1
        
        print("-" * 50)
        
        # Initialize backup manager (this will trigger authentication)
        print("🔐 Initializing authentication...")
        backup_manager = GoogleDriveBackupManager()
        
        print("-" * 50)
        print("✅ Login completed successfully!")
        print(f"📄 Token saved to: {GOOGLE_TOKEN_FILE}")
        print("💡 You can now run the backup script without browser authentication")
        
        return 0
        
    except Exception as e:
        print("-" * 50)
        print(f"❌ Login failed: {e}")
        return 1

def main():
    """Main function"""
    try:
        # Check if this is a login-only run
        if len(sys.argv) > 1 and sys.argv[1] == '--login':
            return login_only()
        
        # Check setup first
        if not check_setup():
            print("❌ Setup validation failed")
            print("💡 Run with --login flag to set up authentication first:")
            print(f"   python {os.path.basename(__file__)} --login")
            return 1
        
        print("-" * 60)
        
        # Initialize and run backup manager
        backup_manager = GoogleDriveBackupManager()
        backup_manager.run_backup_process()
        
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        return 1
    
    return 0

if __name__ == "__main__":
    exit(main())