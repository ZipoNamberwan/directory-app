import os
import time
from dotenv import load_dotenv
import mysql.connector
import pandas as pd
import geopandas as gpd
from shapely.geometry import Point
from shapely import speedups
from tqdm import tqdm
import math
from datetime import datetime
import pytz

# Enable Shapely speedups if available
if hasattr(speedups, "available") and speedups.available:
    speedups.enable()

# =========================
# BASE DIR
# =========================
BASE_DIR = "/var/www"  # Absolute path to your project root

# =========================
# CONFIG
# =========================
DEBUG_MODE = False
DEBUG_SLS_LIMIT = 10000
BATCH_SIZE_DB = 100000
CHUNK_SIZE_JOIN = 50000
POINTS_CRS = "EPSG:4326"

# Jakarta timezone
JAKARTA_TZ = pytz.timezone('Asia/Jakarta')

def get_jakarta_now():
    """Get current datetime in Jakarta timezone"""
    return datetime.now(JAKARTA_TZ)

# Absolute paths
SLS_DIR = os.path.join(BASE_DIR, "python_script/geojson/sls_by_subdistrict")
SLS_FILE_EXTS = (".json", ".geojson")
DOTENV_PATH = os.path.join(BASE_DIR, ".env")

# Tables to process
TABLES_TO_PROCESS = ["supplement_business", "market_business"]

# ID slicing
ID_SLICE = {
    "regency": 4,
    "subdistrict": 7,
    "village": 10,
    "sls_suffix": "00",
}

# Load .env using absolute path
load_dotenv(dotenv_path=DOTENV_PATH)

DB = {
    "host": os.getenv("DB_MAIN_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_MAIN_PORT", 3306)),
    "user": os.getenv("DB_MAIN_USERNAME", "root"),
    "password": os.getenv("DB_MAIN_PASSWORD", ""),
    "database": os.getenv("DB_MAIN_DATABASE", ""),
}

# =========================
# GEO LOADING (with progress)
# =========================
def list_sls_files(sls_root: str):
    """Return list of all SLS files under sls_root with supported extensions."""
    file_list = []
    for root, _, files in os.walk(sls_root):
        for fn in files:
            if fn.lower().endswith(SLS_FILE_EXTS):
                file_list.append(os.path.join(root, fn))
    return file_list

def load_all_sls(sls_root: str, debug_limit: int = None) -> gpd.GeoDataFrame:
    """
    Load SLS polygons into a single GeoDataFrame.
    Adds '__filename' (basename without extension).
    Ensures CRS is POINTS_CRS; reprojects if needed.
    Shows progress for files and total polygons loaded.
    If debug_limit is set, only loads up to that many polygons for fast testing.
    """
    if not os.path.exists(sls_root):
        raise FileNotFoundError(f"SLS root not found: {sls_root}")

    files = list_sls_files(sls_root)
    if not files:
        raise RuntimeError(f"No SLS GeoJSON files found under: {sls_root}")

    print(f"📂 Loading SLS polygons from {len(files):,} files...")
    frames = []
    total_polys = 0
    t0 = time.time()

    for path in tqdm(files, desc="📥 Loading SLS files", unit="file"):
        if debug_limit is not None and total_polys >= debug_limit:
            break
        try:
            gdf = gpd.read_file(path)
            base = os.path.splitext(os.path.basename(path))[0]
            gdf["__filename"] = base
            # Normalize CRS
            if gdf.crs is None:
                gdf.set_crs(POINTS_CRS, allow_override=True, inplace=True)
            elif gdf.crs.to_string() != POINTS_CRS:
                gdf = gdf.to_crs(POINTS_CRS)
            if debug_limit is not None and total_polys + len(gdf) > debug_limit:
                # Only take up to debug_limit polygons
                gdf = gdf.iloc[:debug_limit - total_polys]
            frames.append(gdf)
            total_polys += len(gdf)
        except Exception as e:
            print(f"⚠️ Failed to load {path}: {e}")

    if not frames:
        raise RuntimeError("SLS load failed: no valid GeoDataFrames created.")

    sls_gdf = gpd.GeoDataFrame(pd.concat(frames, ignore_index=True), crs=POINTS_CRS)
    sls_gdf.set_geometry("geometry", inplace=True)

    # Build spatial index (lazily built on first sjoin; we force it for early cost)
    print("🧱 Building spatial index for SLS (one-time)...")
    _ = sls_gdf.sindex

    dt = time.time() - t0
    print(f"✅ SLS loaded: {len(sls_gdf):,} polygons from {len(files):,} files in {dt:.1f}s")
    return sls_gdf

# =========================
# ID DERIVATION
# =========================
def derive_ids_from_sls_code(sls_code: str):
    """Return (regency_id, subdistrict_id, village_id, sls_id) from SLS base code."""
    reg_id = sls_code[:ID_SLICE["regency"]] if ID_SLICE.get("regency") else None
    sub_id = sls_code[:ID_SLICE["subdistrict"]] if ID_SLICE.get("subdistrict") else None
    vil_id = sls_code[:ID_SLICE["village"]] if ID_SLICE.get("village") else None
    sls_id = sls_code + ID_SLICE.get("sls_suffix", "")
    return reg_id, sub_id, vil_id, sls_id

# =========================
# DB HELPERS (modified for multi-table support)
# =========================
def validate_table_name(table_name: str) -> str:
    """
    Validate table name against allowed tables to prevent SQL injection.
    Returns the validated table name or raises ValueError if invalid.
    """
    if table_name not in TABLES_TO_PROCESS:
        raise ValueError(f"Invalid table name: {table_name}. Allowed tables: {TABLES_TO_PROCESS}")
    return table_name

def get_total_rows_to_process(cursor, table_name: str) -> int:
    """
    Returns the total number of rows in the specified table that are either:
    - not matched yet (matched_at IS NULL), OR
    - Have been modified since the last match updated_at > matched_at
    """
    # Validate table name to prevent SQL injection
    validated_table = validate_table_name(table_name)
    
    query = f"""
        SELECT COUNT(*) AS total
        FROM {validated_table}
        WHERE matched_at IS NULL
           OR updated_at > matched_at
    """
    cursor.execute(query)
    result = cursor.fetchone()
    return result["total"] if result else 0

def fetch_batch(cursor, table_name: str, limit: int):
    """Fetch a batch of rows from the specified table."""
    # Validate table name to prevent SQL injection
    validated_table = validate_table_name(table_name)
    
    query = f"""
        SELECT id, latitude, longitude
        FROM {validated_table}
        WHERE matched_at IS NULL
        OR updated_at > matched_at
        ORDER BY id
        LIMIT %s
    """
    cursor.execute(query, (limit,))
    return cursor.fetchall()

def update_rows_batch(cursor, table_name: str, rows: list[tuple]):
    """
    rows: list of tuples (regency_id, subdistrict_id, village_id, sls_id, match_level, id)
    """
    if not rows:
        return 0
    
    # Validate table name to prevent SQL injection
    validated_table = validate_table_name(table_name)
    
    # Get Jakarta current time once for this batch
    jakarta_now = get_jakarta_now()
    
    update_query = f"""
        UPDATE {validated_table}
        SET regency_id = %s,
            subdistrict_id = %s,
            village_id = %s,
            sls_id = %s,
            match_level = %s,
            matched_at = %s
        WHERE id = %s
    """
    
    try:
        # Add jakarta_now to each row tuple
        rows_with_time = [(reg, sub, vil, sls, match, jakarta_now, id_) for reg, sub, vil, sls, match, id_ in rows]
        cursor.executemany(update_query, rows_with_time)
        return cursor.rowcount
    except mysql.connector.errors.IntegrityError:
                print(f"❌ Batch IntegrityError in {validated_table} → retrying one by one...")
                updated_count = 0

                for row in rows:
                    regency_id, subdistrict_id, village_id, sls_id, match_level, business_id = row
                    try:
                        # try full update first
                        cursor.execute(update_query, (regency_id, subdistrict_id, village_id, sls_id, match_level, jakarta_now, business_id))
                        updated_count += 1
                    except mysql.connector.errors.IntegrityError as e_sls:
                        print(f"❌ Failed sls_id for {validated_table} row {business_id}, retrying with village_id and above...")
                        try:
                            cursor.execute(f"""
                                UPDATE {validated_table}
                                SET regency_id = %s,
                                    subdistrict_id = %s,
                                    village_id = %s,
                                    sls_id = NULL,
                                    match_level = 'village',
                                    matched_at = %s
                                WHERE id = %s
                            """, (regency_id, subdistrict_id, village_id, jakarta_now, business_id))
                            updated_count += 1
                        except mysql.connector.errors.IntegrityError as e_village:
                            print(f"❌ Failed village_id for {validated_table} row {business_id}, retrying with subdistrict_id and above...")
                            try:
                                cursor.execute(f"""
                                    UPDATE {validated_table}
                                    SET regency_id = %s,
                                        subdistrict_id = %s,
                                        village_id = NULL,
                                        sls_id = NULL,
                                        match_level = 'subdistrict',
                                        matched_at = %s
                                    WHERE id = %s
                                """, (regency_id, subdistrict_id, jakarta_now, business_id))
                                updated_count += 1
                            except mysql.connector.errors.IntegrityError as e_subdistrict:
                                print(f"❌ Failed subdistrict_id for {validated_table} row {business_id}, retrying with regency_id only...")
                                try:
                                    cursor.execute(f"""
                                        UPDATE {validated_table}
                                        SET regency_id = %s,
                                            subdistrict_id = NULL,
                                            village_id = NULL,
                                            sls_id = NULL,
                                            match_level = 'regency',
                                            matched_at = %s
                                        WHERE id = %s
                                    """, (regency_id, jakarta_now, business_id))
                                    updated_count += 1
                                except mysql.connector.errors.IntegrityError as e_regency:
                                    print(f"❌ Failed regency_id for {validated_table} row {business_id}, fallback → only matched_at")
                                    try:
                                        cursor.execute(f"""
                                            UPDATE {validated_table}
                                            SET match_level = 'noarea',
                                                matched_at = %s
                                            WHERE id = %s
                                        """, (jakarta_now, business_id))
                                        updated_count += 1
                                    except mysql.connector.errors.IntegrityError as e_noarea:
                                        print(f"❌ Failed noarea for {validated_table} row {business_id}")

                return updated_count
    
def mark_failed_rows(cursor, table_name: str, row_ids: list[int]):
    """Mark rows as failed in the specified table."""
    if not row_ids:
        return 0
    
    # Validate table name to prevent SQL injection
    validated_table = validate_table_name(table_name)
    
    # Get Jakarta current time
    jakarta_now = get_jakarta_now()
    
    sql = f"""
        UPDATE {validated_table}
        SET match_level = 'failed',
            matched_at = %s
        WHERE id = %s
    """
    for rid in row_ids:
        cursor.execute(sql, (jakarta_now, rid))
    return len(row_ids)

# =========================
# POINTS → GDF & MATCHING (with progress)
# =========================
def build_points_gdf(records) -> gpd.GeoDataFrame:
    """
    records: list of dicts with keys id, latitude, longitude
    Returns GeoDataFrame (valid coords only).
    """
    df = pd.DataFrame(records)
    if df.empty:
        return gpd.GeoDataFrame(columns=["id", "latitude", "longitude", "geometry"], crs=POINTS_CRS)

    df["valid"] = df["latitude"].notna() & df["longitude"].notna()
    df = df[df["valid"]].copy()
    if df.empty:
        return gpd.GeoDataFrame(columns=["id", "latitude", "longitude", "geometry"], crs=POINTS_CRS)

    df["geometry"] = [Point(lon, lat) for lat, lon in zip(df["latitude"], df["longitude"])]
    gdf = gpd.GeoDataFrame(df[["id", "latitude", "longitude", "geometry"]], geometry="geometry", crs=POINTS_CRS)
    return gdf

def join_points_to_sls_chunked(points_gdf: gpd.GeoDataFrame, sls_gdf: gpd.GeoDataFrame,
                               chunk_size: int) -> pd.DataFrame:
    """
    Chunked spatial join for progress & memory control.
    Returns pandas DataFrame: columns [id, sls_base]
    """
    n = len(points_gdf)
    if n == 0:
        return pd.DataFrame(columns=["id", "sls_base"])

    chunks = range(0, n, chunk_size)
    out_frames = []
    matched_count = 0
    t0 = time.time()

    for i in tqdm(chunks, desc="📍 Matching points→SLS", unit="chunk"):
        sub = points_gdf.iloc[i:i + chunk_size]
        joined = gpd.sjoin(sub, sls_gdf[["__filename", "geometry"]], predicate="within", how="left")
        joined = joined[["id", "__filename"]].rename(columns={"__filename": "sls_base"})
        matched_count += joined["sls_base"].notna().sum()
        out_frames.append(joined)

    dt = time.time() - t0
    print(f"🔢 Matched {matched_count:,}/{n:,} points in {dt:.1f}s (this DB batch)")
    return pd.concat(out_frames, ignore_index=True) if out_frames else pd.DataFrame(columns=["id", "sls_base"])

# =========================
# PROCESS SINGLE TABLE
# =========================
def process_table(cursor, conn, table_name: str, sls_gdf: gpd.GeoDataFrame):
    """Process a single table with geolocation matching."""
    print(f"\n{'='*60}")
    print(f"🎯 Processing table: {table_name}")
    print(f"{'='*60}")
    
    # Determine how many rows to process for this table
    total_target = get_total_rows_to_process(cursor, table_name)
    print(f"🎯 Safety net activated: expecting to process about {total_target:,} rows in {table_name}")

    processed = 0
    updated_total = 0
    rolling_matched_points = 0  # across all batches
    batch_idx = 0

    while True:
        batch_idx += 1
        t_batch = time.time()

        # Fetch a big batch from DB
        print(f"\n📦 Fetching {table_name} batch #{batch_idx} (LIMIT {BATCH_SIZE_DB}) ...")
        rows = fetch_batch(cursor, table_name, BATCH_SIZE_DB)
        if not rows:
            print(f"✅ No more unmatched rows found in {table_name}.")
            break

        # Build points GeoDataFrame (shows invalid dropped implicitly)
        points_gdf = build_points_gdf(rows)
        print(f"🧩 Valid points in this batch: {len(points_gdf):,} / {len(rows):,}")

        if len(points_gdf) == 0:
            processed += len(rows)
            continue

        # Chunked spatial join with progress
        joined_df = join_points_to_sls_chunked(points_gdf, sls_gdf, CHUNK_SIZE_JOIN)

        # Prepare updates
        updates = []
        failed_ids = []
        sls_map = dict(zip(joined_df["id"].tolist(), joined_df["sls_base"].tolist()))
        matched_in_batch = 0

        for rec in rows:
            rid = rec["id"]
            sls_base = sls_map.get(rid)
            if sls_base is None or pd.isna(sls_base):
                failed_ids.append(rid)  # mark as failed later
                continue
            reg_id, sub_id, vil_id, sls_id = derive_ids_from_sls_code(str(sls_base))
            row_tuple = (reg_id, sub_id, vil_id, sls_id, "sls", rid)
            updates.append(row_tuple)
            matched_in_batch += 1

        # Bulk update matches
        updated = update_rows_batch(cursor, table_name, updates)

        # Bulk mark failures
        failed = mark_failed_rows(cursor, table_name, failed_ids)

        conn.commit()

        # Progress stats
        processed += len(rows)
        updated_total += updated
        rolling_matched_points += matched_in_batch

        batch_dt = time.time() - t_batch
        rate = len(rows) / batch_dt if batch_dt > 0 else 0.0

        print(f"✅ {table_name} Batch #{batch_idx}: {updated:,}/{len(rows):,} updated in {batch_dt:.1f}s — {rate:,.0f} rows/s")
        print(f"📊 {table_name} Processed so far: {processed:,} | Matched so far: {rolling_matched_points:,}")
        print(f"❌ {table_name} Failed in batch: {failed:,}")

        # Safety net check
        if processed >= total_target:
            print(f"\n🛑 Safety net triggered for {table_name}: processed rows reached the expected total limit.")
            break

    return {
        'table': table_name,
        'processed': processed,
        'updated': updated_total,
        'matched_points': rolling_matched_points
    }

# =========================
# MAIN
# =========================
def main():
    global_start = time.time()

    # 1) Load SLS polygons once (with progress)
    sls_gdf = load_all_sls(SLS_DIR, DEBUG_SLS_LIMIT if DEBUG_MODE else None)

    # 2) Connect DB
    print("🔌 Connecting to DB...")
    conn = mysql.connector.connect(**DB)
    cursor = conn.cursor(dictionary=True)

    # 3) Process each table
    all_results = []
    
    for table_name in TABLES_TO_PROCESS:
        try:
            result = process_table(cursor, conn, table_name, sls_gdf)
            all_results.append(result)
        except Exception as e:
            print(f"❌ Error processing table {table_name}: {e}")
            continue

    # 4) Summary
    total_dt = time.time() - global_start
    print("\n" + "="*80)
    print("🎉 ALL TABLES COMPLETED")
    print("="*80)
    
    total_processed = sum(r['processed'] for r in all_results)
    total_updated = sum(r['updated'] for r in all_results)
    total_matched = sum(r['matched_points'] for r in all_results)
    
    for result in all_results:
        print(f"📋 {result['table']:<20}: {result['processed']:>8,} processed | {result['updated']:>8,} updated | {result['matched_points']:>8,} matched")
    
    print("-" * 80)
    print(f"📈 TOTAL ACROSS ALL TABLES: {total_processed:>8,} processed | {total_updated:>8,} updated | {total_matched:>8,} matched")
    print(f"⏱️ Total execution time: {total_dt/60:.2f} minutes")

    cursor.close()
    conn.close()

if __name__ == "__main__":
    main()