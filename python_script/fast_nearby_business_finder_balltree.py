#!/usr/bin/env python3
"""
Fast Nearby Business Finder (Ball Tree Version@dataclass
class Business:
    Business data model
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
        self.address = self.address or ""ficiently finds businesses within a specified radius using Ball Tree spatial indexing.
It loads all businesses from supplement_business and market_business tables and for each business,
finds all other businesses within 50 meters that belong to different users.

Features:
- Ball Tree spatial indexing for fast nearest neighbor queries
- Support for both supplement and market business tables
- Configurable radius (default: 50 meters)
- Efficient filtering by different user_id
- Detailed output with distances and business information

Requirements:
    - mysql-connector-python
    - python-dotenv
    - scikit-learn
    - numpy

Install with: pip install mysql-connector-python python-dotenv scikit-learn numpy
"""

import os
import sys
import math
from typing import List, Dict, Tuple, Any
from dataclasses import dataclass
from datetime import datetime

import mysql.connector
import numpy as np
from dotenv import load_dotenv
from sklearn.neighbors import BallTree

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION
# =====================================================================

# Search radius in meters
RADIUS_METERS = 50

# Debug mode - set to True to limit businesses for testing
DEBUG_MODE = False
DEBUG_LIMIT = 10000  # Number of businesses to process in debug mode

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
    id: int
    name: str
    owner: str
    latitude: float
    longitude: float
    user_id: int
    business_type: str
    address: str = ""
    
    def __post_init__(self):
        # Normalize text fields
        self.name = self.name or ""
        self.owner = self.owner or ""
        self.address = self.address or ""

# =====================================================================
# GEOGRAPHIC UTILITIES
# =====================================================================

class GeoUtils:
    """Geographic utility functions"""
    
    @staticmethod
    def haversine_distance(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
        """
        Calculate the great circle distance between two points 
        on the earth (specified in decimal degrees) using Haversine formula
        Returns distance in meters
        """
        # Convert decimal degrees to radians
        lat1, lon1, lat2, lon2 = map(math.radians, [lat1, lon1, lat2, lon2])
        
        # Haversine formula
        dlat = lat2 - lat1
        dlon = lon2 - lon1
        a = math.sin(dlat/2)**2 + math.cos(lat1) * math.cos(lat2) * math.sin(dlon/2)**2
        c = 2 * math.asin(math.sqrt(a))
        
        # Radius of earth in meters
        r = 6371000
        
        return c * r
    
    @staticmethod
    def degrees_to_radians(degrees: float) -> float:
        """Convert degrees to radians"""
        return degrees * math.pi / 180

# =====================================================================
# BALL TREE SPATIAL INDEX
# =====================================================================

class BallTreeSpatialIndex:
    """Manages Ball Tree spatial index for fast geographic queries"""
    
    def __init__(self):
        self.businesses = []  # List of Business objects
        self.coordinates = []  # List of (lat, lon) in radians
        self.tree = None
        
    def add_business(self, business: Business):
        """Add a business to the index"""
        self.businesses.append(business)
        # Convert to radians for haversine distance
        lat_rad = GeoUtils.degrees_to_radians(business.latitude)
        lon_rad = GeoUtils.degrees_to_radians(business.longitude)
        self.coordinates.append([lat_rad, lon_rad])
    
    def build_index(self):
        """Build the Ball Tree index"""
        if not self.coordinates:
            raise ValueError("No businesses added to index")
        
        print("🌳 Building Ball Tree spatial index...")
        # Convert to numpy array
        coords_array = np.array(self.coordinates)
        
        # Build Ball Tree with haversine metric (for geographic data)
        self.tree = BallTree(coords_array, metric='haversine')
        print("✓ Ball Tree index built successfully")
    
    def find_nearby_businesses(self, center_business: Business, radius_meters: float) -> List[Tuple[Business, float]]:
        """
        Find all businesses within radius of center business
        Returns list of (business, distance_meters) tuples
        """
        if self.tree is None:
            raise ValueError("Index not built. Call build_index() first.")
        
        # Convert center coordinates to radians
        center_lat_rad = GeoUtils.degrees_to_radians(center_business.latitude)
        center_lon_rad = GeoUtils.degrees_to_radians(center_business.longitude)
        center_point = np.array([[center_lat_rad, center_lon_rad]])
        
        # Convert radius from meters to radians (for haversine)
        # Earth radius = 6371000 meters
        radius_radians = radius_meters / 6371000
        
        # Query Ball Tree for nearby points
        indices = self.tree.query_radius(center_point, r=radius_radians)[0]
        
        # Filter results
        nearby_businesses = []
        
        for idx in indices:
            candidate_business = self.businesses[idx]
            
            # Skip if same business
            if candidate_business.id == center_business.id:
                continue
                
            # Skip if same user
            if candidate_business.user_id == center_business.user_id:
                continue
            
            # Calculate exact distance
            distance = GeoUtils.haversine_distance(
                center_business.latitude, center_business.longitude,
                candidate_business.latitude, candidate_business.longitude
            )
            
            # Double-check distance (Ball Tree uses approximation)
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
        self.spatial_index = BallTreeSpatialIndex()
    
    def run_search(self):
        """Execute the complete nearby business search"""
        try:
            print("🔍 Starting Fast Nearby Business Search (Ball Tree)")
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
                self.spatial_index.add_business(business)
            
            self.spatial_index.build_index()
            
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
            import traceback
            traceback.print_exc()
            raise
        finally:
            self.db_manager.disconnect()

# =====================================================================
# MAIN EXECUTION
# =====================================================================

def main():
    """Main function"""
    try:
        print("🚀 Fast Nearby Business Finder (Ball Tree Version)")
        print(f"🎯 Searching for businesses within {RADIUS_METERS}m radius")
        print(f"⚡ Using Ball Tree spatial indexing for performance")
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