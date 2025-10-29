#!/usr/bin/env python3
"""
Business Duplicate Detector using Spatial Search

This script efficiently finds businesses within a specified radius using R-tree spatial indexing
and then analyzes them for potential duplicates using text similarity comparison with configurable rules.

It loads all businesses from supplement_business and market_business tables and for each business,
finds all other businesses within 50 meters that belong to different users, then compares them
to detect potential duplicate businesses using customizable detection rules.

Usage:
- Set SLS_ID_FILTER = "123456" to filter businesses by specific SLS ID
- Set SLS_ID_FILTER = None to process all businesses (default)
- Set USE_COMMON_WORDS_FILTERING = True to filter common words during comparison (default)
- Set USE_COMMON_WORDS_FILTERING = False to use full text comparison without filtering
- Customize common words in 'common_words.csv' file to improve comparison accuracy

Features:
- R-tree spatial indexing for O(log n) spatial queries instead of O(n²)
- Support for both supplement and market business tables
- Configurable radius (default: 50 meters)
- Text similarity analysis for duplicate detection
- String normalization for better comparison accuracy
- **Common words filtering** - ignores common business words (jual, toko, warung, etc.) during comparison
- Configurable similarity thresholds
- Classification of duplicates (Strong, Weak, Not duplicate)

Duplicate Detection Algorithm:
The script uses a refined algorithm for detecting business duplicates:
1. If name and owner have similarity higher than threshold → strong_duplicate
2. If name is high similarity but owner is low similarity → not_duplicate
3. If name is low similarity but owner is high similarity → not_duplicate
4. If name is high similarity but owner is empty → advanced step (common words filtering applied)
5. If name is low similarity but owner is empty → not_duplicate

Advanced Step (Rule 4):
When names have high similarity but owner information is missing, the system applies common words
filtering to remove generic business terms (toko, warung, jual, etc.) and re-evaluates similarity.
This helps distinguish between truly similar businesses and those that only share common prefixes.

Configurable Rules:
You can customize how the following conditions are classified:
1. Both name & owner similarity >= threshold → Configurable result
2. Name similarity >= threshold but owner similarity < threshold → Configurable result  
3. Name similarity < threshold but owner similarity >= threshold → Configurable result
4. Name similarity >= threshold and one owner empty → Configurable result
5. Both owners empty and name similarity >= threshold → Configurable result
6. All other cases → Configurable result

The current implementation uses a hardcoded balanced approach for duplicate detection.

Requirements:
    - mysql-connector-python
    - python-dotenv
    - geopy
    - rtree
    - shapely
    - difflib (built-in)

Install with: pip install mysql-connector-python python-dotenv geopy rtree shapely
"""

import os
import sys
import time
import re
import string
import difflib
from typing import List, Dict, Tuple, Any, Optional
from dataclasses import dataclass
from datetime import datetime
import pytz

import mysql.connector
from dotenv import load_dotenv
from rtree import index
from shapely.geometry import Point

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# Timezone configuration
JAKARTA_TIMEZONE = pytz.timezone('Asia/Jakarta')

def get_jakarta_now():
    """Get current timestamp in Jakarta timezone"""
    return datetime.now(JAKARTA_TIMEZONE)

# =====================================================================
# CONFIGURATION
# =====================================================================

# Search radius in meters
RADIUS_METERS = 70

# SLS ID Filter - set to specific SLS ID to filter businesses, or None to process all
SLS_ID_FILTER = None  # Example: "123456" to filter by specific SLS ID

# Debug mode - set to True to limit businesses for testing
DEBUG_MODE = False
DEBUG_LIMIT = 2000000  # Number of businesses to process in debug mode

# Duplicate detection settings
SIMILARITY_THRESHOLD = 0.75  # Minimum similarity score (0.0 - 1.0) to consider as similar

# Common words filtering configuration
USE_COMMON_WORDS_FILTERING = True  # Set to False to disable common words filtering in text comparison

# Duplicate detection rule configuration
DUPLICATE_RULES = {
    # Rule 1: Name similarity >= TH AND Owner similarity >= TH
    'both_high_similarity': 'strong_duplicate',  # Options: 'strong_duplicate', 'weak_duplicate', 'not_duplicate'
    
    # Rule 2: Name similarity >= TH but Owner similarity < TH (owner not empty)
    'name_high_owner_low': 'not_duplicate',  # Options: 'strong_duplicate', 'weak_duplicate', 'not_duplicate'
    
    # Rule 3: Name similarity < TH but Owner similarity >= TH (owner not empty)
    'name_low_owner_high': 'not_duplicate',  # Options: 'strong_duplicate', 'weak_duplicate', 'not_duplicate'
    
    # Rule 4: Name similarity >= TH and one owner empty
    'name_high_one_owner_empty': 'weak_duplicate',  # Options: 'strong_duplicate', 'weak_duplicate', 'not_duplicate'
    
    # Rule 5: Both owners empty, name similarity >= TH
    'both_owners_empty_name_high': 'weak_duplicate',  # Options: 'strong_duplicate', 'weak_duplicate', 'not_duplicate'
    
    # Default rule: All other cases
    'default': 'not_duplicate'  # Options: 'strong_duplicate', 'weak_duplicate', 'not_duplicate'
}

# Distance calculation configuration
CALCULATE_PRECISE_DISTANCE = True  # Set to True to calculate and store precise distances (slower but more accurate)

# Batch processing configuration
BATCH_UPDATE_SIZE = 1000  # Number of businesses to update in each batch for duplicate_scan_at

# Processing mode configuration
UPDATE_DUPLICATE_SCAN_IMMEDIATELY = True  # Set to True to update businesses in batches during processing, False for single batch update at the end

# Output mode configuration - choose where to save results
SAVE_RESULTS_TO_DATABASE = True  # Set to True to save results to duplicate_candidates table
SAVE_RESULTS_TO_FILE = False  # Set to True to save detailed results to CSV file for manual inspection

# Output file configuration (only used when SAVE_RESULTS_TO_FILE = True)
OUTPUT_FILENAME = "business_duplicate_detection_results.csv"  # Constant filename (overwrites previous results)
USE_TIMESTAMP_IN_FILENAME = False  # Set to True to append timestamp to filename
INCLUDE_NOT_DUPLICATES_IN_OUTPUT = False  # Set to True to include "not_duplicate" results in CSV output

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
        'table_name': 'market_business',
        'business_type': 'market'
    },
    {
        'table_name': 'supplement_business',
        'business_type': 'supplement'
    }
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
    sls_id: str  # SLS ID for the business
    business_type: str  # 'supplement' or 'market'
    address: str = ""
    project_id: str = ""  # Project ID for supplement businesses
    
    def __post_init__(self):
        # Normalize text fields
        self.name = self.name or ""
        self.owner = self.owner or ""
        self.address = self.address or ""
        self.project_id = self.project_id or ""

@dataclass
class DuplicateComparison:
    """Result of duplicate comparison between two businesses"""
    business_a: Business
    business_b: Business
    name_similarity: float
    owner_similarity: float
    duplicate_type: str  # "strong_duplicate", "weak_duplicate", "not_duplicate"
    confidence_score: float
    distance_meters: Optional[float] = None

@dataclass
class NearbyBusinessResult:
    """Result for nearby business search with duplicate analysis"""
    source_business: Business
    nearby_businesses: List[Business]
    duplicate_comparisons: List[DuplicateComparison]

# =====================================================================
# GEOGRAPHIC UTILITIES
# =====================================================================

class GeoUtils:
    """Geographic utility functions"""
    
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
# BUSINESS DATA UTILITIES
# =====================================================================

def extract_owner_from_name(name: str) -> tuple[str, str]:
    """
    Extract owner from business name for market businesses.
    
    Rules:
    - If name contains <owner> or (owner), extract owner and clean name
    - If no brackets/parentheses, owner is empty
    
    Examples:
    - "toko sembako <budi>" -> ("toko sembako", "budi")
    - "warung makan (siti)" -> ("warung makan", "siti")
    - "toko abc" -> ("toko abc", "")
    
    Returns:
        tuple: (cleaned_name, extracted_owner)
    """
    if not name:
        return "", ""
    
    import re
    
    # Look for owner in angle brackets <owner>
    angle_match = re.search(r'<([^>]+)>', name)
    if angle_match:
        owner = angle_match.group(1).strip()
        cleaned_name = re.sub(r'\s*<[^>]+>\s*', ' ', name).strip()
        return cleaned_name, owner
    
    # Look for owner in parentheses (owner)
    paren_match = re.search(r'\(([^)]+)\)', name)
    if paren_match:
        owner = paren_match.group(1).strip()
        cleaned_name = re.sub(r'\s*\([^)]+\)\s*', ' ', name).strip()
        return cleaned_name, owner
    
    # No owner found
    return name.strip(), ""

# =====================================================================
# TEXT NORMALIZATION AND SIMILARITY UTILITIES
# =====================================================================

class CommonWordsManager:
    """Manages common words filtering from CSV file"""
    
    _common_words = None  # Class variable to cache loaded words
    
    @classmethod
    def load_common_words(cls) -> set:
        """Load common words from CSV file, with caching"""
        if cls._common_words is not None:
            return cls._common_words
        
        cls._common_words = set()
        common_words_file = os.path.join(os.path.dirname(__file__), 'common_words.csv')
        
        try:
            import csv
            with open(common_words_file, 'r', encoding='utf-8') as f:
                reader = csv.reader(f)
                for row in reader:
                    if row:  # Skip empty rows
                        # Each row might contain multiple words, or just one word per row
                        for word in row:
                            if word.strip():  # Skip empty cells
                                cls._common_words.add(word.strip().lower())
            
            print(f"✓ Loaded {len(cls._common_words)} common words from {common_words_file}")
        except FileNotFoundError:
            print(f"⚠️ Common words file not found: {common_words_file}")
            print("   Creating default common words...")
            # Create default common words file
            default_words = ['jual', 'toko', 'warung', 'usaha', 'dagang', 'depot', 'kios', 'stan', 'lapak', 'counter']
            cls._create_default_common_words_file(common_words_file, default_words)
            cls._common_words = set(default_words)
        except Exception as e:
            print(f"⚠️ Error loading common words: {e}")
            print("   Using default common words...")
            cls._common_words = {'jual', 'toko', 'warung', 'usaha', 'dagang', 'depot', 'kios', 'stan', 'lapak', 'counter'}
        
        return cls._common_words
    
    @classmethod
    def _create_default_common_words_file(cls, filepath: str, words: List[str]):
        """Create default common words CSV file"""
        try:
            import csv
            with open(filepath, 'w', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow(['common_word'])  # Header
                for word in words:
                    writer.writerow([word])
            print(f"✓ Created default common words file: {filepath}")
        except Exception as e:
            print(f"⚠️ Failed to create default common words file: {e}")
    
    @classmethod
    def filter_common_words(cls, text: str) -> str:
        """
        Remove common words from text, but keep original if result would be empty
        
        Args:
            text: Input text to filter
            
        Returns:
            Filtered text, or original text if filtering would result in empty string
        """
        if not text:
            return ""
        
        common_words = cls.load_common_words()
        
        # Split text into words
        words = text.split()
        
        # Filter out common words
        filtered_words = [word for word in words if word.lower() not in common_words]
        
        # If all words were common words, return original text (fallback)
        if not filtered_words:
            return text
        
        # Return filtered text
        return ' '.join(filtered_words)

class TextUtils:
    """Text processing utilities for duplicate detection"""
    
    @staticmethod
    def normalize_text(text: str) -> str:
        """
        Normalize text for comparison:
        1. Convert to lowercase
        2. Remove punctuation and extra spaces
        3. Strip leading/trailing whitespace
        """
        if not text:
            return ""
        
        # Convert to lowercase
        text = text.lower()
        
        # Remove punctuation
        text = text.translate(str.maketrans('', '', string.punctuation))
        
        # Replace multiple spaces with single space and strip
        text = ' '.join(text.split())
        
        return text
    
    @staticmethod
    def calculate_similarity(text1: str, text2: str) -> float:
        """
        Calculate similarity between two text strings using SequenceMatcher
        Optionally filters out common words before comparison based on USE_COMMON_WORDS_FILTERING setting
        Returns a float between 0.0 (no similarity) and 1.0 (identical)
        """
        if USE_COMMON_WORDS_FILTERING:
            return TextUtils.calculate_similarity_with_filtering(text1, text2)
        else:
            return TextUtils.calculate_similarity_without_filtering(text1, text2)
    
    @staticmethod
    def calculate_similarity_without_filtering(text1: str, text2: str) -> float:
        """
        Calculate similarity between two text strings without common words filtering
        Returns a float between 0.0 (no similarity) and 1.0 (identical)
        """
        if not text1 and not text2:
            return 1.0  # Both empty strings are considered identical
        
        if not text1 or not text2:
            return 0.0  # One empty, one not empty
        
        # Normalize both texts
        norm_text1 = TextUtils.normalize_text(text1)
        norm_text2 = TextUtils.normalize_text(text2)
        
        # Use difflib.SequenceMatcher for similarity calculation
        similarity = difflib.SequenceMatcher(None, norm_text1, norm_text2).ratio()
        
        return similarity
    
    @staticmethod
    def calculate_similarity_with_filtering(text1: str, text2: str) -> float:
        """
        Calculate similarity between two text strings with common words filtering
        Returns a float between 0.0 (no similarity) and 1.0 (identical)
        """
        if not text1 and not text2:
            return 1.0  # Both empty strings are considered identical
        
        if not text1 or not text2:
            return 0.0  # One empty, one not empty
        
        # Normalize both texts
        norm_text1 = TextUtils.normalize_text(text1)
        norm_text2 = TextUtils.normalize_text(text2)
        
        # Filter out common words
        filtered_text1 = CommonWordsManager.filter_common_words(norm_text1)
        filtered_text2 = CommonWordsManager.filter_common_words(norm_text2)
        
        # Use difflib.SequenceMatcher for similarity calculation
        similarity = difflib.SequenceMatcher(None, filtered_text1, filtered_text2).ratio()
        
        return similarity
    
    @staticmethod
    def is_empty_or_whitespace(text: str) -> bool:
        """Check if text is empty or contains only whitespace"""
        return not text or text.strip() == ""

class DuplicateDetector:
    """Main duplicate detection logic with new algorithm"""
    
    def __init__(self, similarity_threshold: float = SIMILARITY_THRESHOLD):
        self.similarity_threshold = similarity_threshold
    
    def compare_businesses(self, business_a: Business, business_b: Business, 
                          distance_meters: Optional[float] = None) -> DuplicateComparison:
        """
        Compare two businesses and determine if they are duplicates using the new algorithm:
        
        New Algorithm Rules:
        1. If name and owner have similarity higher than threshold → strong_duplicate
        2. If name is high similarity but owner is low similarity → not_duplicate
        3. If name is low similarity but owner is high similarity → not_duplicate
        4. If name is high similarity but owner is empty → advanced step (common words removal)
        5. If name is low similarity but owner is empty → not_duplicate
        """
        
        # Calculate initial similarities (without common words filtering for first pass)
        name_similarity = TextUtils.calculate_similarity_without_filtering(business_a.name, business_b.name)
        owner_similarity = TextUtils.calculate_similarity_without_filtering(business_a.owner, business_b.owner)
        
        # Check if owners are empty
        owner_a_empty = TextUtils.is_empty_or_whitespace(business_a.owner)
        owner_b_empty = TextUtils.is_empty_or_whitespace(business_b.owner)
        any_owner_empty = owner_a_empty or owner_b_empty
        
        # Apply new algorithm rules
        duplicate_type = 'not_duplicate'
        confidence_score = 0.0
        
        if name_similarity >= self.similarity_threshold:
            if any_owner_empty:
                # Rule 4: Name is high similarity but owner is empty → advanced step
                # Use common words filtering for advanced comparison
                advanced_name_similarity = TextUtils.calculate_similarity_with_filtering(business_a.name, business_b.name)
                
                if advanced_name_similarity >= self.similarity_threshold:
                    duplicate_type = 'strong_duplicate'
                    confidence_score = advanced_name_similarity
                else:
                    duplicate_type = 'not_duplicate'
                    confidence_score = advanced_name_similarity * 0.5
            elif owner_similarity >= self.similarity_threshold:
                # Rule 1: Name and owner both have high similarity → strong_duplicate
                duplicate_type = 'strong_duplicate'
                confidence_score = (name_similarity + owner_similarity) / 2
            else:
                # Rule 2: Name is high similarity but owner is low similarity → not_duplicate
                duplicate_type = 'not_duplicate'
                confidence_score = max(name_similarity, owner_similarity) * 0.3
        else:
            if not any_owner_empty and owner_similarity >= self.similarity_threshold:
                # Rule 3: Name is low similarity but owner is high similarity → not_duplicate
                duplicate_type = 'not_duplicate'
                confidence_score = max(name_similarity, owner_similarity) * 0.3
            else:
                # Rule 5: Name is low similarity but owner is empty → not_duplicate
                # Default: All other cases → not_duplicate
                duplicate_type = 'not_duplicate'
                confidence_score = max(name_similarity, owner_similarity) * 0.2
        
        return DuplicateComparison(
            business_a=business_a,
            business_b=business_b,
            name_similarity=name_similarity,
            owner_similarity=owner_similarity,
            duplicate_type=duplicate_type,
            confidence_score=confidence_score,
            distance_meters=distance_meters
        )

# =====================================================================
# VALIDATION UTILITIES
# =====================================================================

def calculate_precise_distance(lat1: float, lng1: float, lat2: float, lng2: float) -> float:
    """
    Calculate precise distance between two points in meters using Haversine formula
    Used for validation only
    """
    import math
    
    # Convert decimal degrees to radians
    lat1, lng1, lat2, lng2 = map(math.radians, [lat1, lng1, lat2, lng2])
    
    # Haversine formula
    dlat = lat2 - lat1
    dlng = lng2 - lng1
    a = math.sin(dlat/2)**2 + math.cos(lat1) * math.cos(lat2) * math.sin(dlng/2)**2
    c = 2 * math.asin(math.sqrt(a))
    
    # Radius of earth in meters
    r = 6371000
    
    return c * r

def save_results_to_csv(results: List[Dict[str, Any]], filename: str):
    """Save results to CSV file for manual inspection"""
    import csv
    
    output_dir = os.path.join(os.path.dirname(__file__), '..', 'backup', 'validation')
    os.makedirs(output_dir, exist_ok=True)
    output_path = os.path.join(output_dir, filename)
    
    with open(output_path, 'w', newline='', encoding='utf-8') as csvfile:
        if results:
            fieldnames = results[0].keys()
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            writer.writerows(results)
    
    print(f"📁 Results saved to: {output_path}")
    return output_path

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
    
    def find_nearby_businesses(self, center_business: Business, radius_meters: float) -> List[Business]:
        """
        Find all businesses within radius of center business
        Returns list of nearby businesses (no distance calculation)
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
        
        # Filter candidates by different user_id (no distance calculation)
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
                # Additional check for supplement businesses: if same user and same project_id, skip
                if (candidate_business.business_type == 'supplement' and 
                    center_business.business_type == 'supplement' and
                    candidate_business.project_id and 
                    center_business.project_id and
                    candidate_business.project_id == center_business.project_id):
                    continue
                # If users are same but different project_ids (or one/both project_ids are empty), continue processing
                elif candidate_business.business_type == 'supplement' and center_business.business_type == 'supplement':
                    pass  # Continue processing - same user but different projects
                else:
                    continue  # For non-supplement businesses, skip if same user
            
            # Add to nearby businesses (assuming R-tree bounding box is accurate enough)
            nearby_businesses.append(candidate_business)
        
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
    
    def get_businesses_from_table(self, table_name: str, business_type: str, only_unprocessed: bool = False) -> List[Business]:
        """
        Fetch businesses from a specific table
        Args:
            table_name: Name of the business table
            business_type: Type of business ('supplement' or 'market')
            only_unprocessed: If True, only return businesses with duplicate_scan_at IS NULL
        """
        # Add LIMIT clause if debug mode is enabled
        limit_clause = f"LIMIT {DEBUG_LIMIT}" if DEBUG_MODE else ""
        
        # Add SLS ID filter if specified
        sls_filter = f"AND sls_id = '{SLS_ID_FILTER}'" if SLS_ID_FILTER else ""
        
        # Add unprocessed filter if specified
        unprocessed_filter = "AND duplicate_scan_at IS NULL" if only_unprocessed else ""
        
        # Different query structure for market_business (no owner column)
        if business_type == 'market':
            query = f"""
            SELECT 
                id,
                name,
                address,
                latitude,
                longitude,
                user_id,
                sls_id
            FROM {table_name}
            WHERE latitude IS NOT NULL 
                AND longitude IS NOT NULL
                AND deleted_at IS NULL
                {unprocessed_filter}
                {sls_filter}
            ORDER BY created_at DESC
            {limit_clause}
            """
        else:
            # Standard query for supplement_business (has owner and project_id columns)
            query = f"""
            SELECT 
                id,
                name,
                owner,
                address,
                latitude,
                longitude,
                user_id,
                sls_id,
                project_id
            FROM {table_name}
            WHERE latitude IS NOT NULL 
                AND longitude IS NOT NULL
                AND deleted_at IS NULL
                {unprocessed_filter}
                {sls_filter}
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
            # Handle owner extraction based on business type
            if business_type == 'market':
                # Extract owner from name for market businesses
                cleaned_name, extracted_owner = extract_owner_from_name(row['name'] or "")
                name = cleaned_name
                owner = extracted_owner
            else:
                # Use direct owner field for supplement businesses
                name = row['name'] or ""
                owner = row['owner'] or ""
            
            business = Business(
                id=str(row['id']),  # Ensure string type
                name=name,
                owner=owner,
                latitude=float(row['latitude']),
                longitude=float(row['longitude']),
                user_id=str(row['user_id']),  # Ensure string type
                sls_id=str(row['sls_id']) if row['sls_id'] else "",  # Ensure string type
                business_type=business_type,
                address=row['address'] or "",
                project_id=str(row['project_id']) if business_type == 'supplement' and row.get('project_id') else ""
            )
            businesses.append(business)
        
        print(f"✓ Loaded {len(businesses)} businesses from {table_name}")
        return businesses
    
    def save_duplicate_candidate(self, comparison: 'DuplicateComparison') -> bool:
        """
        Save a single duplicate candidate to the database
        Returns True if successful, False otherwise
        """
        try:
            import uuid
            
            # Generate UUID for the record
            record_id = str(uuid.uuid4())
            
            # Map business_type to the correct table suffix for morph relationship
            center_type_mapping = {
                'supplement': 'App\\Models\\SupplementBusiness',
                'market': 'App\\Models\\MarketBusiness'
            }
            nearby_type_mapping = {
                'supplement': 'App\\Models\\SupplementBusiness', 
                'market': 'App\\Models\\MarketBusiness'
            }
            
            center_business_type = center_type_mapping.get(comparison.business_a.business_type, 'App\\Models\\SupplementBusiness')
            nearby_business_type = nearby_type_mapping.get(comparison.business_b.business_type, 'App\\Models\\SupplementBusiness')
            
            query = """
            INSERT INTO duplicate_candidates (
                id, center_business_id, center_business_type, nearby_business_id, nearby_business_type,
                center_business_name, nearby_business_name, center_business_owner, nearby_business_owner,
                name_similarity, owner_similarity, confidence_score, distance_meters, duplicate_status,
                center_business_latitude, center_business_longitude, 
                nearby_business_latitude, nearby_business_longitude,
                status, created_at, updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
            )
            """
            
            cursor = self.connection.cursor()
            cursor.execute(query, (
                record_id,
                comparison.business_a.id,
                center_business_type,
                comparison.business_b.id,
                nearby_business_type,
                comparison.business_a.name or "",
                comparison.business_b.name or "",
                comparison.business_a.owner or "",
                comparison.business_b.owner or "",
                comparison.name_similarity,
                comparison.owner_similarity,
                comparison.confidence_score,
                comparison.distance_meters or 0.0,
                comparison.duplicate_type,
                comparison.business_a.latitude,
                comparison.business_a.longitude,
                comparison.business_b.latitude,
                comparison.business_b.longitude,
                'notconfirmed',  # Default status
                get_jakarta_now(),  # created_at
                get_jakarta_now()   # updated_at
            ))
            
            self.connection.commit()  # Commit the transaction
            cursor.close()
            return True
            
        except Exception as e:
            print(f"⚠️ Error saving duplicate candidate: {e}")
            return False
    
    def get_all_businesses(self, table_configs: List[Dict[str, str]]) -> List[Business]:
        """Load ALL businesses from all configured tables (for spatial indexing)"""
        all_businesses = []
        
        for config in table_configs:
            businesses = self.get_businesses_from_table(
                config['table_name'], 
                config['business_type'],
                only_unprocessed=False  # Load all businesses for spatial index
            )
            all_businesses.extend(businesses)
        
        return all_businesses
    
    def get_unprocessed_businesses(self, table_configs: List[Dict[str, str]]) -> List[Business]:
        """Load only businesses that haven't been processed yet (duplicate_scan_at IS NULL)"""
        unprocessed_businesses = []
        
        for config in table_configs:
            businesses = self.get_businesses_from_table(
                config['table_name'], 
                config['business_type'],
                only_unprocessed=True  # Only load unprocessed businesses
            )
            unprocessed_businesses.extend(businesses)
        
        return unprocessed_businesses
    
    def update_single_duplicate_scan_at(self, business_id: str, business_type: str) -> bool:
        """
        Update duplicate_scan_at for a single business immediately
        Args:
            business_id: ID of the business to update
            business_type: Type of business ('supplement' or 'market')
        Returns:
            bool: True if successful, False if failed
        """
        try:
            cursor = self.connection.cursor()
            current_timestamp = get_jakarta_now()
            
            if business_type == 'supplement':
                table_name = 'supplement_business'
            else:
                table_name = 'market_business'
            
            query = f"UPDATE {table_name} SET duplicate_scan_at = %s WHERE id = %s"
            cursor.execute(query, (current_timestamp, business_id))
            self.connection.commit()
            cursor.close()
            return True
            
        except Exception as e:
            print(f"⚠️ Failed to update duplicate_scan_at for {business_type} business {business_id}: {e}")
            return False
    
    def batch_update_duplicate_scan_at(self, business_updates: List[Tuple[str, str]]) -> Tuple[int, int]:
        """
        Batch update duplicate_scan_at for multiple businesses in smaller chunks
        Args:
            business_updates: List of tuples (business_id, business_type)
        Returns:
            Tuple of (successful_updates, failed_updates)
        """
        if not business_updates:
            return 0, 0
        
        total_successful_updates = 0
        total_failed_updates = 0
        current_timestamp = get_jakarta_now()
        
        # Group updates by business type (table)
        supplement_ids = []
        market_ids = []
        
        for business_id, business_type in business_updates:
            if business_type == 'supplement':
                supplement_ids.append(business_id)
            else:
                market_ids.append(business_id)
        
        print(f"📝 Processing {len(business_updates)} businesses in batches of {BATCH_UPDATE_SIZE}")
        
        try:
            cursor = self.connection.cursor()
            
            # Process supplement_business updates in batches using WHERE id IN (...)
            if supplement_ids:
                print(f"🔄 Updating {len(supplement_ids)} supplement businesses...")
                batches_processed = 0
                
                for i in range(0, len(supplement_ids), BATCH_UPDATE_SIZE):
                    batch_ids = supplement_ids[i:i + BATCH_UPDATE_SIZE]
                    batches_processed += 1
                    
                    try:
                        # Create placeholders for the IN clause
                        placeholders = ','.join(['%s'] * len(batch_ids))
                        query = f"UPDATE supplement_business SET duplicate_scan_at = %s WHERE id IN ({placeholders})"
                        
                        # Execute with timestamp first, then all the IDs
                        cursor.execute(query, [current_timestamp] + batch_ids)
                        self.connection.commit()  # Commit each batch
                        
                        batch_size = len(batch_ids)
                        total_successful_updates += batch_size
                        
                        print(f"  ✓ Batch {batches_processed}: Updated {batch_size} supplement businesses "
                              f"({total_successful_updates}/{len(supplement_ids)} total)")
                        
                    except Exception as e:
                        print(f"  ⚠️ Error in supplement batch {batches_processed}: {e}")
                        total_failed_updates += len(batch_ids)
            
            # Process market_business updates in batches using WHERE id IN (...)
            if market_ids:
                print(f"🔄 Updating {len(market_ids)} market businesses...")
                batches_processed = 0
                
                for i in range(0, len(market_ids), BATCH_UPDATE_SIZE):
                    batch_ids = market_ids[i:i + BATCH_UPDATE_SIZE]
                    batches_processed += 1
                    
                    try:
                        # Create placeholders for the IN clause
                        placeholders = ','.join(['%s'] * len(batch_ids))
                        query = f"UPDATE market_business SET duplicate_scan_at = %s WHERE id IN ({placeholders})"
                        
                        # Execute with timestamp first, then all the IDs
                        cursor.execute(query, [current_timestamp] + batch_ids)
                        self.connection.commit()  # Commit each batch
                        
                        batch_size = len(batch_ids)
                        batch_successful = total_successful_updates + batch_size - len(supplement_ids)
                        total_successful_updates += batch_size
                        
                        print(f"  ✓ Batch {batches_processed}: Updated {batch_size} market businesses "
                              f"({batch_successful}/{len(market_ids)} total)")
                        
                    except Exception as e:
                        print(f"  ⚠️ Error in market batch {batches_processed}: {e}")
                        total_failed_updates += len(batch_ids)
            
            cursor.close()
            
            print(f"✅ Batch update completed:")
            print(f"  - Successfully updated: {total_successful_updates} businesses")
            if total_failed_updates > 0:
                print(f"  - Failed updates: {total_failed_updates} businesses")
            
        except Exception as e:
            print(f"❌ Fatal error in batch update duplicate_scan_at: {e}")
            total_failed_updates = len(business_updates)
            total_successful_updates = 0
        
        return total_successful_updates, total_failed_updates

# =====================================================================
# MAIN FINDER ENGINE
# =====================================================================

class NearbyBusinessFinder:
    """Main engine for finding nearby businesses and detecting duplicates"""
    
    def __init__(self, radius_meters: float = RADIUS_METERS, 
                 similarity_threshold: float = SIMILARITY_THRESHOLD):
        self.radius_meters = radius_meters
        self.db_manager = DatabaseManager(DB_CONFIG)
        self.spatial_index = SpatialIndex()
        self.duplicate_detector = DuplicateDetector(similarity_threshold)
    
    def run_search(self):
        """Execute the complete nearby business search"""
        start_time = time.time()
        
        try:
            print("🔍 Starting Fast Nearby Business Search")
            print("=" * 60)
            print(f"Configuration:")
            print(f"  - Search radius: {self.radius_meters} meters")
            print(f"  - Tables: {[config['table_name'] for config in BUSINESS_TABLES]}")
            if SLS_ID_FILTER:
                print(f"  - SLS ID filter: {SLS_ID_FILTER}")
            print("-" * 60)
            
            # Connect to database
            self.db_manager.connect()
            
            # Load ALL businesses for spatial indexing (including already processed ones)
            print("📊 Loading all businesses for spatial indexing...")
            all_businesses = self.db_manager.get_all_businesses(BUSINESS_TABLES)
            
            if not all_businesses:
                print("⚠️ No businesses found. Exiting.")
                return
            
            print(f"✓ Total businesses loaded for spatial index: {len(all_businesses)}")
            
            # Load only unprocessed businesses for duplicate checking
            print("📊 Loading unprocessed businesses for duplicate checking...")
            unprocessed_businesses = self.db_manager.get_unprocessed_businesses(BUSINESS_TABLES)
            
            if not unprocessed_businesses:
                print("✅ No unprocessed businesses found. All businesses have been scanned!")
                return
            
            print(f"✓ Unprocessed businesses to check: {len(unprocessed_businesses)}")
            
            # Build spatial index with ALL businesses
            print("🏗️ Building spatial index with all businesses...")
            for business in all_businesses:
                self.spatial_index.insert_business(business)
            print("✓ Spatial index built successfully")
            
            # Find nearby businesses and detect duplicates for each business
            print(f"\n🔍 Searching for nearby businesses and detecting duplicates...")
            search_start_time = time.time()
            
            total_matches = 0
            businesses_with_matches = 0
            total_duplicates = {'strong': 0, 'weak': 0, 'not_duplicate': 0}
            unique_businesses_with_duplicates = set()  # Track unique businesses that have duplicates
            compared_pairs = set()  # Track already compared business pairs to avoid duplicates
            skipped_comparisons = 0  # Track how many duplicate comparisons were avoided
            validation_data = []
            businesses_marked_processed = 0  # Track how many businesses successfully marked as processed
            businesses_failed_to_mark = 0  # Track how many businesses failed to mark as processed
            business_updates = []  # Collect business IDs and types for batch update (used for both immediate batching and end-of-process batching)
            
            import random
            random.seed(42)  # For reproducible results
            
            for i, business in enumerate(unprocessed_businesses):
                if i % 1000 == 0:
                    elapsed = time.time() - search_start_time
                    print(f"  Progress: {i:,}/{len(unprocessed_businesses):,} businesses processed ({elapsed:.1f}s)")
                
                # Find nearby businesses
                nearby_businesses = self.spatial_index.find_nearby_businesses(
                    business, self.radius_meters
                )
                
                if nearby_businesses:
                    businesses_with_matches += 1
                    total_matches += len(nearby_businesses)
                    
                    # Perform duplicate detection for each nearby business
                    duplicate_comparisons = []
                    
                    for nearby_business in nearby_businesses:
                        # Create a pair identifier to avoid duplicate comparisons
                        # Use sorted tuple to ensure (A,B) and (B,A) are treated as the same pair
                        pair_id = tuple(sorted([business.id, nearby_business.id]))
                        
                        # Skip if this pair has already been compared
                        if pair_id in compared_pairs:
                            skipped_comparisons += 1
                            continue
                        
                        # Mark this pair as compared
                        compared_pairs.add(pair_id)
                        
                        # Calculate precise distance for comparison if enabled
                        distance = calculate_precise_distance(
                            business.latitude, business.longitude,
                            nearby_business.latitude, nearby_business.longitude
                        ) if CALCULATE_PRECISE_DISTANCE else None
                        
                        # Detect duplicates
                        comparison = self.duplicate_detector.compare_businesses(
                            business, nearby_business, distance
                        )
                        duplicate_comparisons.append(comparison)
                        
                        # Count duplicate types
                        if comparison.duplicate_type == 'strong_duplicate':
                            total_duplicates['strong'] += 1
                            # Track both businesses involved in the duplicate
                            unique_businesses_with_duplicates.add(business.id)
                            unique_businesses_with_duplicates.add(nearby_business.id)
                        elif comparison.duplicate_type == 'weak_duplicate':
                            total_duplicates['weak'] += 1
                            # Track both businesses involved in the duplicate
                            unique_businesses_with_duplicates.add(business.id)
                            unique_businesses_with_duplicates.add(nearby_business.id)
                        else:
                            total_duplicates['not_duplicate'] += 1
                    
                    # Save detailed results to CSV if enabled
                    if SAVE_RESULTS_TO_FILE:
                        for comparison in duplicate_comparisons:
                            # Skip not_duplicate results if configured to exclude them
                            if not INCLUDE_NOT_DUPLICATES_IN_OUTPUT and comparison.duplicate_type == 'not_duplicate':
                                continue
                                
                            validation_data.append({
                                'center_business_id': comparison.business_a.id,
                                'nearby_business_id': comparison.business_b.id,
                                'center_sls_id': comparison.business_a.sls_id,
                                'nearby_sls_id': comparison.business_b.sls_id,
                                'center_business_source': comparison.business_a.business_type,
                                'nearby_business_source': comparison.business_b.business_type,
                                'center_business_name': comparison.business_a.name,
                                'nearby_business_name': comparison.business_b.name,
                                'center_business_owner': comparison.business_a.owner,
                                'nearby_business_owner': comparison.business_b.owner,
                                'name_similarity': round(comparison.name_similarity, 3),
                                'owner_similarity': round(comparison.owner_similarity, 3),
                                'duplicate_type': comparison.duplicate_type,
                                'confidence_score': round(comparison.confidence_score, 3),
                                'distance_meters': comparison.distance_meters,
                                'center_business_user': comparison.business_a.user_id,
                                'nearby_business_user': comparison.business_b.user_id,
                                'center_lat': comparison.business_a.latitude,
                                'center_lng': comparison.business_a.longitude,
                                'nearby_lat': comparison.business_b.latitude,
                                'nearby_lng': comparison.business_b.longitude
                            })
                    
                    # Save detailed results to database if enabled
                    if SAVE_RESULTS_TO_DATABASE:
                        for comparison in duplicate_comparisons:
                            # Skip not_duplicate results if configured to exclude them (similar to CSV logic)
                            if not INCLUDE_NOT_DUPLICATES_IN_OUTPUT and comparison.duplicate_type == 'not_duplicate':
                                continue
                            
                            # Save individual duplicate candidate to database
                            self.db_manager.save_duplicate_candidate(comparison)
                    
                    # Print results with duplicate information (only when duplicates found)
                    strong_dupes = sum(1 for c in duplicate_comparisons if c.duplicate_type == 'strong_duplicate')
                    weak_dupes = sum(1 for c in duplicate_comparisons if c.duplicate_type == 'weak_duplicate')
                    
                    if strong_dupes > 0 or weak_dupes > 0:
                        progress_pct = ((i + 1) / len(unprocessed_businesses)) * 100
                        print(f"📍 [{progress_pct:.1f}%] {business.name} → 🔴 {strong_dupes} strong, 🟡 {weak_dupes} weak duplicates")
                
                # Update duplicate_scan_at based on configuration
                if UPDATE_DUPLICATE_SCAN_IMMEDIATELY:
                    # Collect business info for immediate batch update
                    business_updates.append((business.id, business.business_type))
                    
                    # Process batch when it reaches BATCH_UPDATE_SIZE
                    if len(business_updates) >= BATCH_UPDATE_SIZE:
                        successful_updates, failed_updates = self.db_manager.batch_update_duplicate_scan_at(business_updates)
                        businesses_marked_processed += successful_updates
                        businesses_failed_to_mark += failed_updates
                        if failed_updates > 0:
                            print(f"⚠️ {failed_updates} businesses failed to update in batch")
                        # Clear the batch
                        business_updates = []
                else:
                    # Collect business info for batch update at the end
                    business_updates.append((business.id, business.business_type))
            
            search_end_time = time.time()
            
            # Handle final batch update
            if business_updates:  # If there are remaining businesses to update
                if UPDATE_DUPLICATE_SCAN_IMMEDIATELY:
                    # Process final partial batch for immediate mode
                    print(f"\n📝 Processing final batch of {len(business_updates)} businesses...")
                    successful_updates, failed_updates = self.db_manager.batch_update_duplicate_scan_at(business_updates)
                    businesses_marked_processed += successful_updates
                    businesses_failed_to_mark += failed_updates
                    if failed_updates > 0:
                        print(f"⚠️ {failed_updates} businesses failed to update in final batch")
                else:
                    # Process all businesses for batch mode
                    print(f"\n📝 Batch updating duplicate_scan_at for {len(business_updates)} businesses...")
                    successful_updates, failed_updates = self.db_manager.batch_update_duplicate_scan_at(business_updates)
                    businesses_marked_processed = successful_updates
                    businesses_failed_to_mark = failed_updates
                    if failed_updates > 0:
                        print(f"⚠️  {failed_updates} businesses failed to update")
            
            print(f"\n✅ Processing complete:")
            print(f"  - Businesses successfully marked as processed: {businesses_marked_processed:,}")
            if businesses_failed_to_mark > 0:
                print(f"  - Businesses that failed to mark as processed: {businesses_failed_to_mark:,}")
            
            # Save results to file if enabled
            if SAVE_RESULTS_TO_FILE and validation_data:
                if USE_TIMESTAMP_IN_FILENAME:
                    timestamp = get_jakarta_now().strftime("%Y%m%d_%H%M%S")
                    filename = f"business_duplicate_detection_results_{timestamp}.csv"
                else:
                    filename = OUTPUT_FILENAME
                
                save_results_to_csv(validation_data, filename)
                
                # Display what was saved to CSV
                if INCLUDE_NOT_DUPLICATES_IN_OUTPUT:
                    print(f"💾 Saved {len(validation_data):,} total comparison results to CSV (including not duplicates)")
                else:
                    print(f"💾 Saved {len(validation_data):,} duplicate results to CSV only (excluded not duplicates)")
            
            # Display database save results if database saving was enabled
            if SAVE_RESULTS_TO_DATABASE:
                print(f"💾 Duplicate candidates saved to database during processing")
                
                # Show what type of results were saved
                if INCLUDE_NOT_DUPLICATES_IN_OUTPUT:
                    print(f"🗄️  Database includes all comparison results (duplicates + not duplicates)")
                else:
                    print(f"🗄️  Database includes only duplicate results (strong + weak duplicates)")
            
            # Display output mode summary
            if SAVE_RESULTS_TO_FILE and SAVE_RESULTS_TO_DATABASE:
                print(f"📤 Results saved to both CSV file and database")
            elif SAVE_RESULTS_TO_FILE:
                print(f"📤 Results saved to CSV file only")
            elif SAVE_RESULTS_TO_DATABASE:
                print(f"📤 Results saved to database only")
            else:
                print(f"📤 No results saved (both SAVE_RESULTS_TO_FILE and SAVE_RESULTS_TO_DATABASE are disabled)")
            
            total_end_time = time.time()
            
            print(f"\n" + "=" * 60)
            print("✅ Duplicate Detection Search completed successfully!")
            print(f"📊 Summary:")
            print(f"  - Total businesses in spatial index: {len(all_businesses):,}")
            print(f"  - Unprocessed businesses analyzed: {len(unprocessed_businesses):,}")
            print(f"  - Businesses with nearby matches: {businesses_with_matches:,}")
            print(f"  - Total nearby business pairs found: {total_matches:,}")
            print(f"  - Average matches per business: {total_matches / len(unprocessed_businesses):.2f}" if len(unprocessed_businesses) > 0 else "  - Average matches per business: 0")
            
            print(f"\n🔍 Duplicate Detection Results:")
            print(f"  - Strong duplicate pairs found: {total_duplicates['strong']:,}")
            print(f"  - Weak duplicate pairs found: {total_duplicates['weak']:,}")
            print(f"  - Not duplicate pairs: {total_duplicates['not_duplicate']:,}")
            print(f"  - Total comparison pairs: {total_matches:,}")
            print(f"  - Skipped redundant comparisons: {skipped_comparisons:,}")
            print(f"  - Unique businesses with duplicates: {len(unique_businesses_with_duplicates):,}")
            print(f"  - Business duplicate rate: {len(unique_businesses_with_duplicates) / len(unprocessed_businesses) * 100:.1f}% ({len(unique_businesses_with_duplicates)} of {len(unprocessed_businesses)} unprocessed businesses)" if len(unprocessed_businesses) > 0 else "  - Business duplicate rate: 0%")
            if total_matches > 0:
                print(f"  - Pair duplicate rate: {(total_duplicates['strong'] + total_duplicates['weak']) / total_matches * 100:.1f}% (pairs that are duplicates)")
            
            print(f"\n⚡ Optimization:")
            if skipped_comparisons > 0:
                total_potential_comparisons = total_matches + skipped_comparisons
                efficiency_gain = (skipped_comparisons / total_potential_comparisons) * 100
                print(f"  - Efficiency gain: {efficiency_gain:.1f}% (avoided {skipped_comparisons:,} redundant comparisons)")
                print(f"  - Total potential comparisons: {total_potential_comparisons:,}")
            else:
                print(f"  - No redundant comparisons found (optimal case)")
            
            print(f"\n⏱️  Performance:")
            print(f"  - Total execution time: {total_end_time - start_time:.1f} seconds")
            print(f"  - Search phase time: {search_end_time - search_start_time:.1f} seconds")
            print(f"  - Businesses processed per second: {len(unprocessed_businesses) / (search_end_time - search_start_time):.0f}" if (search_end_time - search_start_time) > 0 and len(unprocessed_businesses) > 0 else "  - Businesses processed per second: 0")
            
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
        print("🚀 Business Duplicate Detector")
        print(f"🎯 Searching for businesses within {RADIUS_METERS}m radius")
        print(f"🔍 Detecting duplicates with {SIMILARITY_THRESHOLD} similarity threshold")
        print(f"⚡ Using R-tree spatial indexing for performance")
        
        # Display common words configuration
        if USE_COMMON_WORDS_FILTERING:
            # Load common words (this will display loading info)
            common_words = CommonWordsManager.load_common_words()
            print(f"📝 Common words filtering enabled ({len(common_words)} words loaded)")
        else:
            print(f"📝 Common words filtering disabled (using full text comparison)")
        
        if SLS_ID_FILTER:
            print(f"🎯 Filtering by SLS ID: {SLS_ID_FILTER}")
        
        if DEBUG_MODE:
            print(f"🐛 DEBUG MODE: Processing only {DEBUG_LIMIT:,} businesses for testing")
        
        # Display current algorithm configuration
        print(f"\n📋 Duplicate Detection Algorithm:")
        print(f"  1. Name & owner both high similarity → strong_duplicate")
        print(f"  2. Name high, owner low similarity → not_duplicate")
        print(f"  3. Name low, owner high similarity → not_duplicate")
        print(f"  4. Name high, owner empty → advanced step (common words filtering)")
        print(f"  5. Name low, owner empty → not_duplicate")
        print(f"  📝 Similarity threshold: {SIMILARITY_THRESHOLD}")
        
        # Display common words configuration
        if USE_COMMON_WORDS_FILTERING:
            common_words_count = len(CommonWordsManager.get_common_words()) if hasattr(CommonWordsManager, 'get_common_words') else 'unknown'
            print(f"  📝 Common words filtering enabled ({common_words_count} words loaded)")
        else:
            print(f"  📝 Common words filtering disabled (using full text comparison)")
        
        # Display distance calculation configuration
        if CALCULATE_PRECISE_DISTANCE:
            print(f"  📏 Precise distance calculation: ✅ (slower but more accurate)")
        else:
            print(f"  📏 Precise distance calculation: ❌ (faster, using spatial approximation)")
        
        # Display processing mode configuration
        if UPDATE_DUPLICATE_SCAN_IMMEDIATELY:
            print(f"  🔄 Processing mode: ✅ Immediate batching (businesses marked as processed in batches of {BATCH_UPDATE_SIZE})")
        else:
            print(f"  🔄 Processing mode: 📦 End-of-process batching (all businesses marked as processed at the end)")
        
        # Display output configuration
        print(f"\n📁 Output Configuration:")
        if SAVE_RESULTS_TO_DATABASE and SAVE_RESULTS_TO_FILE:
            print(f"  💾 Save to database: ✅ (duplicate_candidates table)")
            print(f"  📄 Save to CSV file: ✅ ({OUTPUT_FILENAME})")
        elif SAVE_RESULTS_TO_DATABASE:
            print(f"  � Save to database: ✅ (duplicate_candidates table)")
            print(f"  📄 Save to CSV file: ❌")
        elif SAVE_RESULTS_TO_FILE:
            print(f"  💾 Save to database: ❌")
            print(f"  📄 Save to CSV file: ✅ ({OUTPUT_FILENAME})")
        else:
            print(f"  💾 Save to database: ❌")
            print(f"  📄 Save to CSV file: ❌")
            print(f"  ⚠️  No output configured - results will not be saved!")
        
        if SAVE_RESULTS_TO_FILE:
            if USE_TIMESTAMP_IN_FILENAME:
                print(f"  📝 CSV filename: With timestamp")
            else:
                print(f"  📝 CSV filename: Fixed (overwrites previous)")
            
        if SAVE_RESULTS_TO_DATABASE or SAVE_RESULTS_TO_FILE:
            if INCLUDE_NOT_DUPLICATES_IN_OUTPUT:
                print(f"  � Content: All results (duplicates + not duplicates)")
            else:
                print(f"  � Content: Only duplicates (strong + weak duplicates)")
        
        print("")
        
        finder = NearbyBusinessFinder(RADIUS_METERS, SIMILARITY_THRESHOLD)
        finder.run_search()
        
        return 0
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    exit(main())
