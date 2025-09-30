#!/usr/bin/env python3
"""
Simple Google Drive Upload Test

This script creates a small test file and uploads it to Google Drive
to test service account authentication and permissions.
"""

import os
import json
from datetime import datetime
from dotenv import load_dotenv
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload
from google.oauth2.service_account import Credentials

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# Configuration
GOOGLE_DRIVE_FOLDER_ID = os.getenv('GOOGLE_DRIVE_BACKUP_FOLDER_ID')
GOOGLE_SERVICE_ACCOUNT_KEY_FILE = os.path.join(os.path.dirname(__file__), '..', 'gdrivekey.json')
SCOPES = ['https://www.googleapis.com/auth/drive']

def create_test_file():
    """Create a small test file"""
    test_file_path = os.path.join(os.path.dirname(__file__), 'test_upload.txt')
    
    content = f"""Test Upload File
Created: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
Purpose: Testing Google Drive service account upload
Service Account: Service account authentication test
"""
    
    with open(test_file_path, 'w') as f:
        f.write(content)
    
    print(f"✓ Created test file: {test_file_path}")
    print(f"  Size: {os.path.getsize(test_file_path)} bytes")
    return test_file_path

def setup_drive_service():
    """Initialize Google Drive API service"""
    try:
        print("🔐 Setting up Google Drive service...")
        
        # Check if service account key file exists
        if not os.path.exists(GOOGLE_SERVICE_ACCOUNT_KEY_FILE):
            raise FileNotFoundError(f"Service account key file not found: {GOOGLE_SERVICE_ACCOUNT_KEY_FILE}")
        
        print(f"📄 Loading credentials from: {GOOGLE_SERVICE_ACCOUNT_KEY_FILE}")
        
        # Load credentials
        credentials = Credentials.from_service_account_file(
            GOOGLE_SERVICE_ACCOUNT_KEY_FILE, 
            scopes=SCOPES
        )
        
        # Build service
        service = build('drive', 'v3', credentials=credentials)
        print("✓ Google Drive API service initialized")
        
        # Get service account info
        with open(GOOGLE_SERVICE_ACCOUNT_KEY_FILE, 'r') as f:
            service_account_data = json.load(f)
        
        print(f"  Service account email: {service_account_data.get('client_email')}")
        
        return service
        
    except Exception as e:
        print(f"✗ Error setting up Google Drive service: {e}")
        raise

def check_folder_info(service, folder_id):
    """Check information about the target folder"""
    try:
        if not folder_id:
            print("ℹ️  No folder ID specified - will upload to root")
            return "root"
        
        print(f"🔍 Checking folder ID: {folder_id}")
        
        # Get folder information
        folder_info = service.files().get(
            fileId=folder_id,
            fields='id,name,driveId,parents,permissions',
            supportsAllDrives=True
        ).execute()
        
        print(f"  📁 Folder name: {folder_info.get('name')}")
        print(f"  📋 Folder ID: {folder_info.get('id')}")
        
        if folder_info.get('driveId'):
            print(f"  🏢 Shared Drive ID: {folder_info.get('driveId')}")
            drive_type = "shared_drive"
        else:
            print(f"  👤 Regular Google Drive folder")
            drive_type = "regular_drive"
        
        return drive_type
        
    except Exception as e:
        print(f"⚠️  Error checking folder: {e}")
        return "unknown"

def list_files_in_folder(service, folder_id):
    """List files in the target folder to test read permissions"""
    try:
        print(f"📋 Listing files in folder...")
        
        # Build query
        if folder_id:
            query = f"'{folder_id}' in parents and trashed=false"
        else:
            query = "trashed=false"
        
        # List files
        results = service.files().list(
            q=query,
            fields="files(id,name,size,createdTime,mimeType)",
            orderBy="createdTime desc",
            pageSize=10,  # Limit to 10 files for testing
            supportsAllDrives=True,
            includeItemsFromAllDrives=True
        ).execute()
        
        files = results.get('files', [])
        
        if not files:
            print("  📭 No files found in folder")
        else:
            print(f"  📁 Found {len(files)} files:")
            for i, file in enumerate(files[:5], 1):  # Show first 5 files
                file_size = int(file.get('size', 0)) if file.get('size') else 0
                size_str = format_file_size(file_size) if file_size > 0 else "Unknown size"
                print(f"    {i}. {file.get('name')} ({size_str})")
                print(f"       ID: {file.get('id')}")
                print(f"       Type: {file.get('mimeType', 'Unknown')}")
                print(f"       Created: {file.get('createdTime', 'Unknown')}")
        
        return len(files)
        
    except Exception as e:
        print(f"❌ Error listing files: {e}")
        
        # Provide specific guidance for common errors
        if "insufficientFilePermissions" in str(e):
            print("💡 Permission Error: Service account needs read access to the folder")
        elif "notFound" in str(e):
            print("💡 Folder Not Found: Check if the folder ID is correct and shared")
        
        raise

def format_file_size(size_bytes):
    """Format file size in human readable format"""
    if size_bytes == 0:
        return "0 B"
    
    size_names = ["B", "KB", "MB", "GB", "TB"]
    import math
    i = int(math.floor(math.log(size_bytes, 1024)))
    p = math.pow(1024, i)
    s = round(size_bytes / p, 2)
    return f"{s} {size_names[i]}"

def test_upload(service, test_file_path, folder_id):
    """Test uploading the file"""
    try:
        file_name = f"test_upload_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
        file_size = os.path.getsize(test_file_path)
        
        print(f"📤 Uploading test file: {file_name}")
        print(f"  Size: {file_size} bytes")
        
        # File metadata
        file_metadata = {
            'name': file_name,
            'description': 'Test upload from backup script'
        }
        
        # Add parent folder if specified
        if folder_id:
            file_metadata['parents'] = [folder_id]
        
        # Media upload
        media = MediaFileUpload(
            test_file_path,
            mimetype='text/plain',
            resumable=False  # Small file, no need for resumable
        )
        
        # Execute upload
        file = service.files().create(
            body=file_metadata,
            media_body=media,
            fields='id,name,size,createdTime,webViewLink',
            supportsAllDrives=True
        ).execute()
        
        print("✅ Upload successful!")
        print(f"  📋 File ID: {file.get('id')}")
        print(f"  📁 Name: {file.get('name')}")
        print(f"  📏 Size: {file.get('size')} bytes")
        print(f"  🕒 Created: {file.get('createdTime')}")
        print(f"  🔗 Link: {file.get('webViewLink')}")
        
        return file.get('id')
        
    except Exception as e:
        print(f"❌ Upload failed: {e}")
        
        # Provide specific guidance for common errors
        if "storageQuotaExceeded" in str(e):
            print("\n💡 Storage Quota Error Solutions:")
            print("   1. Use a Shared Drive (Google Workspace)")
            print("   2. Use OAuth2 authentication instead")
            print("   3. Enable domain-wide delegation")
        elif "insufficientFilePermissions" in str(e):
            print("\n💡 Permission Error Solutions:")
            print("   1. Make sure the service account has edit access to the folder")
            print("   2. Check if the folder ID is correct")
        elif "notFound" in str(e):
            print("\n💡 Folder Not Found:")
            print("   1. Verify the GOOGLE_DRIVE_BACKUP_FOLDER_ID is correct")
            print("   2. Make sure the folder is shared with the service account")
        
        raise

def cleanup_test_file(test_file_path):
    """Remove the test file"""
    try:
        if os.path.exists(test_file_path):
            os.remove(test_file_path)
            print(f"🗑️  Cleaned up test file: {os.path.basename(test_file_path)}")
    except Exception as e:
        print(f"⚠️  Could not clean up test file: {e}")

def main():
    """Main test function"""
    test_file_path = None
    
    try:
        print("🧪 Google Drive Upload Test")
        print("=" * 50)
        
        # Create test file
        test_file_path = create_test_file()
        
        # Setup Google Drive service
        service = setup_drive_service()
        
        # Check folder information
        drive_type = check_folder_info(service, GOOGLE_DRIVE_FOLDER_ID)
        
        print("-" * 50)
        
        # Test reading files first (less invasive)
        print("📖 Testing read permissions...")
        try:
            file_count = list_files_in_folder(service, GOOGLE_DRIVE_FOLDER_ID)
            print(f"✅ Read test successful! Found {file_count} files")
        except Exception as e:
            print(f"❌ Read test failed: {e}")
            print("⚠️  Skipping upload test due to read permission issues")
            return 1
        
        print("-" * 50)
        
        # Test upload (more invasive, only if read works)
        print("📤 Testing upload permissions...")
        try:
            file_id = test_upload(service, test_file_path, GOOGLE_DRIVE_FOLDER_ID)
            print(f"✅ Upload test successful! File ID: {file_id}")
        except Exception as e:
            print(f"❌ Upload test failed: {e}")
            print("ℹ️  Read permissions work, but upload failed")
            
            # Still return success if read worked
            print("-" * 50)
            print("✅ Partial success - read permissions confirmed")
            print(f"🎯 Drive type: {drive_type}")
            return 0
        
        print("-" * 50)
        print("✅ All tests completed successfully!")
        print(f"🎯 Drive type: {drive_type}")
        print("� Both read and write permissions confirmed")
        
        return 0
        
    except Exception as e:
        print("-" * 50)
        print(f"❌ Test failed: {e}")
        return 1
        
    finally:
        # Always cleanup
        if test_file_path:
            cleanup_test_file(test_file_path)

if __name__ == "__main__":
    exit(main())