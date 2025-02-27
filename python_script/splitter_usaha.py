from pathlib import Path
import pandas as pd
import os

 # Function to divide the DataFrame into smaller CSV files, each containing 10 records
def split_csv(df, records_per_file, output_dir):
    print(df)
    for i in range(0, len(df), records_per_file):
        chunk = df.iloc[i : i + records_per_file]
        output_file = f"{output_dir}/{(file_path.stem)[:4]} output_{i // records_per_file + 1}.csv"
        chunk.to_csv(output_file, index=False)
                
# Specify the folder path
folder_path = Path("./python_script/level/desa")

# Loop through files in the folder
for file_path in folder_path.iterdir():
    if file_path.is_file():  # Check if it's a file
        df = pd.read_excel(file_path, dtype=str)

        # filtered_df = df[df['sumber'].str.contains("snapwangi", case=False, na=False)]
        
        # Split the file into smaller files
        output_directory = "./python_script/result/usaha"
        os.makedirs(output_directory, exist_ok=True)

        records_per_file = 1000
        split_csv(df, records_per_file=1000, output_dir=output_directory)
