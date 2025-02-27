from pathlib import Path
import pandas as pd

folder_path = Path("./python_script/usaha")
output_desa = Path("./python_script/level/desa/")
output_other = Path("./python_script/level/other/")
summary_file = Path("./python_script/level/summary.xlsx")

# Ensure output directories exist
output_desa.mkdir(parents=True, exist_ok=True)
output_other.mkdir(parents=True, exist_ok=True)

# List to store summary data
summary_data = []

for file_path in folder_path.iterdir():
    if file_path.suffix not in [".xls", ".xlsx"]:  # Ensure only Excel files are processed
        continue

    filename_prefix = file_path.stem[:4]  # First 4 characters of filename
    level1_filename = output_desa / f"{filename_prefix}_desa.xlsx"
    level2_filename = output_other / f"{filename_prefix}_other.xlsx"

    # Check if the result files exist and are larger than 100KB
    if (
        level1_filename.exists()
        and level1_filename.stat().st_size > 100 * 1024
        and level2_filename.exists()
        and level2_filename.stat().st_size > 100 * 1024
    ):
        print(f"Result files for {file_path.name} already exist and are larger than 100KB. Skipping processing.")

        # If summary file exists, load it and check if this file is already recorded
        if summary_file.exists():
            summary_df = pd.read_excel(summary_file)
            existing_summary = summary_df[summary_df["File Name"] == file_path.name]
            if not existing_summary.empty:
                summary_data.append(existing_summary.iloc[0].to_dict())
                continue  # Skip to the next file

        # If summary does not exist or this file is not yet recorded, read the result files
        level1_df = pd.read_excel(level1_filename, dtype=str)
        level2_df = pd.read_excel(level2_filename, dtype=str)

        # Append summary from existing result files
        summary_data.append(
            {
                "File Name": file_path.name,
                "Total Records": len(level1_df) + len(level2_df),
                "Level 1 Records": len(level1_df),
                "Level 2 Records": len(level2_df),
            }
        )
        continue  # Skip processing since files already exist

    # Process new files
    df = pd.read_excel(file_path, dtype=str)

    # Ensure columns are treated as strings and strip whitespace
    df["idsls"] = df["idsls"].fillna("").astype(str).str.strip()
    df["iddesa"] = df["iddesa"].fillna("").astype(str).str.strip()

    # Define Level 1 condition
    level1_condition = (df["idsls"] != "") | (df["iddesa"].str.len() == 10)

    # Split data
    level1_df = df[level1_condition]
    level2_df = df[~level1_condition]

    # Save to new files
    level1_df.to_excel(level1_filename, index=False)
    level2_df.to_excel(level2_filename, index=False)

    # Store summary data
    summary_data.append(
        {
            "File Name": file_path.name,
            "Total Records": len(df),
            "Level 1 Records": len(level1_df),
            "Level 2 Records": len(level2_df),
        }
    )

    print(f"Processed: {file_path.name} -> {len(level1_df)} Level 1, {len(level2_df)} Level 2")

# Save summary report
summary_df = pd.DataFrame(summary_data)
summary_df.to_excel(summary_file, index=False)

print(f"Summary saved to {summary_file}")
