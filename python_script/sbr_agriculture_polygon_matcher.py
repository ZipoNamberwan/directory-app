import gc
import os
import time
from datetime import datetime

import geopandas as gpd
import mysql.connector
import pandas as pd
import pytz
from dotenv import load_dotenv
from shapely import speedups
from shapely.geometry import Point
from tqdm import tqdm


if hasattr(speedups, "available") and speedups.available:
	speedups.enable()


BASE_DIR = "/var/www"

DEBUG_MODE = False
DEBUG_SLS_LIMIT = 10000
BATCH_SIZE_DB = 100000
CHUNK_SIZE_JOIN = 50000
POINTS_CRS = "EPSG:4326"

JAKARTA_TZ = pytz.timezone("Asia/Jakarta")

GEOJSON_BASE_DIR = os.path.join(BASE_DIR, "storage/app/private/geojson")
SLS_FILE_EXTS = (".json", ".geojson")
DOTENV_PATH = os.path.join(BASE_DIR, ".env")

TABLES_TO_PROCESS = ["sbr_business", "agriculture_business"]

ID_SLICE = {
	"regency": 4,
	"subdistrict": 7,
	"village": 10,
	"sls_suffix": "00",
}

load_dotenv(dotenv_path=DOTENV_PATH)

DB = {
	"host": os.getenv("DB_MAIN_HOST", "127.0.0.1"),
	"port": int(os.getenv("DB_MAIN_PORT", 3306)),
	"user": os.getenv("DB_MAIN_USERNAME", "root"),
	"password": os.getenv("DB_MAIN_PASSWORD", ""),
	"database": os.getenv("DB_MAIN_DATABASE", ""),
}


def get_jakarta_now():
	return datetime.now(JAKARTA_TZ)


def list_sls_files(sls_root: str):
	file_list = []
	for root, _, files in os.walk(sls_root):
		for filename in files:
			if filename.lower().endswith(SLS_FILE_EXTS):
				file_list.append(os.path.join(root, filename))
	return file_list


def load_all_sls(sls_root: str, debug_limit: int = None) -> gpd.GeoDataFrame:
	if not os.path.exists(sls_root):
		raise FileNotFoundError(f"SLS root not found: {sls_root}")

	files = list_sls_files(sls_root)
	if not files:
		raise RuntimeError(f"No SLS GeoJSON files found under: {sls_root}")

	print(f"📂 Loading SLS polygons from {len(files):,} files...")
	frames = []
	total_polys = 0
	started_at = time.time()

	for path in tqdm(files, desc="📥 Loading SLS files", unit="file"):
		if debug_limit is not None and total_polys >= debug_limit:
			break

		try:
			gdf = gpd.read_file(path)
			base = os.path.splitext(os.path.basename(path))[0]
			gdf["__filename"] = base

			if gdf.crs is None:
				gdf.set_crs(POINTS_CRS, allow_override=True, inplace=True)
			elif gdf.crs.to_string() != POINTS_CRS:
				gdf = gdf.to_crs(POINTS_CRS)

			if debug_limit is not None and total_polys + len(gdf) > debug_limit:
				gdf = gdf.iloc[: debug_limit - total_polys]

			frames.append(gdf)
			total_polys += len(gdf)
		except Exception as error:
			print(f"⚠️ Failed to load {path}: {error}")

	if not frames:
		raise RuntimeError("SLS load failed: no valid GeoDataFrames created.")

	print("🔗 Concatenating all SLS polygons...")
	sls_gdf = gpd.GeoDataFrame(pd.concat(frames, ignore_index=True), crs=POINTS_CRS)
	sls_gdf.set_geometry("geometry", inplace=True)

	del frames
	gc.collect()

	print(f"✓ Concatenated {len(sls_gdf):,} polygons")
	print("🧱 Building spatial index for SLS (this may take a while)...")
	index_started_at = time.time()
	_ = sls_gdf.sindex
	print(f"✓ Spatial index built in {time.time() - index_started_at:.1f}s")
	print(
		f"✅ SLS loaded: {len(sls_gdf):,} polygons from {len(files):,} files in {time.time() - started_at:.1f}s"
	)

	return sls_gdf


def load_regencies(cursor) -> dict:
	cursor.execute(
		"""
		SELECT r.id, r.long_code
		FROM regencies r
		INNER JOIN area_periods ap ON ap.id = r.area_period_id
		WHERE ap.is_active = 1
		"""
	)
	return {row["long_code"]: row["id"] for row in cursor.fetchall()}


def load_subdistricts(cursor) -> dict:
	cursor.execute(
		"""
		SELECT s.id, s.long_code
		FROM subdistricts s
		INNER JOIN area_periods ap ON ap.id = s.area_period_id
		WHERE ap.is_active = 1
		"""
	)
	return {row["long_code"]: row["id"] for row in cursor.fetchall()}


def load_villages(cursor) -> dict:
	cursor.execute(
		"""
		SELECT v.id, v.long_code
		FROM villages v
		INNER JOIN area_periods ap ON ap.id = v.area_period_id
		WHERE ap.is_active = 1
		"""
	)
	return {row["long_code"]: row["id"] for row in cursor.fetchall()}


def load_sls(cursor) -> dict:
	cursor.execute(
		"""
		SELECT s.id, s.long_code
		FROM sls s
		INNER JOIN area_periods ap ON ap.id = s.area_period_id
		WHERE ap.is_active = 1
		"""
	)
	return {row["long_code"]: row["id"] for row in cursor.fetchall()}


def load_all_area_lookups(cursor) -> tuple:
	print("📚 Loading area lookup tables...")
	started_at = time.time()

	regencies = load_regencies(cursor)
	subdistricts = load_subdistricts(cursor)
	villages = load_villages(cursor)
	sls = load_sls(cursor)

	print(
		f"✅ Loaded {len(regencies):,} regencies, {len(subdistricts):,} subdistricts, "
		f"{len(villages):,} villages, {len(sls):,} sls in {time.time() - started_at:.1f}s"
	)

	return regencies, subdistricts, villages, sls


def get_active_area_period_version(cursor) -> str:
	cursor.execute("SELECT period_version FROM area_periods WHERE is_active = 1 LIMIT 1")
	row = cursor.fetchone()
	if not row or row.get("period_version") is None:
		raise RuntimeError("No active area_periods row found (is_active = 1).")
	return str(row["period_version"])


def get_sls_dir_for_period_version(period_version: str) -> str:
	return os.path.join(GEOJSON_BASE_DIR, str(period_version), "sls_by_subdistrict")


def derive_ids_from_sls_code(
	sls_code: str,
	regencies_lookup: dict,
	subdistricts_lookup: dict,
	villages_lookup: dict,
	sls_lookup: dict,
):
	regency_code = sls_code[: ID_SLICE["regency"]] if ID_SLICE.get("regency") else None
	subdistrict_code = sls_code[: ID_SLICE["subdistrict"]] if ID_SLICE.get("subdistrict") else None
	village_code = sls_code[: ID_SLICE["village"]] if ID_SLICE.get("village") else None
	sls_code_full = sls_code + ID_SLICE.get("sls_suffix", "")

	regency_id = regencies_lookup.get(regency_code)
	subdistrict_id = subdistricts_lookup.get(subdistrict_code)
	village_id = villages_lookup.get(village_code)
	sls_id = sls_lookup.get(sls_code_full)

	return regency_id, subdistrict_id, village_id, sls_id


def validate_table_name(table_name: str) -> str:
	if table_name not in TABLES_TO_PROCESS:
		raise ValueError(f"Invalid table name: {table_name}. Allowed tables: {TABLES_TO_PROCESS}")
	return table_name


def get_total_rows_to_process(cursor, table_name: str) -> int:
	validated_table = validate_table_name(table_name)
	query = f"""
		SELECT COUNT(*) AS total
		FROM {validated_table}
		WHERE deleted_at IS NULL
	"""
	cursor.execute(query)
	result = cursor.fetchone()
	return result["total"] if result else 0


def fetch_batch(cursor, table_name: str, last_id: str | None, limit: int):
	validated_table = validate_table_name(table_name)

	query = f"""
		SELECT id, latitude, longitude
		FROM {validated_table}
		WHERE deleted_at IS NULL
		  AND (%s IS NULL OR id > %s)
		ORDER BY id
		LIMIT %s
	"""
	cursor.execute(query, (last_id, last_id, limit))
	return cursor.fetchall()


def update_rows_batch(cursor, table_name: str, rows: list[tuple]):
	if not rows:
		return 0

	validated_table = validate_table_name(table_name)

	update_query = f"""
		UPDATE {validated_table}
		SET regency_id = %s,
			subdistrict_id = %s,
			village_id = %s,
			sls_id = %s
		WHERE id = %s
	"""

	cursor.executemany(update_query, rows)
	return cursor.rowcount


def build_points_gdf(records) -> gpd.GeoDataFrame:
	df = pd.DataFrame(records)
	if df.empty:
		return gpd.GeoDataFrame(columns=["id", "latitude", "longitude", "geometry"], crs=POINTS_CRS)

	df["valid"] = df["latitude"].notna() & df["longitude"].notna()
	df = df[df["valid"]].copy()
	if df.empty:
		return gpd.GeoDataFrame(columns=["id", "latitude", "longitude", "geometry"], crs=POINTS_CRS)

	df["geometry"] = [Point(lon, lat) for lat, lon in zip(df["latitude"], df["longitude"])]
	return gpd.GeoDataFrame(
		df[["id", "latitude", "longitude", "geometry"]],
		geometry="geometry",
		crs=POINTS_CRS,
	)


def join_points_to_sls_chunked(points_gdf: gpd.GeoDataFrame, sls_gdf: gpd.GeoDataFrame, chunk_size: int) -> pd.DataFrame:
	total_points = len(points_gdf)
	if total_points == 0:
		return pd.DataFrame(columns=["id", "sls_base"])

	chunks = range(0, total_points, chunk_size)
	out_frames = []
	matched_count = 0
	started_at = time.time()

	for index in tqdm(chunks, desc="📍 Matching points→SLS", unit="chunk"):
		subset = points_gdf.iloc[index:index + chunk_size]
		joined = gpd.sjoin(subset, sls_gdf[["__filename", "geometry"]], predicate="within", how="left")
		joined = joined[["id", "__filename"]].rename(columns={"__filename": "sls_base"})
		matched_count += joined["sls_base"].notna().sum()
		out_frames.append(joined)

	print(f"🔢 Matched {matched_count:,}/{total_points:,} points in {time.time() - started_at:.1f}s")
	return pd.concat(out_frames, ignore_index=True) if out_frames else pd.DataFrame(columns=["id", "sls_base"])


def process_table(cursor, conn, table_name: str, sls_gdf: gpd.GeoDataFrame, regencies_lookup: dict, subdistricts_lookup: dict, villages_lookup: dict, sls_lookup: dict):
	print(f"\n{'=' * 60}")
	print(f"🎯 Processing table: {table_name}")
	print(f"{'=' * 60}")

	total_target = get_total_rows_to_process(cursor, table_name)
	print(f"🎯 Full scan target: {total_target:,} rows in {table_name}")

	processed = 0
	updated_total = 0
	rolling_matched_points = 0
	batch_index = 0
	last_id = None

	while True:
		batch_index += 1
		batch_started_at = time.time()

		print(f"\n📦 Fetching {table_name} batch #{batch_index} (LIMIT {BATCH_SIZE_DB}) ...")
		rows = fetch_batch(cursor, table_name, last_id, BATCH_SIZE_DB)
		if not rows:
			print(f"✅ No more rows found in {table_name}.")
			break

		last_id = rows[-1]["id"]
		points_gdf = build_points_gdf(rows)
		print(f"🧩 Valid points in this batch: {len(points_gdf):,} / {len(rows):,}")

		if len(points_gdf) == 0:
			processed += len(rows)
			continue

		joined_df = join_points_to_sls_chunked(points_gdf, sls_gdf, CHUNK_SIZE_JOIN)

		updates = []
		sls_map = dict(zip(joined_df["id"].tolist(), joined_df["sls_base"].tolist()))
		matched_in_batch = 0

		for record in rows:
			business_id = record["id"]
			sls_base = sls_map.get(business_id)
			if sls_base is None or pd.isna(sls_base):
				continue

			regency_id, subdistrict_id, village_id, sls_id = derive_ids_from_sls_code(
				str(sls_base),
				regencies_lookup,
				subdistricts_lookup,
				villages_lookup,
				sls_lookup,
			)
			updates.append((regency_id, subdistrict_id, village_id, sls_id, business_id))
			matched_in_batch += 1

		updated = update_rows_batch(cursor, table_name, updates)
		conn.commit()

		processed += len(rows)
		updated_total += updated
		rolling_matched_points += matched_in_batch

		batch_duration = time.time() - batch_started_at
		rate = len(rows) / batch_duration if batch_duration > 0 else 0.0

		print(
			f"✅ {table_name} Batch #{batch_index}: {updated:,}/{len(rows):,} updated in {batch_duration:.1f}s "
			f"— {rate:,.0f} rows/s"
		)
		print(f"📊 {table_name} Processed so far: {processed:,} | Matched so far: {rolling_matched_points:,}")

	return {
		"table": table_name,
		"processed": processed,
		"updated": updated_total,
		"matched_points": rolling_matched_points,
	}


def main():
	global_started_at = time.time()

	print("🔌 Connecting to DB...")
	conn = mysql.connector.connect(**DB)
	cursor = conn.cursor(dictionary=True)

	active_version = get_active_area_period_version(cursor)
	sls_dir = get_sls_dir_for_period_version(active_version)
	print(f"🗂️ Active area period version: {active_version}")
	print(f"📁 Using SLS folder: {sls_dir}")

	regencies_lookup, subdistricts_lookup, villages_lookup, sls_lookup = load_all_area_lookups(cursor)
	sls_gdf = load_all_sls(sls_dir, DEBUG_SLS_LIMIT if DEBUG_MODE else None)

	results = []
	for table_name in TABLES_TO_PROCESS:
		try:
			result = process_table(
				cursor,
				conn,
				table_name,
				sls_gdf,
				regencies_lookup,
				subdistricts_lookup,
				villages_lookup,
				sls_lookup,
			)
			results.append(result)
		except Exception as error:
			print(f"❌ Error processing table {table_name}: {error}")

	total_duration = time.time() - global_started_at
	print("\n" + "=" * 80)
	print("🎉 ALL TABLES COMPLETED")
	print("=" * 80)

	total_processed = sum(result["processed"] for result in results)
	total_updated = sum(result["updated"] for result in results)
	total_matched = sum(result["matched_points"] for result in results)

	for result in results:
		print(
			f"📋 {result['table']:<20}: {result['processed']:>8,} processed | "
			f"{result['updated']:>8,} updated | {result['matched_points']:>8,} matched"
		)

	print("-" * 80)
	print(
		f"📈 TOTAL ACROSS ALL TABLES: {total_processed:>8,} processed | "
		f"{total_updated:>8,} updated | {total_matched:>8,} matched"
	)
	print(f"⏱️ Total execution time: {total_duration / 60:.2f} minutes")

	cursor.close()
	conn.close()


if __name__ == "__main__":
	main()
