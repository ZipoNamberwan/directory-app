import pandas as pd
import os

types = ["kec", "des", "sls"]

for type in types:

    # Load the uploaded CSV file
    file_path = "./python_script/area/" + type + ".xlsx"
    df = pd.read_excel(file_path, dtype=str)

    # Function to divide the DataFrame into smaller CSV files, each containing 10 records
    def split_csv(df, records_per_file, output_dir):
        print(df)
        for i in range(0, len(df), records_per_file):
            chunk = df.iloc[i : i + records_per_file]
            output_file = f"{output_dir}/output_{i // records_per_file + 1}.csv"
            chunk.to_csv(output_file, index=False)

    # Split the file into smaller files
    output_directory = "./python_script/result/" + type + "/"
    os.makedirs(output_directory, exist_ok=True)

    records_per_file = 1000
    split_csv(df, records_per_file=1000, output_dir=output_directory)
