from pathlib import Path
import pandas as pd


def load_reference_data():
    """Load and prepare reference data files."""
    village_df = pd.read_excel("./python_script/area/des.xlsx", dtype=str)
    subdistrict_df = pd.read_excel("./python_script/area/kec.xlsx", dtype=str)

    # Convert necessary columns to lowercase
    village_df["des_name"] = village_df["des_name"].str.lower()
    subdistrict_df["kec_name"] = subdistrict_df["kec_name"].str.lower()

    return village_df, subdistrict_df


def get_filtered_data(regency_code, village_df, subdistrict_df):
    """Filter subdistricts and villages by regency code."""
    filtered_subdistricts = subdistrict_df[
        (subdistrict_df["prov"] + subdistrict_df["kab"]) == regency_code
    ]

    filtered_villages = village_df[
        (village_df["prov"] + village_df["kab"]) == regency_code
    ]
    
    #reorder regency and subdistrict with the same name, it should be in the bottom of the array
    regency_name = filtered_subdistricts["kab_name"].iloc[0].lower()
    condition = (filtered_subdistricts["kec_name"] == regency_name)
    to_move = filtered_subdistricts[condition]
    remaining = filtered_subdistricts[~condition]
    filtered_subdistricts = pd.concat([remaining, to_move], ignore_index=True)

    # Create mapping dictionaries for faster lookups
    subdistrict_map = {
        row["kec_name"]: (f"{row.prov}{row.kab}{row.kec}", row["kec_name"])
        for _, row in filtered_subdistricts.iterrows()
    }

    # Pre-compute village mapping grouped by subdistrict
    all_village_regency = {}
    for subdistrict_id in filtered_subdistricts["kec"].unique():
        province_id = filtered_subdistricts.iloc[0]["prov"]
        regency_id = filtered_subdistricts.iloc[0]["kab"]

        mask = (
            (filtered_villages["prov"] == province_id)
            & (filtered_villages["kab"] == regency_id)
            & (filtered_villages["kec"] == subdistrict_id)
        )

        villages = filtered_villages[mask]
        all_village_regency[subdistrict_id] = {
            row["des_name"]: (f"{row.prov}{row.kab}{row.kec}{row.des}", row["des_name"])
            for _, row in villages.iterrows()
        }

    # Also create full village map for regency-wide searches
    village_map = {
        row["des_name"]: (f"{row.prov}{row.kab}{row.kec}{row.des}", row["des_name"])
        for _, row in filtered_villages.iterrows()
    }

    return subdistrict_map, all_village_regency, village_map


def extract_codes_from_address(filename, df, village_df, subdistrict_df):
    """Extract subdistrict and village codes and names from the address column."""
    # Get regency code from filename
    regency_code = filename.split("_")[0]

    # Get filtered data and mapping dictionaries
    subdistrict_map, all_village_regency, village_map = get_filtered_data(
        regency_code, village_df, subdistrict_df
    )

    results = []
    subdistrict_found_count = 0
    village_found_count = 0

    for idx, row in df.iterrows():
        # Initialize default values
        print(f"Processing {regency_code} row {idx + 1} of {len(df)}")
        subdistrict_code = ""
        subdistrict_name = ""
        village_code = ""
        village_name = ""

        # Process rows based on iddesa status
        if row["iddesa"] == "" or len(row["iddesa"]) == 4:
            address = row["Alamat"].lower()

            # Match subdistrict
            for sub_name, (sub_code, sub_name_original) in subdistrict_map.items():
                if sub_name in address:
                    subdistrict_code = sub_code
                    subdistrict_name = sub_name_original
                    subdistrict_found_count += 1
                    address = address.replace(
                        sub_name, ""
                    ).strip()
                    break

            # Match village based on whether subdistrict was found
            if subdistrict_code:
                # Use pre-computed village map for this subdistrict
                subdistrict_id = subdistrict_code[4:]
                relevant_villages = all_village_regency.get(subdistrict_id, {})

                for village_key, (
                    village_code_value,
                    village_name_original,
                ) in relevant_villages.items():
                    if village_key in address:
                        village_code = village_code_value
                        village_name = village_name_original
                        village_found_count += 1
                        break
            else:
                # Try to find village in the entire regency
                for village_key, (
                    village_code_value,
                    village_name_original,
                ) in village_map.items():
                    if village_key in address:
                        village_code = village_code_value
                        village_name = village_name_original
                        village_found_count += 1
                        break

            # Increment subdistrict count if village found but subdistrict missing
            if subdistrict_code == "" and village_code != "":
                subdistrict_found_count += 1

        elif row["iddesa"] != "" and len(row["iddesa"]) == 7:
            # If we already have subdistrict info, just try to find the village
            subdistrict_code = row["iddesa"]
            province_id, regency_id, subdistrict_id = (
                subdistrict_code[:2],
                subdistrict_code[2:4],
                subdistrict_code[4:],
            )

            address = row["Alamat"].lower()

            # Use relevant villages for the subdistrict
            relevant_villages = all_village_regency.get(subdistrict_id, {})

            for village_key, (
                village_code_value,
                village_name_original,
            ) in relevant_villages.items():
                if village_key in address:
                    village_code = village_code_value
                    village_name = village_name_original
                    village_found_count += 1
                    break

        # Append results
        results.append(
            {
                "subdistrict_code": subdistrict_code,
                "subdistrict_name": subdistrict_name,
                "village_code": village_code,
                "village_name": village_name,
            }
        )

    return pd.DataFrame(results), subdistrict_found_count, village_found_count


def main():
    # Load reference data once
    village_df, subdistrict_df = load_reference_data()

    # Process files in the folder
    folder_path = Path("./python_script/level/other")
    summary_data = []

    for file_path in folder_path.iterdir():
        if file_path.is_file() and file_path.suffix.lower() in [".xlsx", ".xls"]:
            print(f"Processing: {file_path.name}")

            # Read and prepare data
            df = pd.read_excel(file_path, dtype=str)
            df.fillna("", inplace=True)

            # Extract codes from address
            extracted_codes_df, subdistrict_count, village_count = (
                extract_codes_from_address(
                    file_path.name, df, village_df, subdistrict_df
                )
            )

            # Merge extracted codes with df
            df = df.reset_index(drop=True)
            df = pd.concat([df, extracted_codes_df], axis=1)

            # Copy extracted codes to iddesa
            df["iddesa"] = df["village_code"].where(
                df["village_code"] != "", df["subdistrict_code"]
            )

            # Save output
            output_path = (
                f"./python_script/level/processed_other/processed_{file_path.name}"
            )
            df.to_excel(output_path, index=False)

            # Store summary data
            summary_data.append(
                {
                    "File Name": file_path.name,
                    "Total Rows": len(df),
                    "Subdistricts Found": subdistrict_count,
                    "Villages Found": village_count,
                }
            )

    # Save summary to an Excel file
    summary_df = pd.DataFrame(summary_data)
    summary_df.to_excel(
        "./python_script/level/processed_other/summary_processed.xlsx", index=False
    )

    print("Processing complete. Summary saved to summary_processed.xlsx")


if __name__ == "__main__":
    main()
