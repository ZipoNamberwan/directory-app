#!/usr/bin/env python3
"""
Business Name Word Frequency Analyzer

This script analyzes word frequency from business names in supplement_business and market_business tables.
It helps identify the most common words used in business names, which can be useful for:
- Updating common_words.csv for duplicate detection
- Understanding business naming patterns
- Data analysis and insights

Features:
- Processes both supplement_business and market_business tables
- Normalizes text (lowercase, removes punctuation)
- Counts word frequency across all business names
- Exports results to CSV for analysis
- Configurable minimum word length and frequency thresholds
- Handles owner extraction from market business names

Requirements:
    - mysql-connector-python
    - python-dotenv

Usage:
    python word_frequency_analyzer.py
"""

import os
import sys
import time
import re
import string
from collections import Counter
from typing import Dict, List, Tuple
import csv

import mysql.connector
from dotenv import load_dotenv

# Load environment variables
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION
# =====================================================================

# Analysis settings
MIN_WORD_LENGTH = 2  # Minimum word length to include in analysis
MIN_FREQUENCY = 5    # Minimum frequency for a word to be included in results
EXCLUDE_NUMBERS = True  # Exclude purely numeric words
EXCLUDE_SINGLE_CHARS = True  # Exclude single character words

# Output settings
OUTPUT_FILENAME = "business_name_word_frequency.csv"
TOP_WORDS_COUNT = 100  # Show top N words in console output

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
    {
        'table_name': 'market_business',
        'business_type': 'market'
    }
]

# =====================================================================
# TEXT PROCESSING UTILITIES
# =====================================================================

def extract_owner_from_name(name: str) -> tuple[str, str]:
    """
    Extract owner from business name for market businesses.
    
    Rules:
    - If name contains <owner> or (owner), extract owner and clean name
    - If no brackets/parentheses, owner is empty
    
    Returns:
        tuple: (cleaned_name, extracted_owner)
    """
    if not name:
        return "", ""
    
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

def normalize_text(text: str) -> str:
    """
    Normalize text for word frequency analysis:
    1. Convert to lowercase
    2. Remove punctuation
    3. Remove extra spaces
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

def extract_words(text: str) -> List[str]:
    """
    Extract words from normalized text with filtering rules
    """
    if not text:
        return []
    
    words = text.split()
    filtered_words = []
    
    for word in words:
        # Skip if word is too short
        if len(word) < MIN_WORD_LENGTH:
            continue
            
        # Skip single characters if configured
        if EXCLUDE_SINGLE_CHARS and len(word) == 1:
            continue
            
        # Skip purely numeric words if configured
        if EXCLUDE_NUMBERS and word.isdigit():
            continue
            
        filtered_words.append(word)
    
    return filtered_words

# =====================================================================
# DATABASE MANAGER
# =====================================================================

class DatabaseManager:
    """Handles database operations"""
    
    def __init__(self, config: Dict):
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
    
    def get_business_names_from_table(self, table_name: str, business_type: str) -> List[str]:
        """Fetch business names from a specific table"""
        query = f"""
        SELECT name
        FROM {table_name}
        WHERE name IS NOT NULL 
            AND name != ''
            AND deleted_at IS NULL
        """
        
        print(f"📊 Loading business names from {table_name}...")
        
        cursor = self.connection.cursor()
        cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        
        business_names = []
        for row in results:
            name = row[0] or ""
            
            # Handle owner extraction for market businesses
            if business_type == 'market':
                # Extract owner from name and use cleaned name
                cleaned_name, extracted_owner = extract_owner_from_name(name)
                name = cleaned_name
            
            if name.strip():  # Only add non-empty names
                business_names.append(name.strip())
        
        print(f"✓ Loaded {len(business_names)} business names from {table_name}")
        return business_names
    
    def get_all_business_names(self, table_configs: List[Dict[str, str]]) -> List[str]:
        """Load business names from all configured tables"""
        all_names = []
        
        for config in table_configs:
            names = self.get_business_names_from_table(
                config['table_name'], 
                config['business_type']
            )
            all_names.extend(names)
        
        return all_names

# =====================================================================
# WORD FREQUENCY ANALYZER
# =====================================================================

class WordFrequencyAnalyzer:
    """Main engine for analyzing word frequency in business names"""
    
    def __init__(self):
        self.db_manager = DatabaseManager(DB_CONFIG)
        self.word_counter = Counter()
    
    def analyze_word_frequency(self):
        """Execute the complete word frequency analysis"""
        start_time = time.time()
        
        try:
            print("🔍 Starting Business Name Word Frequency Analysis")
            print("=" * 60)
            print(f"Configuration:")
            print(f"  - Minimum word length: {MIN_WORD_LENGTH}")
            print(f"  - Minimum frequency: {MIN_FREQUENCY}")
            print(f"  - Exclude numbers: {EXCLUDE_NUMBERS}")
            print(f"  - Exclude single chars: {EXCLUDE_SINGLE_CHARS}")
            print(f"  - Tables: {[config['table_name'] for config in BUSINESS_TABLES]}")
            print("-" * 60)
            
            # Connect to database
            self.db_manager.connect()
            
            # Load all business names
            print("📊 Loading business names from database...")
            all_names = self.db_manager.get_all_business_names(BUSINESS_TABLES)
            
            if not all_names:
                print("⚠️ No business names found. Exiting.")
                return
            
            print(f"✓ Total business names loaded: {len(all_names):,}")
            
            # Process each business name
            print("\n🔍 Analyzing word frequency...")
            analysis_start_time = time.time()
            
            total_words = 0
            unique_words = set()
            
            for i, name in enumerate(all_names):
                if i % 10000 == 0 and i > 0:
                    elapsed = time.time() - analysis_start_time
                    print(f"  Progress: {i:,}/{len(all_names):,} names processed ({elapsed:.1f}s)")
                
                # Normalize and extract words
                normalized_name = normalize_text(name)
                words = extract_words(normalized_name)
                
                # Count words
                for word in words:
                    self.word_counter[word] += 1
                    unique_words.add(word)
                    total_words += 1
            
            analysis_end_time = time.time()
            
            # Filter words by minimum frequency
            filtered_word_counter = Counter({
                word: count for word, count in self.word_counter.items()
                if count >= MIN_FREQUENCY
            })
            
            # Get top words
            top_words = filtered_word_counter.most_common(TOP_WORDS_COUNT)
            
            # Display results
            print(f"\n📊 Word Frequency Analysis Results:")
            print(f"  - Total business names analyzed: {len(all_names):,}")
            print(f"  - Total words extracted: {total_words:,}")
            print(f"  - Unique words found: {len(unique_words):,}")
            print(f"  - Words with frequency >= {MIN_FREQUENCY}: {len(filtered_word_counter):,}")
            print(f"  - Average words per business name: {total_words / len(all_names):.2f}")
            
            # Show top words
            if top_words:
                print(f"\n🏆 Top {len(top_words)} Most Frequent Words:")
                print(f"{'Rank':<6} {'Word':<20} {'Frequency':<12} {'Percentage':<10}")
                print("-" * 50)
                for rank, (word, count) in enumerate(top_words, 1):
                    percentage = (count / len(all_names)) * 100
                    print(f"{rank:<6} {word:<20} {count:<12,} {percentage:<10.2f}%")
            
            # Save results to CSV
            self.save_results_to_csv(filtered_word_counter)
            
            total_end_time = time.time()
            
            print(f"\n⏱️  Performance:")
            print(f"  - Total execution time: {total_end_time - start_time:.1f} seconds")
            print(f"  - Analysis time: {analysis_end_time - analysis_start_time:.1f} seconds")
            print(f"  - Names processed per second: {len(all_names) / (analysis_end_time - analysis_start_time):.0f}")
            
            print(f"\n✅ Word frequency analysis completed successfully!")
            
        except Exception as e:
            print(f"\n❌ Error during analysis: {e}")
            raise
        finally:
            self.db_manager.disconnect()
    
    def save_results_to_csv(self, word_counter: Counter):
        """Save word frequency results to CSV file"""
        output_dir = os.path.join(os.path.dirname(__file__), '..', 'backup', 'validation')
        os.makedirs(output_dir, exist_ok=True)
        output_path = os.path.join(output_dir, OUTPUT_FILENAME)
        
        # Prepare data for CSV
        results_data = []
        for word, count in word_counter.most_common():
            percentage = (count / len(word_counter)) * 100
            results_data.append({
                'word': word,
                'frequency': count,
                'percentage': round(percentage, 2)
            })
        
        # Write to CSV
        with open(output_path, 'w', newline='', encoding='utf-8') as csvfile:
            fieldnames = ['word', 'frequency', 'percentage']
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            writer.writerows(results_data)
        
        print(f"\n💾 Results saved to: {output_path}")
        print(f"📄 Total words in CSV: {len(results_data):,}")
        
        return output_path

# =====================================================================
# MAIN EXECUTION
# =====================================================================

def main():
    """Main function"""
    try:
        print("📊 Business Name Word Frequency Analyzer")
        print("🔍 Analyzing word patterns in business names")
        print("⚡ Processing supplement_business and market_business tables")
        print("")
        
        analyzer = WordFrequencyAnalyzer()
        analyzer.analyze_word_frequency()
        
        return 0
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    exit(main())