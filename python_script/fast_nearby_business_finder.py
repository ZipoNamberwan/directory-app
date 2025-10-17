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
- **Configurable duplicate detection rules** - customize how different conditions are classified
- Classification of duplicates (Strong, Weak, Not duplicate)
- Predefined rule sets (Conservative, Aggressive, Name-focused)

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

To customize rules, modify the DUPLICATE_RULES configuration or use predefined sets:
- DUPLICATE_RULES (default): Balanced approach
- CONSERVATIVE_RULES: More restrictive duplicate detection
- AGGRESSIVE_RULES: More liberal duplicate detection  
- NAME_FOCUSED_RULES: Prioritizes name similarity over owner similarity

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

import mysql.connector
from dotenv import load_dotenv
from rtree import index
from shapely.geometry import Point

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

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

# Validation mode - set to True to enable result validation
VALIDATION_MODE = True
SAVE_RESULTS_TO_FILE = True  # Save detailed results for manual inspection
SAMPLE_CHECK_COUNT = 100  # Number of random businesses to validate manually

# Output file configuration
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
        'table_name': 'supplement_business',
        'business_type': 'supplement'
    },
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
    sls_id: str  # SLS ID for the business
    business_type: str  # 'supplement' or 'market'
    address: str = ""
    
    def __post_init__(self):
        # Normalize text fields
        self.name = self.name or ""
        self.owner = self.owner or ""
        self.address = self.address or ""

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
    
    @classmethod
    def get_filtering_info(cls, text: str) -> Dict[str, Any]:
        """
        Get detailed information about how text would be filtered
        Useful for debugging and understanding the filtering process
        """
        if not text:
            return {
                'original': "",
                'filtered': "",
                'common_words_found': [],
                'remaining_words': [],
                'used_fallback': False
            }
        
        common_words_set = cls.load_common_words()
        words = text.split()
        
        common_words_found = [word for word in words if word.lower() in common_words_set]
        remaining_words = [word for word in words if word.lower() not in common_words_set]
        
        filtered_text = ' '.join(remaining_words) if remaining_words else text
        used_fallback = len(remaining_words) == 0 and len(words) > 0
        
        return {
            'original': text,
            'filtered': filtered_text,
            'common_words_found': common_words_found,
            'remaining_words': remaining_words,
            'used_fallback': used_fallback
        }

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
# DUPLICATE RULE CONFIGURATIONS
# =====================================================================

def create_custom_rules(both_high_similarity='strong_duplicate',
                       name_high_owner_low='not_duplicate',
                       name_low_owner_high='not_duplicate',
                       name_high_one_owner_empty='weak_duplicate',
                       both_owners_empty_name_high='strong_duplicate',
                       default='not_duplicate'):
    """
    Create a custom duplicate detection rules configuration
    
    Parameters:
        both_high_similarity: Result when both name and owner similarity >= threshold
        name_high_owner_low: Result when name similarity >= threshold but owner similarity < threshold (owner not empty)
        name_low_owner_high: Result when name similarity < threshold but owner similarity >= threshold (owner not empty)
        name_high_one_owner_empty: Result when name similarity >= threshold and one owner is empty
        both_owners_empty_name_high: Result when both owners are empty and name similarity >= threshold
        default: Result for all other cases
    
    Valid values: 'strong_duplicate', 'weak_duplicate', 'not_duplicate'
    """
    return {
        'both_high_similarity': both_high_similarity,
        'name_high_owner_low': name_high_owner_low,
        'name_low_owner_high': name_low_owner_high,
        'name_high_one_owner_empty': name_high_one_owner_empty,
        'both_owners_empty_name_high': both_owners_empty_name_high,
        'default': default
    }

# Predefined rule configurations for common scenarios
CONSERVATIVE_RULES = create_custom_rules(
    both_high_similarity='strong_duplicate',
    name_high_owner_low='not_duplicate',
    name_low_owner_high='not_duplicate',
    name_high_one_owner_empty='weak_duplicate',
    both_owners_empty_name_high='weak_duplicate',  # More conservative
    default='not_duplicate'
)

AGGRESSIVE_RULES = create_custom_rules(
    both_high_similarity='strong_duplicate',
    name_high_owner_low='weak_duplicate',  # More aggressive
    name_low_owner_high='weak_duplicate',  # More aggressive
    name_high_one_owner_empty='strong_duplicate',  # More aggressive
    both_owners_empty_name_high='strong_duplicate',
    default='not_duplicate'
)

NAME_FOCUSED_RULES = create_custom_rules(
    both_high_similarity='strong_duplicate',
    name_high_owner_low='strong_duplicate',  # Focus on name similarity
    name_low_owner_high='not_duplicate',
    name_high_one_owner_empty='strong_duplicate',  # Focus on name similarity
    both_owners_empty_name_high='strong_duplicate',
    default='not_duplicate'
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

def validate_nearby_businesses(center_business: Business, nearby_businesses: List[Business], radius_meters: float) -> Dict[str, Any]:
    """
    Validate nearby businesses using precise distance calculation
    Returns validation results
    """
    validation_results = {
        'total_found': len(nearby_businesses),
        'within_radius': 0,
        'outside_radius': 0,
        'same_user_violations': 0,
        'distances': [],
        'violations': []
    }
    
    for nearby_business in nearby_businesses:
        # Check for same user violation
        if nearby_business.user_id == center_business.user_id:
            validation_results['same_user_violations'] += 1
            validation_results['violations'].append({
                'type': 'same_user',
                'business_id': nearby_business.id,
                'business_name': nearby_business.name
            })
        
        # Calculate precise distance
        precise_distance = calculate_precise_distance(
            center_business.latitude, center_business.longitude,
            nearby_business.latitude, nearby_business.longitude
        )
        
        validation_results['distances'].append(precise_distance)
        
        if precise_distance <= radius_meters:
            validation_results['within_radius'] += 1
        else:
            validation_results['outside_radius'] += 1
            validation_results['violations'].append({
                'type': 'outside_radius',
                'business_id': nearby_business.id,
                'business_name': nearby_business.name,
                'distance': precise_distance
            })
    
    return validation_results

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
                continue
            
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
    
    def get_businesses_from_table(self, table_name: str, business_type: str) -> List[Business]:
        """Fetch businesses from a specific table"""
        # Add LIMIT clause if debug mode is enabled
        limit_clause = f"LIMIT {DEBUG_LIMIT}" if DEBUG_MODE else ""
        
        # Add SLS ID filter if specified
        sls_filter = f"AND sls_id = '{SLS_ID_FILTER}'" if SLS_ID_FILTER else ""
        
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
                {sls_filter}
            ORDER BY created_at DESC
            {limit_clause}
            """
        else:
            # Standard query for supplement_business (has owner column)
            query = f"""
            SELECT 
                id,
                name,
                owner,
                address,
                latitude,
                longitude,
                user_id,
                sls_id
            FROM {table_name}
            WHERE latitude IS NOT NULL 
                AND longitude IS NOT NULL
                AND deleted_at IS NULL
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
            sample_businesses = []
            
            import random
            random.seed(42)  # For reproducible results
            
            for i, business in enumerate(all_businesses):
                if i % 1000 == 0:
                    elapsed = time.time() - search_start_time
                    print(f"  Progress: {i:,}/{len(all_businesses):,} businesses processed ({elapsed:.1f}s)")
                
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
                        
                        # Calculate precise distance for comparison
                        distance = calculate_precise_distance(
                            business.latitude, business.longitude,
                            nearby_business.latitude, nearby_business.longitude
                        ) if VALIDATION_MODE else None
                        
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
                    
                    # Collect sample for validation
                    if VALIDATION_MODE and len(sample_businesses) < SAMPLE_CHECK_COUNT:
                        sample_businesses.append((business, nearby_businesses))
                    
                    # Save detailed results if enabled
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
                    
                    # Print results with duplicate information
                    strong_dupes = sum(1 for c in duplicate_comparisons if c.duplicate_type == 'strong_duplicate')
                    weak_dupes = sum(1 for c in duplicate_comparisons if c.duplicate_type == 'weak_duplicate')
                    
                    if strong_dupes > 0 or weak_dupes > 0:
                        print(f"📍 {business.name} → {len(nearby_businesses)} nearby, "
                              f"🔴 {strong_dupes} strong duplicates, 🟡 {weak_dupes} weak duplicates")
                    else:
                        print(f"📍 {business.name} → {len(nearby_businesses)} nearby, ✅ no duplicates")
            
            search_end_time = time.time()
            
            # Perform validation if enabled
            if VALIDATION_MODE and sample_businesses:
                print(f"\n🔍 Validating results with {len(sample_businesses)} sample businesses...")
                
                total_outside_radius = 0
                total_same_user_violations = 0
                
                for business, nearby_businesses in sample_businesses:
                    validation_result = validate_nearby_businesses(business, nearby_businesses, self.radius_meters)
                    
                    print(f"\n📊 Validation for {business.name}:")
                    print(f"  - Found {validation_result['total_found']} nearby businesses")
                    print(f"  - Within radius: {validation_result['within_radius']}")
                    print(f"  - Outside radius: {validation_result['outside_radius']}")
                    print(f"  - Same user violations: {validation_result['same_user_violations']}")
                    
                    if validation_result['distances']:
                        print(f"  - Distance range: {min(validation_result['distances']):.1f}m - {max(validation_result['distances']):.1f}m")
                    
                    total_outside_radius += validation_result['outside_radius']
                    total_same_user_violations += validation_result['same_user_violations']
                    
                    if validation_result['violations']:
                        print(f"  ⚠️  Violations found:")
                        for violation in validation_result['violations'][:3]:  # Show first 3
                            if violation['type'] == 'outside_radius':
                                print(f"    - {violation['business_name']} is {violation['distance']:.1f}m away (outside {self.radius_meters}m)")
                            elif violation['type'] == 'same_user':
                                print(f"    - {violation['business_name']} has same user_id")
                
                print(f"\n📋 Validation Summary:")
                print(f"  - Total businesses outside radius: {total_outside_radius}")
                print(f"  - Total same user violations: {total_same_user_violations}")
                print(f"  - Validation accuracy: {((len(sample_businesses) * 100) - total_outside_radius - total_same_user_violations) / (len(sample_businesses) * 100) * 100:.1f}%")
            
            # Save results to file if enabled
            if SAVE_RESULTS_TO_FILE and validation_data:
                if USE_TIMESTAMP_IN_FILENAME:
                    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                    filename = f"business_duplicate_detection_results_{timestamp}.csv"
                else:
                    filename = OUTPUT_FILENAME
                
                save_results_to_csv(validation_data, filename)
                
                # Display what was saved
                if INCLUDE_NOT_DUPLICATES_IN_OUTPUT:
                    print(f"💾 Saved {len(validation_data):,} total comparison results (including not duplicates)")
                else:
                    print(f"💾 Saved {len(validation_data):,} duplicate results only (excluded not duplicates)")
            
            total_end_time = time.time()
            
            print(f"\n" + "=" * 60)
            print("✅ Duplicate Detection Search completed successfully!")
            print(f"📊 Summary:")
            print(f"  - Total businesses analyzed: {len(all_businesses):,}")
            print(f"  - Businesses with nearby matches: {businesses_with_matches:,}")
            print(f"  - Total nearby business pairs found: {total_matches:,}")
            print(f"  - Average matches per business: {total_matches / len(all_businesses):.2f}")
            
            print(f"\n🔍 Duplicate Detection Results:")
            print(f"  - Strong duplicate pairs found: {total_duplicates['strong']:,}")
            print(f"  - Weak duplicate pairs found: {total_duplicates['weak']:,}")
            print(f"  - Not duplicate pairs: {total_duplicates['not_duplicate']:,}")
            print(f"  - Total comparison pairs: {total_matches:,}")
            print(f"  - Skipped redundant comparisons: {skipped_comparisons:,}")
            print(f"  - Unique businesses with duplicates: {len(unique_businesses_with_duplicates):,}")
            print(f"  - Business duplicate rate: {len(unique_businesses_with_duplicates) / len(all_businesses) * 100:.1f}% ({len(unique_businesses_with_duplicates)} of {len(all_businesses)} businesses)")
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
            print(f"  - Businesses processed per second: {len(all_businesses) / (search_end_time - search_start_time):.0f}")
            
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
        
        # Display output file configuration
        if SAVE_RESULTS_TO_FILE:
            if USE_TIMESTAMP_IN_FILENAME:
                print(f"\n📁 Output: Results will be saved with timestamp in filename")
            else:
                print(f"\n📁 Output: Results will be saved to '{OUTPUT_FILENAME}' (overwrites previous results)")
            
            if INCLUDE_NOT_DUPLICATES_IN_OUTPUT:
                print(f"📄 Content: Including all results (duplicates + not duplicates)")
            else:
                print(f"📄 Content: Including only duplicates (strong + weak duplicates only)")
        
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
