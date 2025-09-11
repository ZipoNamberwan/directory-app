import re
import pandas as pd
import string
import mysql.connector
import os
from dotenv import load_dotenv
from datetime import datetime

# Load environment variables from .env file in parent directory
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION AND CONSTANTS
# =====================================================================

# Database configuration
DB_CONFIG = {
    "host": os.getenv("DB_MAIN_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_MAIN_PORT", 3306)),
    "user": os.getenv("DB_MAIN_USERNAME", "root"),
    "password": os.getenv("DB_MAIN_PASSWORD", ""),
    "database": os.getenv("DB_MAIN_DATABASE", ""),
}

# Column to anomaly type mapping
ANOMALY_TYPE_MAPPING = {
    'name': 1,
    'description': 2,
    'address': 3,
    'owner': 4,
    'sector': 5
}

# Table to Laravel model mapping
BUSINESS_TYPE_MAPPING = {
    'supplement_business': 'App\\Models\\SupplementBusiness',
    'market_business': 'App\\Models\\MarketBusiness'
}

# Columns that can have null values without being flagged as anomalies
NULLABLE_COLUMNS = ['address', 'owner']

# Columns to analyze
COLUMNS_TO_ANALYZE = ['name', 'description', 'owner', 'sector']

# Detection thresholds
MIN_REPETITION_COUNT = 3
CHARACTER_FREQUENCY_THRESHOLD = 0.6
ANOMALY_THRESHOLD_STRICT = 1
ANOMALY_THRESHOLD_NORMAL = 2

# =====================================================================
# ANOMALY DETECTION ALGORITHMS
# =====================================================================

class AnomalyDetector:
    """Detects anomalous/random text patterns in business data"""
    
    def __init__(self):
        # Indonesian vowels for linguistic pattern detection
        self.vowels = 'aiueo'
        # Keyboard sequences for pattern detection
        self.keyboard_sequences = [
            'qwertyuiop',
            'asdfghjkl', 
            'zxcvbnm',
            '1234567890',
            'abcdefghijklmnopqrstuvwxyz'
        ]
    
    def _clean_text(self, text):
        """Clean text for analysis"""
        return re.sub(r'[^a-zA-Z]', '', str(text).lower())
    
    def _clean_text_alphanumeric(self, text):
        """Clean text keeping alphanumeric characters"""
        return re.sub(r'[^a-zA-Z0-9]', '', str(text).lower())
    
    def detect_repetitive_patterns(self, text):
        """Detect repetitive character patterns like 'aaaa', 'xsdsxsd'"""
        text_clean = self._clean_text(text)
        
        if len(text_clean) < 2:
            return False
        
        # Check for repeated single characters
        for char in set(text_clean):
            if text_clean.count(char) >= MIN_REPETITION_COUNT and len(set(text_clean)) <= 2:
                return True
        
        # Check for repeated patterns
        for pattern_length in range(1, len(text_clean) // 2 + 1):
            pattern = text_clean[:pattern_length]
            if len(pattern) >= 1:
                repetitions = 0
                pos = 0
                while pos <= len(text_clean) - len(pattern):
                    if text_clean[pos:pos+len(pattern)] == pattern:
                        repetitions += 1
                        pos += len(pattern)
                    else:
                        pos += 1
                
                if repetitions >= 2 and repetitions * len(pattern) >= len(text_clean) * 0.6:
                    return True
        
        return False
    
    def detect_keyboard_patterns(self, text):
        """Detect keyboard sequences like 'qwerty', 'asdf', '123'"""
        text_clean = self._clean_text_alphanumeric(text)
        
        for sequence in self.keyboard_sequences:
            for i in range(len(sequence) - 2):
                pattern = sequence[i:i+3]
                if pattern in text_clean or pattern[::-1] in text_clean:
                    return True
        
        return False
    
    def detect_no_vowels(self, text):
        """Detect words with no vowels (usually random)"""
        text_clean = self._clean_text(text)
        
        if len(text_clean) >= 3:
            vowel_count = sum(1 for char in text_clean if char in self.vowels)
            return vowel_count == 0
        
        return False
    
    def detect_excessive_consonant_clusters(self, text):
        """Detect unusual consonant clusters"""
        text_clean = self._clean_text(text)
        consonant_pattern = f'[^{self.vowels}]{{4,}}'
        return len(re.findall(consonant_pattern, text_clean)) > 0
    
    def detect_alternating_patterns(self, text):
        """Detect simple alternating patterns like 'ababab'"""
        text_clean = self._clean_text(text)
        
        if len(text_clean) < 4:
            return False
        
        for i in range(len(text_clean) - 3):
            if (text_clean[i] == text_clean[i+2] and 
                text_clean[i+1] == text_clean[i+3] and
                text_clean[i] != text_clean[i+1]):
                return True
        
        return False
    
    def detect_character_frequency_anomaly(self, text):
        """Detect texts where one character dominates"""
        text_clean = self._clean_text(text)
        
        if len(text_clean) < 3:
            return False
        
        for char in set(text_clean):
            char_frequency = text_clean.count(char) / len(text_clean)
            if char_frequency > CHARACTER_FREQUENCY_THRESHOLD:
                return True
        
        return False
    
    def calculate_vowel_consonant_ratio(self, text):
        """Calculate vowel to consonant ratio"""
        text_clean = self._clean_text(text)
        if not text_clean:
            return 0
        
        vowels = sum(1 for char in text_clean if char in self.vowels)
        consonants = len(text_clean) - vowels
        
        if consonants == 0:
            return float('inf') if vowels > 0 else 0
        return vowels / consonants
    
    def detect_invalid_sector(self, text):
        """
        Detect invalid sector codes
        
        Business sector should be B-N, P-S, or U (valid sectors)
        Invalid sectors are:
        - A, O, T (forbidden sectors)
        - Any character outside A-U range
        - Empty/null values
        """
        if not isinstance(text, str) or not text:
            return True  # Empty/null sector is invalid
        
        first_char = text.strip().upper()[:1]
        
        # Check if first character is A, O, T (forbidden) or outside A-U range
        if first_char in ['A', 'O', 'T']:
            return True
        
        # Check if first character is outside A-U range
        if not ('A' <= first_char <= 'U'):
            return True
        
        return False
    
    def is_anomaly(self, text, column_name=None, strict=False, return_reason=False):
        """
        Main function to detect anomalous/random text
        
        Args:
            text: Text to analyze
            column_name: Column being analyzed (affects null handling)
            strict: Use stricter detection threshold
            return_reason: Return tuple with reason
        
        Returns:
            bool or tuple: Is anomaly (and reason if requested)
        """
        if not isinstance(text, str):
            return (False, None) if return_reason else False
        
        # Special handling for sector column
        if column_name == 'sector':
            is_sector_anomaly = self.detect_invalid_sector(text)
            reason = 'invalid_sector' if is_sector_anomaly else None
            return (is_sector_anomaly, reason) if return_reason else is_sector_anomaly
        
        # Handle null/empty values based on column type
        if not text or len(text.strip()) < 1:
            if column_name in NULLABLE_COLUMNS:
                return (False, None) if return_reason else False
            else:
                return (True, 'empty_or_null') if return_reason else True
        
        text = str(text).strip()
        text_alpha = self._clean_text(text)
        
        # Short text check
        if len(text_alpha) <= 2:
            reason = 'short_or_repetitive' if self.detect_repetitive_patterns(text) or len(text_alpha) == 0 else None
            is_anomaly = reason is not None
            return (is_anomaly, reason) if return_reason else is_anomaly
        
        # Run detection algorithms
        checks = [
            ('repetitive_pattern', self.detect_repetitive_patterns(text)),
            ('no_vowels', self.detect_no_vowels(text)),
            ('alternating_pattern', self.detect_alternating_patterns(text)),
            ('char_frequency_anomaly', self.detect_character_frequency_anomaly(text)),
            ('extreme_vowel_ratio', self.calculate_vowel_consonant_ratio(text) > 4 or 
                                   self.calculate_vowel_consonant_ratio(text) < 0.05)
        ]
        
        # Find triggered algorithms
        reasons = [name for name, result in checks if result]
        threshold = ANOMALY_THRESHOLD_STRICT if strict else ANOMALY_THRESHOLD_NORMAL
        is_anomaly = len(reasons) >= threshold
        reason = reasons[0] if is_anomaly and reasons else None
        
        return (is_anomaly, reason) if return_reason else is_anomaly

# =====================================================================
# DATABASE OPERATIONS
# =====================================================================

class DatabaseManager:
    """Handles database connections and anomaly storage"""
    
    def __init__(self):
        self.connection = None
    
    def connect(self):
        """Establish database connection"""
        try:
            self.connection = mysql.connector.connect(**DB_CONFIG)
            return True
        except Exception as e:
            print(f"Database connection failed: {e}")
            return False
    
    def disconnect(self):
        """Close database connection"""
        if self.connection:
            self.connection.close()
    
    def execute_query_to_dataframe(self, query):
        """Execute SQL query and return pandas DataFrame"""
        if not self.connection:
            raise Exception("No database connection")
        
        cursor = self.connection.cursor(dictionary=True)
        cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        return pd.DataFrame(results)
    
    def save_anomaly(self, business_id, business_type, anomaly_type_id, old_value):
        """Save anomaly record to database"""
        if not self.connection:
            raise Exception("No database connection")
        
        try:
            cursor = self.connection.cursor()
            now = datetime.now()
            
            insert_query = """
            INSERT INTO anomaly_repairs (id, business_id, business_type, anomaly_type_id, old_value, created_at, updated_at)
            VALUES (UUID(), %s, %s, %s, %s, %s, %s)
            """
            
            cursor.execute(insert_query, (
                business_id,
                business_type,
                anomaly_type_id,
                old_value,
                now,
                now
            ))
            
            self.connection.commit()
            cursor.close()
            return True
        except Exception as e:
            print(f"Error saving anomaly: {e}")
            self.connection.rollback()
            return False

# =====================================================================
# BUSINESS LOGIC
# =====================================================================

class AnomalyAnalyzer:
    """Main class for analyzing business data anomalies"""
    
    def __init__(self):
        self.detector = AnomalyDetector()
        self.db_manager = DatabaseManager()
    
    def _get_anomaly_type_id(self, column_name):
        """Get anomaly type ID for column"""
        return ANOMALY_TYPE_MAPPING.get(column_name)
    
    def _get_business_type(self, table_name):
        """Get Laravel model class for table"""
        return BUSINESS_TYPE_MAPPING.get(table_name)
    
    def analyze_column(self, df, column_name, table_name):
        """Analyze specific column and save anomalies"""
        print(f"Analyzing {column_name} from {table_name}...")
        
        # Prepare data based on column type
        if column_name in NULLABLE_COLUMNS:
            analysis_df = df.copy()
            analysis_df[column_name] = analysis_df[column_name].fillna('')
        else:
            analysis_df = df[df[column_name].notna()].copy()
        
        if len(analysis_df) == 0:
            print(f"No values found in {column_name}")
            return 0
        
        # Get mapping values
        anomaly_type_id = self._get_anomaly_type_id(column_name)
        business_type = self._get_business_type(table_name)
        
        if not anomaly_type_id or not business_type:
            print(f"Error: Invalid column name or table name mapping")
            return 0
        
        anomalies_saved = 0
        
        # Analyze each record
        for _, row in analysis_df.iterrows():
            text_value = row[column_name]
            business_id = row['id']
            
            # Check for anomaly
            is_anomaly, reason = self.detector.is_anomaly(
                text_value, 
                column_name=column_name, 
                return_reason=True
            )
            
            if is_anomaly:
                success = self.db_manager.save_anomaly(
                    business_id=business_id,
                    business_type=business_type,
                    anomaly_type_id=anomaly_type_id,
                    old_value=text_value
                )
                
                if success:
                    anomalies_saved += 1
                    print(f"  Saved anomaly: {business_id} - {text_value} ({reason})")
        
        print(f"  Total {column_name} records: {len(analysis_df)}")
        print(f"  Anomalies saved: {anomalies_saved}")
        
        return anomalies_saved
    
    def analyze_table(self, table_name, query):
        """Analyze all configured columns in a table"""
        print(f"Loading {table_name} table...")
        df = self.db_manager.execute_query_to_dataframe(query)
        print(f"Loaded {len(df)} records from {table_name}")
        
        total_anomalies = 0
        available_columns = [col for col in COLUMNS_TO_ANALYZE if col in df.columns]
        
        for column in available_columns:
            anomalies_count = self.analyze_column(df, column, table_name)
            total_anomalies += anomalies_count
        
        return total_anomalies
    
    def run_analysis(self):
        """Run complete anomaly analysis"""
        if not self.db_manager.connect():
            print("Failed to connect to database. Please check your connection settings.")
            return
        
        total_anomalies_saved = 0
        
        try:
            # Analyze supplement_business table
            supplement_query = """
            SELECT id, name, description, address, owner, sector, regency_id, subdistrict_id, village_id, sls_id 
            FROM supplement_business 
            WHERE deleted_at IS NULL
            """
            total_anomalies_saved += self.analyze_table('supplement_business', supplement_query)
            
            # Analyze market_business table
            print()
            market_query = """
            SELECT id, name, description, address, sector, market_id, regency_id, subdistrict_id, village_id, sls_id 
            FROM market_business 
            WHERE deleted_at IS NULL
            """
            total_anomalies_saved += self.analyze_table('market_business', market_query)
            
            print(f"\nAnalysis complete!")
            print(f"Total anomalies saved to database: {total_anomalies_saved}")
            
        except Exception as e:
            print(f"Error during analysis: {e}")
        finally:
            self.db_manager.disconnect()

# =====================================================================
# MAIN EXECUTION
# =====================================================================

def main():
    """Main entry point"""
    analyzer = AnomalyAnalyzer()
    analyzer.run_analysis()

if __name__ == "__main__":
    main()
