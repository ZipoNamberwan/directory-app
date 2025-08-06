import os
from dotenv import load_dotenv
import mysql.connector
import geopandas as gpd
from shapely.geometry import Point

# Load environment variables from Laravel .env file
load_dotenv(dotenv_path="../.env")  # Change path if needed

# Fetch DB credentials from the .env
db_config = {
    "host": os.getenv("DB_MAIN_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_MAIN_PORT", 3306)),
    "user": os.getenv("DB_MAIN_USERNAME", "root"),
    "password": os.getenv("DB_MAIN_PASSWORD", ""),
    "database": os.getenv("DB_MAIN_DATABASE", ""),
}

# Load the GeoJSON file with polygons
geojson_path = "../data/admin_boundaries.geojson"  # Adjust path if needed
print(f"📂 Loading GeoJSON: {geojson_path}")
gdf = gpd.read_file(geojson_path)

# Ensure CRS is correct
if gdf.crs is None or gdf.crs.to_epsg() != 4326:
    print("⚠️ Warning: CRS not set or incorrect. Forcing to EPSG:4326 (WGS84).")
    gdf = gdf.set_crs("EPSG:4326", allow_override=True)

# Connect to MySQL
conn = mysql.connector.connect(**db_config)
cursor = conn.cursor(dictionary=True)

# Query supplement_business entries with null region info
print("📦 Fetching rows with missing admin boundaries...")
cursor.execute("""
    SELECT id, latitude, longitude
    FROM supplement_business
    WHERE regency_id IS NULL
       OR subdistrict_id IS NULL
       OR village_id IS NULL
       OR sls_id IS NULL
    LIMIT 10
""")
rows = cursor.fetchall()

print(f"🔍 Matching {len(rows)} rows...")
updated = 0
skipped = 0

for row in rows:
    business_id = row["id"]
    lat = row["latitude"]
    lon = row["longitude"]

    if lat is None or lon is None:
        print(f"⚠️ Skipped ID {business_id} due to missing lat/lon")
        skipped += 1
        continue

    point = Point(lon, lat)
    match = gdf[gdf.contains(point)]

    if not match.empty:
        matched_row = match.iloc[0]

        regency_id = matched_row.get("regency_id")
        subdistrict_id = matched_row.get("subdistrict_id")
        village_id = matched_row.get("village_id")
        sls_id = matched_row.get("sls_id")

        cursor.execute("""
            UPDATE supplement_business
            SET regency_id = %s,
                subdistrict_id = %s,
                village_id = %s,
                sls_id = %s
            WHERE id = %s
        """, (regency_id, subdistrict_id, village_id, sls_id, business_id))

        print(f"✅ Updated ID {business_id}")
        updated += 1
    else:
        print(f"⚠️ No match for ID {business_id} at ({lat}, {lon})")
        skipped += 1

# Finalize DB operations
conn.commit()
cursor.close()
conn.close()

print(f"\n✅ Done: {updated} updated, {skipped} skipped.")
