import pandas as pd
import os

file_path = "./python_script/market/alokasi pasar.xlsx"
xls = pd.ExcelFile(file_path)

# Prepare a list to hold data from all sheets
combined_data = []

for sheet_name in xls.sheet_names:
    try:
        # Skip merged header rows
        df = pd.read_excel(file_path, sheet_name=sheet_name, skiprows=2)
        df.columns = df.columns.str.strip()

        # Normalize and match column names
        col_map = {col.lower(): col for col in df.columns}
        if "nama pasar" in col_map and "iddesa" in col_map:
            df_extracted = df[[col_map["nama pasar"], col_map["iddesa"]]].copy()

            df_extracted.columns = ["Nama Pasar", "iddesa"]

            # Clean and convert to string
            df_extracted["Nama Pasar"] = df_extracted["Nama Pasar"].astype(str).str.strip()
            df_extracted["iddesa"] = pd.to_numeric(df_extracted["iddesa"], errors="coerce", downcast=None)
            df_extracted = df_extracted.dropna(subset=["iddesa"])
            df_extracted["iddesa"] = df_extracted["iddesa"].astype("Int64").astype(str).str.zfill(10)

            # Filter out rows that are empty or invalid
            df_valid = df_extracted[
                (df_extracted["Nama Pasar"].str.len() > 0) &
                (df_extracted["iddesa"].str.len() == 10) &
                (df_extracted["iddesa"].str.isdigit())
            ]

            # df_valid["sheet"] = sheet_name
            
            if not df_valid.empty:
                combined_data.append(df_valid)
            else:
                print(f"No valid data in sheet '{sheet_name}'.")
        else:
            print(f"Skipping sheet '{sheet_name}': columns not found.")
    except Exception as e:
        print(f"Error processing sheet '{sheet_name}': {e}")
    
# Function to divide the DataFrame into smaller CSV files, each containing 10 records
def split_csv(df, records_per_file, output_dir):
    for i in range(0, len(df), records_per_file):
        chunk = df.iloc[i : i + records_per_file]
        output_file = f"{output_dir}/market_{i // records_per_file + 1}.csv"
        chunk.to_csv(output_file, index=False)

# Split the file into smaller files
output_directory = "./python_script/result/market/"
os.makedirs(output_directory, exist_ok=True)

records_per_file = 1000
final_df = pd.concat(combined_data, ignore_index=True)
split_csv(final_df, records_per_file=records_per_file, output_dir=output_directory)
