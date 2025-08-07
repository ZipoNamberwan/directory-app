import os
import shutil
import argparse
from pathlib import Path

def move_files_to_folders(source_directory, n_chars):
    """
    Move files to folders based on the first n characters of their filenames.
    
    Args:
        source_directory (str): Path to the directory containing files to organize
        n_chars (int): Number of characters from the beginning of filename to use as folder name
    """
    # Convert to Path object for easier handling
    source_path = Path(source_directory)
    
    if not source_path.exists():
        print(f"Error: Directory '{source_directory}' does not exist.")
        return
    
    if not source_path.is_dir():
        print(f"Error: '{source_directory}' is not a directory.")
        return
    
    # Dictionary to track folders and their files
    folder_files = {}
    
    # Get all files in the source directory (not subdirectories)
    files = [f for f in source_path.iterdir() if f.is_file()]
    
    if not files:
        print(f"No files found in '{source_directory}'.")
        return
    
    # Group files by their first n characters
    for file_path in files:
        filename = file_path.name
        
        # Get the first n characters for folder name
        if len(filename) >= n_chars:
            folder_name = filename[:n_chars]
        else:
            folder_name = filename  # Use full filename if shorter than n_chars
        
        if folder_name not in folder_files:
            folder_files[folder_name] = []
        folder_files[folder_name].append(file_path)
    
    # Create folders and move files
    for folder_name, file_list in folder_files.items():
        # Create the destination folder
        dest_folder = source_path / folder_name
        dest_folder.mkdir(exist_ok=True)
        
        print(f"\nCreated/Using folder: {dest_folder}")
        
        # Move files to the folder
        for file_path in file_list:
            dest_file = dest_folder / file_path.name
            
            # Check if file already exists in destination
            if dest_file.exists():
                print(f"  Warning: File '{file_path.name}' already exists in '{folder_name}' folder. Skipping.")
                continue
            
            try:
                shutil.move(str(file_path), str(dest_file))
                print(f"  Moved: {file_path.name} -> {folder_name}/")
            except Exception as e:
                print(f"  Error moving {file_path.name}: {e}")
    
    print(f"\nFile organization complete!")
    print(f"Files grouped into {len(folder_files)} folders based on first {n_chars} characters.")

def main():
    parser = argparse.ArgumentParser(
        description="Move files to folders based on first n characters of filename",
        epilog="""
Examples:
  python move_to_folder.py /path/to/files 4
  python move_to_folder.py . 3
  python move_to_folder.py "C:\\Users\\Documents\\files" 2
        """,
        formatter_class=argparse.RawDescriptionHelpFormatter
    )
    
    parser.add_argument(
        "directory", 
        help="Path to the directory containing files to organize"
    )
    
    parser.add_argument(
        "n_chars", 
        type=int,
        help="Number of characters from the beginning of filename to use as folder name"
    )
    
    parser.add_argument(
        "--dry-run", 
        action="store_true",
        help="Show what would be done without actually moving files"
    )
    
    args = parser.parse_args()
    
    if args.n_chars <= 0:
        print("Error: n_chars must be a positive integer.")
        return
    
    if args.dry_run:
        print("DRY RUN MODE - No files will be moved")
        dry_run_preview(args.directory, args.n_chars)
    else:
        move_files_to_folders(args.directory, args.n_chars)

def dry_run_preview(source_directory, n_chars):
    """
    Preview what folders would be created and which files would be moved.
    """
    source_path = Path(source_directory)
    
    if not source_path.exists():
        print(f"Error: Directory '{source_directory}' does not exist.")
        return
    
    files = [f for f in source_path.iterdir() if f.is_file()]
    
    if not files:
        print(f"No files found in '{source_directory}'.")
        return
    
    folder_files = {}
    
    for file_path in files:
        filename = file_path.name
        folder_name = filename[:n_chars] if len(filename) >= n_chars else filename
        
        if folder_name not in folder_files:
            folder_files[folder_name] = []
        folder_files[folder_name].append(filename)
    
    print(f"Preview: Files would be organized into {len(folder_files)} folders:")
    
    for folder_name, file_list in folder_files.items():
        print(f"\nFolder '{folder_name}' would contain {len(file_list)} files:")
        for filename in file_list:
            print(f"  - {filename}")

if __name__ == "__main__":
    # If run without command line arguments, provide interactive mode
    if len(os.sys.argv) == 1:
        print("Move Files to Folders Script")
        print("=" * 30)
        
        source_dir = input("Enter the directory path containing files to organize: ").strip()
        if not source_dir:
            source_dir = "."  # Current directory
        
        try:
            n_chars = int(input("Enter number of characters for folder names: ").strip())
        except ValueError:
            print("Error: Please enter a valid number.")
            exit(1)
        
        dry_run = input("Do you want to preview first? (y/n): ").strip().lower()
        
        if dry_run in ['y', 'yes']:
            dry_run_preview(source_dir, n_chars)
            
            confirm = input("\nProceed with moving files? (y/n): ").strip().lower()
            if confirm in ['y', 'yes']:
                move_files_to_folders(source_dir, n_chars)
            else:
                print("Operation cancelled.")
        else:
            move_files_to_folders(source_dir, n_chars)
    else:
        main()
