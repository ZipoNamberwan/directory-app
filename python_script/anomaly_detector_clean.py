import re
import pandas as pd
import string
import mysql.connector
import os
import json
import ast
from dotenv import load_dotenv
from datetime import datetime
import pytz

# Load environment variables from .env file in parent directory
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), '..', '.env'))

# =====================================================================
# CONFIGURATION AND CONSTANTS
# =====================================================================

# Jakarta timezone
JAKARTA_TZ = pytz.timezone('Asia/Jakarta')

def get_jakarta_now():
    """Get current datetime in Jakarta timezone"""
    return datetime.now(JAKARTA_TZ)

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

# Valid table names (whitelist for SQL injection protection)
VALID_TABLES = ['supplement_business', 'market_business']

# Valid columns (whitelist for SQL injection protection)
VALID_COLUMNS = [
    'id', 'name', 'description', 'address', 'owner', 'sector', 
    'regency_id', 'subdistrict_id', 'village_id', 'sls_id', 'market_id', 'checked_at'
]

# Columns that can have null values without being flagged as anomalies
NULLABLE_COLUMNS = ['address', 'owner']

# Columns to analyze
COLUMNS_TO_ANALYZE = ['name', 'description', 'owner', 'sector', 'address']

# Detection thresholds
MIN_REPETITION_COUNT = 3
CHARACTER_FREQUENCY_THRESHOLD = 0.6
ANOMALY_THRESHOLD_STRICT = 1
ANOMALY_THRESHOLD_NORMAL = 2

# Batch processing configuration
BATCH_SIZE = 100000  # Process 100k records at a time

# Ignore words CSV file path (should be in same directory as this script)
IGNORE_WORDS_CSV_PATH = os.path.join(os.path.dirname(__file__), 'ignore_words.csv')

# =====================================================================
# IGNORE WORDS MANAGER
# =====================================================================

class IgnoreWordsManager:
    """Manages the ignore words list from CSV file"""
    
    def __init__(self, csv_path=IGNORE_WORDS_CSV_PATH):
        self.csv_path = csv_path
        self.ignore_words_dict = {}
        self.load_ignore_words()
    
    def load_ignore_words(self):
        """Load ignore words from CSV file"""
        try:
            if not os.path.exists(self.csv_path):
                print(f"Ignore words CSV file not found at: {self.csv_path}")
                print("Continuing analysis without ignore words filter.")
                return
            
            df = pd.read_csv(self.csv_path)
            
            # Validate CSV structure
            if 'word' not in df.columns or 'types' not in df.columns:
                print("Error: CSV file must have 'word' and 'types' columns")
                return
            
            # Process each row
            for _, row in df.iterrows():
                word = str(row['word']).strip().lower()
                types_str = str(row['types']).strip()
                
                # Parse types - handle both JSON array format and comma-separated
                try:
                    # Try JSON format first: ['name', 'description']
                    if types_str.startswith('[') and types_str.endswith(']'):
                        types_list = ast.literal_eval(types_str)  # Safe evaluation for list parsing
                    else:
                        # Handle comma-separated format: name, description
                        types_list = [t.strip() for t in types_str.split(',') if t.strip()]
                except:
                    print(f"Warning: Could not parse types for word '{word}': {types_str}")
                    continue
                
                # Add word to ignore dictionary for each applicable type
                for column_type in types_list:
                    column_type = column_type.strip().lower()
                    if column_type not in self.ignore_words_dict:
                        self.ignore_words_dict[column_type] = set()
                    self.ignore_words_dict[column_type].add(word)
            
            total_words = sum(len(words) for words in self.ignore_words_dict.values())
            print(f"Loaded {total_words} ignore words for {len(self.ignore_words_dict)} column types")
            
            # Show summary
            for column_type, words in self.ignore_words_dict.items():
                print(f"  {column_type}: {len(words)} words")
                
        except Exception as e:
            print(f"Error loading ignore words CSV: {e}")
            print("Continuing analysis without ignore words filter.")
    
    def should_ignore(self, text, column_name):
        """Check if text should be ignored for specific column"""
        if not text or not column_name:
            return False
        
        text_clean = str(text).strip().lower()
        column_name_clean = column_name.lower()
        
        # Check if this column has ignore words
        ignore_words = self.ignore_words_dict.get(column_name_clean, set())
        
        # Check exact match
        if text_clean in ignore_words:
            return True
        
        # Check if any ignore word is contained in the text (for partial matches)
        for ignore_word in ignore_words:
            if ignore_word in text_clean:
                return True
        
        return False

# =====================================================================
# ANOMALY DETECTION ALGORITHMS
# =====================================================================

class AnomalyDetector:
    """Detects anomalous/random text patterns in business data"""
    
    def __init__(self, ignore_words_manager=None):
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
        # Ignore words manager
        self.ignore_words_manager = ignore_words_manager
    
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
    
    def detect_sequential_alphabetic_patterns(self, text, min_length=3):
        """Detect sequential alphabetic patterns like 'abc', 'abcd', 'xyz', etc."""
        text_clean = self._clean_text(text)
        
        if len(text_clean) < min_length:
            return False
        
        # Check for ascending sequences (abc, def, xyz, etc.)
        for i in range(len(text_clean) - min_length + 1):
            is_sequential = True
            for j in range(min_length - 1):
                if ord(text_clean[i + j + 1]) != ord(text_clean[i + j]) + 1:
                    is_sequential = False
                    break
            if is_sequential:
                return True
        
        # Check for descending sequences (zyx, fed, cba, etc.)
        for i in range(len(text_clean) - min_length + 1):
            is_sequential = True
            for j in range(min_length - 1):
                if ord(text_clean[i + j + 1]) != ord(text_clean[i + j]) - 1:
                    is_sequential = False
                    break
            if is_sequential:
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
        
        # Check ignore words first - if word should be ignored, it's not an anomaly
        if self.ignore_words_manager and column_name:
            if self.ignore_words_manager.should_ignore(text, column_name):
                return (False, 'ignored_word') if return_reason else False
        
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
            # ('keyboard_pattern', self.detect_keyboard_patterns(text)),
            # ('sequential_alphabetic_pattern', self.detect_sequential_alphabetic_patterns(text)),
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
# SECURITY VALIDATION FUNCTIONS
# =====================================================================

def validate_table_name(table_name):
    """Validate table name against whitelist to prevent SQL injection"""
    if table_name not in VALID_TABLES:
        raise ValueError(f"Invalid table name: {table_name}. Allowed tables: {VALID_TABLES}")
    return table_name

def validate_column_names(columns):
    """Validate column names against whitelist to prevent SQL injection"""
    if isinstance(columns, str):
        columns = [columns]
    
    for column in columns:
        if column not in VALID_COLUMNS:
            raise ValueError(f"Invalid column name: {column}. Allowed columns: {VALID_COLUMNS}")
    
    return columns

def build_safe_select_query(table_name, columns, where_conditions=None):
    """Build a safe SELECT query with validated table and column names"""
    # Validate inputs
    validated_table = validate_table_name(table_name)
    validated_columns = validate_column_names(columns)
    
    # Build column list
    column_list = ', '.join(validated_columns)
    
    # Build base query
    query = f"SELECT {column_list} FROM {validated_table} WHERE checked_at IS NULL"
    if where_conditions:
        query += f" AND ({where_conditions})"
    
    return query

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
    
    def execute_query_to_dataframe(self, query, params=None):
        """Execute SQL query and return pandas DataFrame"""
        if not self.connection:
            raise Exception("No database connection")
        
        cursor = self.connection.cursor(dictionary=True)
        if params:
            cursor.execute(query, params)
        else:
            cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        return pd.DataFrame(results)
    
    def execute_safe_select(self, table_name, columns, where_conditions=None, params=None):
        """Execute a safe SELECT query with validation"""
        query = build_safe_select_query(table_name, columns, where_conditions)
        return self.execute_query_to_dataframe(query, params)
    
    def get_total_records_to_process(self, table_name, where_conditions=None):
        """Get total count of records that need to be processed"""
        # Validate table name for security
        validated_table = validate_table_name(table_name)
        
        query = f"SELECT COUNT(*) as total FROM {validated_table} WHERE checked_at IS NULL"
        if where_conditions:
            query += f" AND ({where_conditions})"
        
        result = self.execute_query_to_dataframe(query)
        return result['total'].iloc[0] if len(result) > 0 else 0
    
    def execute_safe_select_batch(self, table_name, columns, where_conditions=None, limit=None):
        """Execute a safe SELECT query to get the first N unprocessed records"""
        # Validate inputs
        validated_table = validate_table_name(table_name)
        validated_columns = validate_column_names(columns)
        
        # Build column list
        column_list = ', '.join(validated_columns)
        
        # Build base query - always get the first unprocessed records
        query = f"SELECT {column_list} FROM {validated_table} WHERE checked_at IS NULL"
        
        if where_conditions:
            query += f" AND ({where_conditions})"
        
        # Add ORDER BY for consistent results
        query += " ORDER BY id"
        
        # Add LIMIT
        if limit:
            query += f" LIMIT {limit}"
        
        return self.execute_query_to_dataframe(query)
    
    def save_anomaly(self, business_id, business_type, anomaly_type_id, old_value):
        """Save anomaly record to database"""
        if not self.connection:
            raise Exception("No database connection")
        
        try:
            cursor = self.connection.cursor()
            now = get_jakarta_now()
            
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
    
    def update_checked_records(self, table_name, record_ids):
        """Update checked_at timestamp for processed records"""
        if not self.connection:
            raise Exception("No database connection")
        
        if not record_ids:
            return 0
        
        try:
            # Validate table name for security
            validated_table = validate_table_name(table_name)
            
            cursor = self.connection.cursor()
            now = get_jakarta_now()
            
            # Build placeholders for the IN clause
            placeholders = ', '.join(['%s'] * len(record_ids))
            
            update_query = f"""
            UPDATE {validated_table} 
            SET checked_at = %s 
            WHERE id IN ({placeholders})
            """
            
            # Prepare parameters: timestamp + list of IDs
            params = [now] + list(record_ids)
            
            cursor.execute(update_query, params)
            updated_count = cursor.rowcount
            
            self.connection.commit()
            cursor.close()
            
            return updated_count
        except Exception as e:
            print(f"Error updating checked_at for {table_name}: {e}")
            self.connection.rollback()
            return 0

# =====================================================================
# BUSINESS LOGIC
# =====================================================================

class AnomalyAnalyzer:
    """Main class for analyzing business data anomalies"""
    
    def __init__(self):
        self.ignore_words_manager = IgnoreWordsManager()
        self.detector = AnomalyDetector(ignore_words_manager=self.ignore_words_manager)
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
                    # print(f"  Saved anomaly: {business_id} - {text_value} ({reason})")
        
        print(f"  Total {column_name} records: {len(analysis_df)}")
        print(f"  Anomalies saved: {anomalies_saved}")
        
        return anomalies_saved
    
    def analyze_table(self, table_name, columns=None):
        """Analyze all configured columns in a table using batch processing"""
        print(f"\nProcessing {table_name} table in batches...")
        
        # Define default columns for each table
        if columns is None:
            if table_name == 'supplement_business':
                columns = ['id', 'name', 'description', 'address', 'owner', 'sector', 
                          'regency_id', 'subdistrict_id', 'village_id', 'sls_id', 'checked_at']
            elif table_name == 'market_business':
                columns = ['id', 'name', 'description', 'address', 'sector', 'market_id',
                          'regency_id', 'subdistrict_id', 'village_id', 'sls_id', 'checked_at']
            else:
                raise ValueError(f"Unknown table: {table_name}")
        
        # Get total records to process
        total_records = self.db_manager.get_total_records_to_process(
            table_name=table_name,
            where_conditions="deleted_at IS NULL"
        )
        
        if total_records == 0:
            print(f"No records to process in {table_name}")
            return 0
        
        print(f"Total records to process: {total_records:,}")
        print(f"Batch size: {BATCH_SIZE:,}")
        total_batches = (total_records + BATCH_SIZE - 1) // BATCH_SIZE
        print(f"Estimated batches: {total_batches}")
        
        total_anomalies = 0
        processed_records = 0
        max_batches = total_batches + 1  # Safety buffer to prevent infinite loops
        
        # Process in batches - always get first unprocessed records
        batch_num = 0
        while batch_num < max_batches:
            batch_num += 1
            print(f"\n--- Batch {batch_num}/{max_batches} ---")
            
            # Always get the first unprocessed records
            df_batch = self.db_manager.execute_safe_select_batch(
                table_name=table_name,
                columns=columns,
                where_conditions="deleted_at IS NULL",
                limit=BATCH_SIZE
            )
            
            batch_size = len(df_batch)
            if batch_size == 0:
                print(f"No more unprocessed records found - processing complete!")
                break
            
            print(f"Loaded {batch_size:,} records in this batch")
            
            # Collect record IDs for this batch
            batch_record_ids = df_batch['id'].tolist()
            
            # Analyze each column for this batch
            batch_anomalies = 0
            available_columns = [col for col in COLUMNS_TO_ANALYZE if col in df_batch.columns]
            
            for column in available_columns:
                anomalies_count = self.analyze_column(df_batch, column, table_name)
                batch_anomalies += anomalies_count
            
            # Update checked_at for this batch
            updated_count = self.db_manager.update_checked_records(table_name, batch_record_ids)
            print(f"Updated checked_at for {updated_count:,} records in this batch")
            
            # Update counters
            total_anomalies += batch_anomalies
            processed_records += batch_size
            
            print(f"Batch {batch_num} summary: {batch_anomalies:,} anomalies found")
            print(f"Progress: {processed_records:,} records processed so far")
            
            # If we processed less than BATCH_SIZE, we're likely at the end
            if batch_size < BATCH_SIZE:
                print(f"Last batch detected (size: {batch_size:,} < {BATCH_SIZE:,})")
                break
        
        # Safety check - warn if we hit the max batch limit
        if batch_num >= max_batches:
            print(f"⚠️  WARNING: Reached maximum batch limit ({max_batches}). This might indicate an infinite loop issue.")
            print(f"   Consider investigating if records are not being marked as processed correctly.")
        
        print(f"\n{table_name} completed:")
        print(f"  Total records processed: {processed_records:,}")
        print(f"  Total anomalies found: {total_anomalies:,}")
        
        return total_anomalies
    
    def run_analysis(self):
        """Run complete anomaly analysis using safe queries"""
        if not self.db_manager.connect():
            print("Failed to connect to database. Please check your connection settings.")
            return
        
        total_anomalies_saved = 0
        
        try:
            # Analyze supplement_business table
            print("Analyzing supplement_business table...")
            total_anomalies_saved += self.analyze_table('supplement_business')
            
            # Analyze market_business table
            print()
            print("Analyzing market_business table...")
            total_anomalies_saved += self.analyze_table('market_business')
            
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
