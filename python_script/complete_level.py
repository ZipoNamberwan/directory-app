from pathlib import Path
import pandas as pd

# Load reference files
village_df = pd.read_excel("./python_script/area/des.xlsx", dtype=str)
subdistrict_df = pd.read_excel("./python_script/area/kec.xlsx", dtype=str)

# Convert necessary columns to lowercase
village_df["des_name"] = village_df["des_name"].str.lower()
subdistrict_df["kec_name"] = subdistrict_df["kec_name"].str.lower()


def extract_codes_from_address(filename, df):
    """
    Extract subdistrict and village codes and names from the address column.
    """
    # Get regency code from filename (assuming filename format is 'regencycode_something.xlsx')
    regency_code = filename.split("_")[0]  # Adjust if necessary

    # Filter subdistricts by regency
    filtered_subdistricts = subdistrict_df[
        (subdistrict_df["prov"] + subdistrict_df["kab"]) == regency_code
    ]

    # Create a dictionary for subdistrict names to codes
    subdistrict_map = {
        row["kec_name"]: (f"{row.prov}{row.kab}{row.kec}", row["kec_name"])
        for _, row in filtered_subdistricts.iterrows()
    }

    results = []
    subdistrict_found_count = 0
    village_found_count = 0

    for _, row in df.iterrows():
        address = row["Alamat"].lower()  # Convert address to lowercase
        subdistrict_code = ""
        subdistrict_name = ""
        village_code = ""
        village_name = ""

        # Step 2: Match subdistrict
        for sub_name, (sub_code, sub_name_original) in subdistrict_map.items():
            if sub_name in address:
                subdistrict_code = sub_code
                subdistrict_name = sub_name_original
                subdistrict_found_count += 1
                address = address.replace(sub_name, "").strip()  # Remove matched subdistrict name
                break  # Stop after first match

        # Step 3: Match village if subdistrict is found
        if subdistrict_code:
            province_id, regency_id, subdistrict_id = (
                subdistrict_code[:2],
                subdistrict_code[2:4],
                subdistrict_code[4:],
            )
            filtered_villages = village_df[
                (village_df["prov"] == province_id)
                & (village_df["kab"] == regency_id)
                & (village_df["kec"] == subdistrict_id)
            ]

            # Create a dictionary for village names to codes
            village_map = {
                row["des_name"]: (
                    f"{row.prov}{row.kab}{row.kec}{row.des}",
                    row["des_name"],
                )
                for _, row in filtered_villages.iterrows()
            }

            # Match village
            for village_name_key, (village_code_value, village_name_original) in village_map.items():
                if village_name_key in address:
                    village_code = village_code_value
                    village_name = village_name_original
                    village_found_count += 1
                    address = address.replace(village_name_key, "").strip()  # Remove matched village name
                    break  # Stop after first match

        # Append results with subdistrict & village info
        results.append(
            {
                "subdistrict_code": subdistrict_code,
                "subdistrict_name": subdistrict_name,
                "village_code": village_code,
                "village_name": village_name,
            }
        )

    return pd.DataFrame(results), subdistrict_found_count, village_found_count


# Process files in the folder
folder_path = Path("./python_script/level/other")
summary_data = []

for file_path in folder_path.iterdir():
    if file_path.is_file():
        df = pd.read_excel(file_path, dtype=str)

        df.fillna("", inplace=True)

        level1_df = df[(df["iddesa"] == "") | (df["iddesa"].str.len() == 4)]
        level2_df = df[(df["iddesa"] != "") & (df["iddesa"].str.len() == 7)]

        # Extract codes from address
        extracted_codes_df, subdistrict_count, village_count = extract_codes_from_address(file_path.name, level1_df)

        # Merge extracted codes with level1_df
        level1_df = level1_df.reset_index(drop=True)
        level1_df = pd.concat([level1_df, extracted_codes_df], axis=1)

        # Save output
        output_path = f"./python_script/level/processed_other/processed_{file_path.name}"
        level1_df.to_excel(output_path, index=False)

        # Store summary data
        summary_data.append({
            "File Name": file_path.name,
            "Total Rows": len(level1_df),
            "Subdistricts Found": subdistrict_count,
            "Villages Found": village_count
        })

        print(f"Processed: {file_path.name}")
        print(level1_df.head())

# Save summary to an Excel file
summary_df = pd.DataFrame(summary_data)
summary_df.to_excel("./python_script/level/processed_other/summary_processed.xlsx", index=False)

print("Summary saved to summary_processed.xlsx")
