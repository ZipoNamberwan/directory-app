#!/usr/bin/env python3
"""
Fast Nearby Business Finder

This script efficiently finds businesses within a specified radius using R-tree spatial indexing.
It loads all businesses from supplement_business and market_business tables and for each business,
finds all other businesses within 50 meters that belong to different users.

Features:
- R-tree spatial indexing for O(log n) spatial queries instead of O(n²)
- Support for both supplement and market business tables
- Configurable radius (default: 50 meters)
- Efficient filtering by different user_id
- Detailed output with distances and business information

Requirements:
    - mysql-connector-python
    - python-dotenv
    - geopy
    - rtree
    - shapely

Install with: pip install mysql-connector-python python-dotenv geopy rtree shapely
"""

import os
import sys
from typing import List, Dict, Tuple, Any
from dataclasses import dataclass
from datetime import datetime

import mysql.connector
from dotenv import load_dotenv
from geopy.distance import geodesic
from rtree import index
from shapely.geometry import Point

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION
# =====================================================================

# Search radius in meters
RADIUS_METERS = 50

# Debug mode - set to True to limit businesses for testing
DEBUG_MODE = True
DEBUG_LIMIT = 1000000  # Number of businesses to process in debug mode

# Database connection settings
DB_CONFIG = {
    'host': os.getenv('DB_MAIN_HOST', 'localhost'),
    'port': int(os.getenv('DB_MAIN_PORT', 3306)),
    'user': os.getenv('DB_MAIN_USERNAME', 'root'),
    'password': os.getenv('DB_MAIN_PASSWORD', ''),
    'database': os.getenv('DB_MAIN_DATABASE', 'database'),
    'charset': 'utf8mb4'
}

# Business tables configuration
BUSINESS_TABLES = [
    {
        'table_name': 'supplement_business',
        'business_type': 'supplement'
    },
    # Note: Uncomment the market_business if it exists and has the same structure
    # {
    #     'table_name': 'market_business',
    #     'business_type': 'market'
    # }
]

# =====================================================================
# DATA MODELS
# =====================================================================

@dataclass
class Business:
    """Business data model"""
    id: str  # Changed from int to str for UUID support
    name: str
    owner: str
    latitude: float
    longitude: float
    user_id: str  # Changed from int to str for UUID support
    business_type: str
    address: str = ""
    
    def __post_init__(self):
        # Normalize text fields
        self.name = self.name or ""
        self.owner = self.owner or ""
        self.address = self.address or ""

@dataclass
class NearbyBusinessResult:
    """Result for nearby business search"""
    source_business: Business
    nearby_businesses: List[Tuple[Business, float]]  # (business, distance_meters)

# =====================================================================
# GEOGRAPHIC UTILITIES
# =====================================================================

class GeoUtils:
    """Geographic utility functions"""
    
    @staticmethod
    def calculate_distance(lat1: float, lng1: float, lat2: float, lng2: float) -> float:
        """Calculate distance between two points in meters"""
        try:
            point1 = (lat1, lng1)
            point2 = (lat2, lng2)
            return geodesic(point1, point2).meters
        except Exception:
            return float('inf')
    
    @staticmethod
    def degrees_to_meters_approx(lat: float, degrees: float) -> float:
        """
        Approximate conversion from degrees to meters at given latitude
        Used for creating bounding boxes for R-tree queries
        """
        # At equator: 1 degree ≈ 111,320 meters
        # Longitude distance varies by latitude: cos(lat) * 111,320
        lat_rad = lat * 3.14159 / 180
        meters_per_degree_lat = 111320
        meters_per_degree_lng = 111320 * abs(cos(lat_rad))
        
        # Use the smaller value to ensure we don't miss any points
        return degrees / min(meters_per_degree_lat, meters_per_degree_lng)
    
    @staticmethod
    def meters_to_degrees_approx(lat: float, meters: float) -> float:
        """
        Approximate conversion from meters to degrees at given latitude
        Used for creating bounding boxes for R-tree queries
        """
        # At equator: 1 degree ≈ 111,320 meters
        lat_rad = lat * 3.14159 / 180
        meters_per_degree_lat = 111320
        meters_per_degree_lng = 111320 * abs(cos(lat_rad))
        
        # Use the smaller value to ensure we capture all points within radius
        return meters / min(meters_per_degree_lat, meters_per_degree_lng)

def cos(x):
    """Simple cosine approximation"""
    import math
    return math.cos(x)

# =====================================================================
# SPATIAL INDEX MANAGER
# =====================================================================

class SpatialIndex:
    """Manages R-tree spatial index for fast geographic queries"""
    
    def __init__(self):
        self.idx = index.Index()
        self.businesses = {}  # string_id -> Business object
        self.id_mapping = {}  # string_id -> integer_index
        self.reverse_mapping = {}  # integer_index -> string_id
        self.next_index = 0
        
    def insert_business(self, business: Business):
        """Insert a business into the spatial index"""
        # Convert string ID to integer index for R-tree
        if business.id not in self.id_mapping:
            self.id_mapping[business.id] = self.next_index
            self.reverse_mapping[self.next_index] = business.id
            self.next_index += 1
        
        int_id = self.id_mapping[business.id]
        
        # Store business
        self.businesses[business.id] = business
        
        # Insert into R-tree index using integer ID
        # R-tree expects (minx, miny, maxx, maxy) bounding box
        # For points, min and max are the same
        self.idx.insert(
            int_id, 
            (business.longitude, business.latitude, business.longitude, business.latitude)
        )
    
    def find_nearby_businesses(self, center_business: Business, radius_meters: float) -> List[Tuple[Business, float]]:
        """
        Find all businesses within radius of center business
        Returns list of (business, distance_meters) tuples
        """
        # Convert radius to approximate degrees for bounding box
        radius_degrees = GeoUtils.meters_to_degrees_approx(center_business.latitude, radius_meters)
        
        # Create bounding box
        min_lat = center_business.latitude - radius_degrees
        max_lat = center_business.latitude + radius_degrees
        min_lng = center_business.longitude - radius_degrees
        max_lng = center_business.longitude + radius_degrees
        
        # Query R-tree for candidates within bounding box
        candidate_int_ids = list(self.idx.intersection((min_lng, min_lat, max_lng, max_lat)))
        
        # Filter candidates by exact distance and different user_id
        nearby_businesses = []
        
        for int_id in candidate_int_ids:
            # Convert integer ID back to string ID
            business_id = self.reverse_mapping[int_id]
            candidate_business = self.businesses[business_id]
            
            # Skip if same business
            if candidate_business.id == center_business.id:
                continue
                
            # Skip if same user
            if candidate_business.user_id == center_business.user_id:
                continue
            
            # Calculate exact distance
            distance = GeoUtils.calculate_distance(
                center_business.latitude, center_business.longitude,
                candidate_business.latitude, candidate_business.longitude
            )
            
            # Include if within radius
            if distance <= radius_meters:
                nearby_businesses.append((candidate_business, distance))
        
        # Sort by distance
        nearby_businesses.sort(key=lambda x: x[1])
        
        return nearby_businesses

# =====================================================================
# DATABASE MANAGER
# =====================================================================

class DatabaseManager:
    """Handles database operations"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.connection = None
    
    def connect(self):
        """Establish database connection"""
        try:
            print(f"🔗 Connecting to database: {self.config['database']}")
            self.connection = mysql.connector.connect(**self.config)
            print("✓ Database connected successfully")
        except Exception as e:
            print(f"❌ Database connection failed: {e}")
            raise
    
    def disconnect(self):
        """Close database connection"""
        if self.connection:
            self.connection.close()
            print("✓ Database connection closed")
    
    def get_businesses_from_table(self, table_name: str, business_type: str) -> List[Business]:
        """Fetch businesses from a specific table"""
        # Add LIMIT clause if debug mode is enabled
        limit_clause = f"LIMIT {DEBUG_LIMIT}" if DEBUG_MODE else ""
        
        query = f"""
        SELECT 
            id,
            name,
            owner,
            address,
            latitude,
            longitude,
            user_id
        FROM {table_name}
        WHERE latitude IS NOT NULL 
            AND longitude IS NOT NULL
            AND deleted_at IS NULL
        ORDER BY created_at DESC
        {limit_clause}
        """
        
        debug_info = f" (DEBUG: limiting to {DEBUG_LIMIT} records)" if DEBUG_MODE else ""
        print(f"📊 Loading businesses from {table_name}{debug_info}...")
        
        cursor = self.connection.cursor(dictionary=True)
        cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        
        businesses = []
        for row in results:
            business = Business(
                id=str(row['id']),  # Ensure string type
                name=row['name'] or "",
                owner=row['owner'] or "",
                latitude=float(row['latitude']),
                longitude=float(row['longitude']),
                user_id=str(row['user_id']),  # Ensure string type
                business_type=business_type,
                address=row['address'] or ""
            )
            businesses.append(business)
        
        print(f"✓ Loaded {len(businesses)} businesses from {table_name}")
        return businesses
    
    def get_all_businesses(self, table_configs: List[Dict[str, str]]) -> List[Business]:
        """Load businesses from all configured tables"""
        all_businesses = []
        
        for config in table_configs:
            businesses = self.get_businesses_from_table(
                config['table_name'], 
                config['business_type']
            )
            all_businesses.extend(businesses)
        
        return all_businesses

# =====================================================================
# MAIN FINDER ENGINE
# =====================================================================

class NearbyBusinessFinder:
    """Main engine for finding nearby businesses"""
    
    def __init__(self, radius_meters: float = RADIUS_METERS):
        self.radius_meters = radius_meters
        self.db_manager = DatabaseManager(DB_CONFIG)
        self.spatial_index = SpatialIndex()
    
    def run_search(self):
        """Execute the complete nearby business search"""
        try:
            print("🔍 Starting Fast Nearby Business Search")
            print("=" * 60)
            print(f"Configuration:")
            print(f"  - Search radius: {self.radius_meters} meters")
            print(f"  - Tables: {[config['table_name'] for config in BUSINESS_TABLES]}")
            print("-" * 60)
            
            # Connect to database
            self.db_manager.connect()
            
            # Load all businesses
            print("📊 Loading businesses from database...")
            all_businesses = self.db_manager.get_all_businesses(BUSINESS_TABLES)
            
            if not all_businesses:
                print("⚠️ No businesses found. Exiting.")
                return
            
            print(f"✓ Total businesses loaded: {len(all_businesses)}")
            
            # Build spatial index
            print("🏗️ Building spatial index...")
            for business in all_businesses:
                self.spatial_index.insert_business(business)
            print("✓ Spatial index built successfully")
            
            # Find nearby businesses for each business
            print(f"\n🔍 Searching for nearby businesses...")
            
            total_matches = 0
            businesses_with_matches = 0
            
            for i, business in enumerate(all_businesses):
                if i % 100 == 0:
                    print(f"  Progress: {i}/{len(all_businesses)} businesses processed")
                
                # Find nearby businesses
                nearby_businesses = self.spatial_index.find_nearby_businesses(
                    business, self.radius_meters
                )
                
                if nearby_businesses:
                    businesses_with_matches += 1
                    total_matches += len(nearby_businesses)
                    
                    # Just print the count of nearby businesses
                    print(f"📍 {business.name} (ID: {business.id}) → {len(nearby_businesses)} nearby businesses")
            
            print(f"\n" + "=" * 60)
            print("✅ Search completed successfully!")
            print(f"📊 Summary:")
            print(f"  - Total businesses analyzed: {len(all_businesses)}")
            print(f"  - Businesses with nearby matches: {businesses_with_matches}")
            print(f"  - Total nearby business pairs found: {total_matches}")
            print(f"  - Average matches per business: {total_matches / len(all_businesses):.2f}")
            
        except Exception as e:
            print(f"\n❌ Error during search: {e}")
            raise
        finally:
            self.db_manager.disconnect()

# =====================================================================
# MAIN EXECUTION
# =====================================================================

def main():
    """Main function"""
    try:
        print("🚀 Fast Nearby Business Finder")
        print(f"🎯 Searching for businesses within {RADIUS_METERS}m radius")
        print(f"⚡ Using R-tree spatial indexing for performance")
        if DEBUG_MODE:
            print(f"🐛 DEBUG MODE: Processing only {DEBUG_LIMIT:,} businesses for testing")
        print("")
        
        finder = NearbyBusinessFinder(RADIUS_METERS)
        finder.run_search()
        
        return 0
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    exit(main())
