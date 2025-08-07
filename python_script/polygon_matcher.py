import os
from dotenv import load_dotenv
import mysql.connector
import geopandas as gpd
import pandas as pd
from shapely.geometry import Point
import time
from rtree import index
import numpy as np
from concurrent.futures import ProcessPoolExecutor
import multiprocessing as mp

# === CONFIGURATION ===
MATCH_MODE = "skip_to_sls"  # options: "full", "skip_to_sls"
BATCH_SIZE = 1000  # Process records in batches
MAX_WORKERS = mp.cpu_count() - 1  # Use multiple processes

# Load .env for DB connection
load_dotenv(dotenv_path=".env")

# DB config
db_config = {
    "host": os.getenv("DB_MAIN_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_MAIN_PORT", 3306)),
    "user": os.getenv("DB_MAIN_USERNAME", "root"),
    "password": os.getenv("DB_MAIN_PASSWORD", ""),
    "database": os.getenv("DB_MAIN_DATABASE", ""),
}

# Folder paths
REGENCY_DIR = "python_script/geojson/regency"
SUBDISTRICT_DIR = "python_script/geojson/subdistrict"
VILLAGE_DIR = "python_script/geojson/village"
SLS_BY_VILLAGE_DIR = "python_script/geojson/sls_by_village"
SLS_BY_SUBDISTRICT_DIR = "python_script/geojson/sls_by_subdistrict"

# Global cache for loaded GeoDataFrames and spatial indices
_cache = {}

def create_spatial_index(gdf):
    """Create R-tree spatial index for faster point-in-polygon queries"""
    idx = index.Index()
    for i, geom in enumerate(gdf.geometry):
        idx.insert(i, geom.bounds)
    return idx

def load_geojson_folder_cached(folder_path, cache_key=None):
    """Load GeoJSON folder with caching"""
    if cache_key is None:
        cache_key = folder_path
        
    if cache_key in _cache:
        return _cache[cache_key]
    
    if not os.path.exists(folder_path):
        _cache[cache_key] = (gpd.GeoDataFrame(), None)
        return _cache[cache_key]
    
    frames = []
    for file in os.listdir(folder_path):
        if file.endswith(".json") or file.endswith(".geojson"):
            try:
                gdf = gpd.read_file(os.path.join(folder_path, file))
                gdf["__filename"] = file.split(".")[0]
                frames.append(gdf)
            except Exception as e:
                print(f"⚠️ Error loading {file}: {e}")
                continue
    
    if frames:
        combined_gdf = gpd.GeoDataFrame(pd.concat(frames, ignore_index=True))
        # Create spatial index
        spatial_idx = create_spatial_index(combined_gdf)
        _cache[cache_key] = (combined_gdf, spatial_idx)
    else:
        _cache[cache_key] = (gpd.GeoDataFrame(), None)
    
    return _cache[cache_key]

def find_matching_area_fast(point: Point, gdf_and_idx):
    """Fast point-in-polygon search using spatial index"""
    gdf, spatial_idx = gdf_and_idx
    
    if spatial_idx is None or gdf.empty:
        return None
    
    # Use spatial index to find candidate polygons
    candidates = list(spatial_idx.intersection(point.coords[0] * 2))  # (x, y, x, y)
    
    if not candidates:
        return None
    
    # Check actual containment only for candidates
    for idx in candidates:
        if gdf.iloc[idx].geometry.contains(point):
            return gdf.iloc[idx]
    
    return None

def process_coordinates_batch(coordinates_batch, regency_gdf_data):
    """Process a batch of coordinates"""
    results = []
    
    # Recreate spatial index in this process
    regency_gdf, _ = regency_gdf_data
    regency_spatial_idx = create_spatial_index(regency_gdf)
    regency_gdf_and_idx = (regency_gdf, regency_spatial_idx)
    
    for row_id, lat, lon in coordinates_batch:
        if lat is None or lon is None:
            results.append((row_id, None, None, None, None, "Invalid coordinates"))
            continue
        
        try:
            point = Point(lon, lat)
            
            # Find regency
            matched_regency = find_matching_area_fast(point, regency_gdf_and_idx)
            if matched_regency is None:
                results.append((row_id, None, None, None, None, "No regency found"))
                continue
            
            regency_id = matched_regency["__filename"]
            
            # Find subdistrict
            subdistrict_dir = os.path.join(SUBDISTRICT_DIR, regency_id)
            subdistrict_gdf_and_idx = load_geojson_folder_cached(subdistrict_dir)
            
            matched_subdistrict = find_matching_area_fast(point, subdistrict_gdf_and_idx)
            if matched_subdistrict is None:
                results.append((row_id, regency_id, None, None, None, "No subdistrict found"))
                continue
            
            subdistrict_id = matched_subdistrict["__filename"]
            
            village_id = None
            sls_id = None
            
            # Handle different matching modes
            if MATCH_MODE == "full":
                # Find village
                village_dir = os.path.join(VILLAGE_DIR, subdistrict_id)
                village_gdf_and_idx = load_geojson_folder_cached(village_dir)
                
                matched_village = find_matching_area_fast(point, village_gdf_and_idx)
                if matched_village is None:
                    results.append((row_id, regency_id, subdistrict_id, None, None, "No village found"))
                    continue
                
                village_id = matched_village["__filename"]
                
                # Find SLS by village
                sls_dir = os.path.join(SLS_BY_VILLAGE_DIR, village_id)
                sls_gdf_and_idx = load_geojson_folder_cached(sls_dir)
                
            elif MATCH_MODE == "skip_to_sls":
                # Find SLS by subdistrict
                sls_dir = os.path.join(SLS_BY_SUBDISTRICT_DIR, subdistrict_id)
                sls_gdf_and_idx = load_geojson_folder_cached(sls_dir)
            
            matched_sls = find_matching_area_fast(point, sls_gdf_and_idx)
            if matched_sls is None:
                results.append((row_id, regency_id, subdistrict_id, village_id, None, "No SLS found"))
                continue
            
            sls_id = matched_sls["__filename"]
            results.append((row_id, regency_id, subdistrict_id, village_id, sls_id, "Success"))
            
        except Exception as e:
            results.append((row_id, None, None, None, None, f"Error: {str(e)}"))
    
    return results

def update_rows_batch(cursor, results):
    """Batch update database rows"""
    update_data = []
    for row_id, regency_id, subdistrict_id, village_id, sls_id, status in results:
        if status == "Success":
            update_data.append((regency_id, subdistrict_id, village_id, sls_id, row_id))
    
    if update_data:
        cursor.executemany("""
            UPDATE supplement_business
            SET regency_id = %s,
                subdistrict_id = %s,
                village_id = %s,
                sls_id = %s
            WHERE id = %s
        """, update_data)
    
    return len(update_data)

def preload_regency_data():
    """Preload regency data for multiprocessing"""
    print("📂 Loading and indexing regency polygons...")
    regency_gdf_and_idx = load_geojson_folder_cached(REGENCY_DIR, "regency")
    # Return just the GeoDataFrame for pickling (spatial index will be recreated in each process)
    regency_gdf, _ = regency_gdf_and_idx
    return regency_gdf, None

def main():
    start_total = time.time()
    
    # Preload regency data
    regency_gdf_data = preload_regency_data()
    
    print("🔌 Connecting to database...")
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)
    
    print("📊 Getting total count of rows to process...")
    cursor.execute("""
        SELECT COUNT(*) as total
        FROM supplement_business
        WHERE regency_id IS NULL
           OR subdistrict_id IS NULL
           OR village_id IS NULL
           OR sls_id IS NULL
    """)
    total_rows = cursor.fetchone()["total"]
    print(f"📦 Total rows to process: {total_rows:,}")
    
    if total_rows == 0:
        print("✅ No rows need processing!")
        return
    
    updated = 0
    processed = 0
    
    # Process in batches
    offset = 0
    while offset < total_rows:
        batch_start = time.time()
        
        print(f"📦 Fetching batch {offset//BATCH_SIZE + 1} (rows {offset+1}-{min(offset+BATCH_SIZE, total_rows)})...")
        cursor.execute("""
            SELECT id, latitude, longitude
            FROM supplement_business
            WHERE regency_id IS NULL
               OR subdistrict_id IS NULL
               OR village_id IS NULL
               OR sls_id IS NULL
            LIMIT %s OFFSET %s
        """, (BATCH_SIZE, offset))
        
        rows = cursor.fetchall()
        if not rows:
            break
        
        # Prepare coordinates for processing
        coordinates = [(row["id"], row["latitude"], row["longitude"]) for row in rows]
        
        # Split coordinates into chunks for multiprocessing
        chunk_size = max(1, len(coordinates) // MAX_WORKERS)
        chunks = [coordinates[i:i + chunk_size] for i in range(0, len(coordinates), chunk_size)]
        
        print(f"🚀 Processing {len(coordinates)} coordinates using {len(chunks)} processes...")
        
        # Process chunks in parallel
        with ProcessPoolExecutor(max_workers=MAX_WORKERS) as executor:
            futures = [executor.submit(process_coordinates_batch, chunk, regency_gdf_data) for chunk in chunks]
            
            all_results = []
            for future in futures:
                all_results.extend(future.result())
        
        # Update database in batch (COMMENTED OUT FOR TESTING)
        # batch_updated = update_rows_batch(cursor, all_results)
        # conn.commit()
        
        # Count successful matches for reporting
        batch_updated = sum(1 for result in all_results if result[5] == "Success")
        updated += batch_updated
        processed += len(coordinates)
        
        batch_end = time.time()
        batch_time = batch_end - batch_start
        rate = len(coordinates) / batch_time if batch_time > 0 else 0
        
        print(f"✅ Batch completed: {batch_updated}/{len(coordinates)} updated in {batch_time:.2f}s")
        print(f"📈 Rate: {rate:.1f} coordinates/second")
        print(f"📊 Progress: {processed}/{total_rows} ({processed/total_rows*100:.1f}%)")
        
        # Estimate remaining time
        if rate > 0:
            remaining = total_rows - processed
            eta_seconds = remaining / rate
            eta_hours = eta_seconds / 3600
            print(f"⏱️ ETA: {eta_hours:.1f} hours\n")
        
        offset += BATCH_SIZE
    
    total_time = time.time() - start_total
    final_rate = processed / total_time if total_time > 0 else 0
    
    print(f"🎉 Processing complete!")
    print(f"📊 Total processed: {processed:,}")
    print(f"✅ Total successfully matched: {updated:,}")
    print(f"⏱️ Total time: {total_time/3600:.2f} hours")
    print(f"📈 Average rate: {final_rate:.1f} coordinates/second")
    print(f"💾 Database updates were DISABLED for this test run")
    
    cursor.close()
    conn.close()

if __name__ == "__main__":
    main()