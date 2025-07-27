
import pandas as pd
import os

def split_excel_to_csv(input_excel, output_dir, rows_per_file=1000):
    # Read the Excel file, force all columns to string
    df = pd.read_excel(input_excel, dtype=str)
    total_rows = len(df)
    os.makedirs(output_dir, exist_ok=True)
    num_files = (total_rows + rows_per_file - 1) // rows_per_file

    for i in range(num_files):
        start_row = i * rows_per_file
        end_row = min(start_row + rows_per_file, total_rows)
        chunk = df.iloc[start_row:end_row].astype(str)
        output_path = os.path.join(output_dir, f"output_part_{i+1}.csv")
        chunk.to_csv(output_path, index=False)
        print(f"Saved {output_path} with rows {start_row} to {end_row-1}")

if __name__ == "__main__":
    # Example usage
    input_excel = "./python_script/sipw-assignment/sipw assignment.xlsx"
    output_dir = "./python_script/result/sipw-assignment"
    rows_per_file = 1000
    split_excel_to_csv(input_excel, output_dir, rows_per_file)
