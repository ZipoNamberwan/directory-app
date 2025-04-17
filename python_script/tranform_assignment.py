import pandas as pd

# Load the Excel file
file_path = "./python_script/assignment/assignment provinsi.xlsx"

# Load the main sheet
df = pd.read_excel(file_path, sheet_name='assignment')

# Fill down merged cells in the 'Tim' column
df['Tim'] = df['Tim'].fillna(method='ffill')

# Step 1: Create list of (Market, Officer) pairs
expanded_rows = []
for _, row in df.iterrows():
    market = row['Nama Pasar']
    officers = str(row['Tim']).split('\n')  # Split by newline
    for officer in officers:
        pair = (market.strip(), officer.strip())
        expanded_rows.append(pair)

# Convert to DataFrame and drop duplicate pairs
pair_df = pd.DataFrame(expanded_rows, columns=['nama_pasar', 'nama_petugas'])
unique_pair_df = pair_df.drop_duplicates()

# Step 2: Load Market and Officer reference data
market_df = pd.read_excel(file_path, sheet_name=1)  # Market sheet
officer_df = pd.read_excel(file_path, sheet_name=2)  # Officer sheet

# Step 3: Normalize and merge
market_df['nama_pasar'] = market_df['nama_pasar'].str.strip()
officer_df['nama_petugas'] = officer_df['nama_petugas'].str.strip()

# Merge market ID and officer email
merged_df = unique_pair_df.merge(market_df[['nama_pasar', 'id_pasar']], on='nama_pasar', how='left')
merged_df = merged_df.merge(officer_df[['nama_petugas', 'email_bps']], on='nama_petugas', how='left')

# Step 4: Reorder columns
final_df = merged_df[['id_pasar', 'nama_pasar', 'nama_petugas', 'email_bps']]

# Step 5: Save the result
final_df.to_excel("./python_script/result/assignment/result assignment.xlsx", index=False)
