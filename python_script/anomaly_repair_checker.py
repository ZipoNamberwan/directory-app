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

# Import the AnomalyDetector and IgnoreWordsManager from the main script
import sys
sys.path.append(os.path.dirname(__file__))
from anomaly_detector_clean import AnomalyDetector, IgnoreWordsManager, get_jakarta_now

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

# Anomaly type to column mapping
ANOMALY_TYPE_TO_COLUMN = {
    1: 'name',
    2: 'description', 
    3: 'address',
    4: 'owner',
    5: 'sector'
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
    'regency_id', 'subdistrict_id', 'village_id', 'sls_id', 'market_id', 'user_id', 'deleted_at'
]

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

# =====================================================================
# DATABASE OPERATIONS
# =====================================================================

class RepairDatabaseManager:
    """Handles database connections for anomaly repair checking"""
    
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
        
        # Check connection health and reconnect if needed
        try:
            self.connection.ping(reconnect=True, attempts=3, delay=1)
        except Exception as e:
            print(f"Database connection lost, attempting to reconnect: {e}")
            if not self.connect():
                raise Exception("Failed to reconnect to database")
        
        cursor = self.connection.cursor(dictionary=True)
        try:
            if params:
                cursor.execute(query, params)
            else:
                cursor.execute(query)
            results = cursor.fetchall()
            return pd.DataFrame(results)
        finally:
            cursor.close()
    
    def get_notconfirmed_anomaly_repairs(self, business_type=None, limit=None):
        """Get anomaly repairs with status 'notconfirmed'"""
        if not self.connection:
            raise Exception("No database connection")
        
        query = "SELECT id, business_id, business_type, anomaly_type_id, old_value FROM anomaly_repairs WHERE status = 'notconfirmed'"
        params = []
        
        if business_type:
            query += " AND business_type = %s"
            params.append(business_type)
        
        if limit:
            query += " LIMIT %s"
            params.append(limit)
        
        return self.execute_query_to_dataframe(query, params if params else None)
    
    def get_business_current_values(self, table_name, business_ids, columns):
        """Get current values for specific businesses and columns (including soft-deleted ones)"""
        if not self.connection:
            raise Exception("No database connection")
        
        if not business_ids:
            return pd.DataFrame()
        
        # Validate business_ids
        if not isinstance(business_ids, list) or len(business_ids) == 0:
            return pd.DataFrame()
        
        # Filter out None/null business_ids
        valid_business_ids = [bid for bid in business_ids if bid is not None]
        if not valid_business_ids:
            return pd.DataFrame()
        
        # Validate inputs
        validated_table = validate_table_name(table_name)
        # Always include deleted_at to check for soft-deleted records
        validated_columns = validate_column_names(['id', 'user_id', 'deleted_at'] + columns)
        
        # Build column list
        column_list = ', '.join(validated_columns)
        
        # Build placeholders for the IN clause
        placeholders = ', '.join(['%s'] * len(valid_business_ids))
        
        query = f"SELECT {column_list} FROM {validated_table} WHERE id IN ({placeholders})"
        
        return self.execute_query_to_dataframe(query, valid_business_ids)
    
    def update_anomaly_repair_to_fixed(self, repair_id, new_value, user_id=None):
        """Update anomaly repair status to 'fixed' with new value, repaired_by and repaired_at"""
        if not self.connection:
            raise Exception("No database connection")
        
        try:
            cursor = self.connection.cursor()
            now = get_jakarta_now()
            
            update_query = """
            UPDATE anomaly_repairs 
            SET status = 'fixed', fixed_value = %s, last_repaired_by = %s, repaired_at = %s, updated_at = %s 
            WHERE id = %s
            """
            
            cursor.execute(update_query, (new_value, user_id, now, now, repair_id))
            updated_count = cursor.rowcount
            
            self.connection.commit()
            cursor.close()
            
            return updated_count > 0
        except Exception as e:
            print(f"Error updating anomaly repair {repair_id}: {e}")
            self.connection.rollback()
            return False
    
    def update_anomaly_repair_to_deleted(self, repair_id):
        """Update anomaly repair status to 'deleted' when business is soft-deleted"""
        if not self.connection:
            raise Exception("No database connection")
        
        try:
            cursor = self.connection.cursor()
            now = get_jakarta_now()
            
            update_query = """
            UPDATE anomaly_repairs 
            SET status = 'deleted', updated_at = %s 
            WHERE id = %s
            """
            
            cursor.execute(update_query, (now, repair_id))
            updated_count = cursor.rowcount
            
            self.connection.commit()
            cursor.close()
            
            return updated_count > 0
        except Exception as e:
            print(f"Error updating anomaly repair {repair_id} to deleted: {e}")
            self.connection.rollback()
            return False
    
    def get_repair_statistics(self):
        """Get statistics about anomaly repairs"""
        if not self.connection:
            raise Exception("No database connection")
        
        stats_query = """
        SELECT 
            status,
            COUNT(*) as count
        FROM anomaly_repairs 
        GROUP BY status
        """
        
        return self.execute_query_to_dataframe(stats_query)

# =====================================================================
# ANOMALY REPAIR CHECKER
# =====================================================================

class AnomalyRepairChecker:
    """Main class for checking and updating anomaly repairs"""
    
    def __init__(self, dry_run=False):
        self.ignore_words_manager = IgnoreWordsManager()
        self.detector = AnomalyDetector(ignore_words_manager=self.ignore_words_manager)
        self.db_manager = RepairDatabaseManager()
        self.dry_run = dry_run
    
    def check_repairs_by_business_type(self, business_type_class=None, limit=None):
        """Check anomaly repairs for a specific business type or all types"""
        mode_text = "DRY RUN - Counting" if self.dry_run else "Checking"
        print(f"\n{mode_text} anomaly repairs...")
        
        if self.dry_run:
            print("🔍 DRY RUN MODE: Will only count repairs that would be fixed, no actual updates will be performed.")
        
        if business_type_class:
            print(f"Business type filter: {business_type_class}")
        
        # Get notconfirmed anomaly repairs
        anomaly_repairs = self.db_manager.get_notconfirmed_anomaly_repairs(business_type_class, limit)
        
        if len(anomaly_repairs) == 0:
            print("No notconfirmed anomaly repairs found.")
            return 0
        
        print(f"Found {len(anomaly_repairs):,} notconfirmed anomaly repairs to check")
        
        # Group by business type and table for efficient querying
        fixed_count = 0
        total_deleted_count = 0
        
        for business_type in anomaly_repairs['business_type'].unique():
            # Determine table name from business type
            table_name = None
            for table, model_class in BUSINESS_TYPE_MAPPING.items():
                if model_class == business_type:
                    table_name = table
                    break
            
            if not table_name:
                print(f"Warning: Unknown business type {business_type}, skipping...")
                continue
            
            # Get repairs for this business type
            type_repairs = anomaly_repairs[anomaly_repairs['business_type'] == business_type]
            
            print(f"\nChecking {len(type_repairs)} repairs for {table_name}...")
            
            # Group by anomaly type to batch queries
            for anomaly_type_id in type_repairs['anomaly_type_id'].unique():
                column_name = ANOMALY_TYPE_TO_COLUMN.get(anomaly_type_id)
                if not column_name:
                    print(f"Warning: Unknown anomaly type {anomaly_type_id}, skipping...")
                    continue
                
                # Get repairs for this anomaly type
                type_anomaly_repairs = type_repairs[type_repairs['anomaly_type_id'] == anomaly_type_id]
                business_ids = type_anomaly_repairs['business_id'].tolist()
                
                print(f"  Checking {len(type_anomaly_repairs)} {column_name} repairs...")
                
                # Get current business values
                current_values = self.db_manager.get_business_current_values(
                    table_name, business_ids, [column_name]
                )
                
                if len(current_values) == 0:
                    print(f"    No current values found for {len(business_ids)} businesses")
                    continue
                
                column_fixed_count = 0
                checked_count = 0
                no_change_count = 0
                still_anomalous_count = 0
                deleted_count = 0
                
                # Check each repair
                for _, repair in type_anomaly_repairs.iterrows():
                    business_id = repair['business_id']
                    old_value = repair['old_value']
                    repair_id = repair['id']
                    checked_count += 1
                    
                    # Find current value for this business
                    current_business = current_values[current_values['id'] == business_id]
                    if len(current_business) == 0:
                        # Business not found (may be hard deleted), skip this repair
                        continue
                    
                    try:
                        current_value = current_business.iloc[0][column_name]
                        user_id = current_business.iloc[0]['user_id'] if 'user_id' in current_business.columns else None
                        deleted_at = current_business.iloc[0]['deleted_at'] if 'deleted_at' in current_business.columns else None
                    except (KeyError, IndexError) as e:
                        print(f"    WARNING: Error accessing data for business {business_id}: {e}")
                        continue
                    
                    # Check if business is soft-deleted (use pd.notna for pandas null check)
                    if pd.notna(deleted_at):
                        print(f"    DEBUG: Business {business_id} ({column_name}):")
                        print(f"      Status: DELETED (deleted_at: {deleted_at})")
                        
                        if self.dry_run:
                            deleted_count += 1
                            print(f"      📊 WOULD BE MARKED AS DELETED (dry run)")
                        else:
                            success = self.db_manager.update_anomaly_repair_to_deleted(repair_id)
                            
                            if success:
                                deleted_count += 1
                                print(f"      🗑️  MARKED AS DELETED")
                            else:
                                print(f"      ❌ Failed to update repair {repair_id}")
                        
                        print()  # Empty line for readability
                        continue
                    
                    # Compare old_value with current_value
                    old_str = str(old_value).strip() if old_value is not None else ""
                    current_str = str(current_value).strip() if current_value is not None else ""
                    
                    if old_str != current_str:
                        # Only show debug for values that have changed
                        print(f"    DEBUG: Business {business_id} ({column_name}):")
                        print(f"      Old value: '{old_str}'")
                        print(f"      Current value: '{current_str}'")
                        print(f"      User ID: {user_id}")
                        
                        # Value has changed, check if current value passes anomaly detector
                        is_anomaly, reason = self.detector.is_anomaly(
                            current_value, 
                            column_name=column_name,
                            return_reason=True
                        )
                        
                        print(f"      Is current value anomalous: {is_anomaly} (reason: {reason})")
                        
                        if not is_anomaly:
                            # Current value passes the check (not anomalous)
                            if self.dry_run:
                                # Dry run - just count, don't update
                                column_fixed_count += 1
                                print(f"      📊 WOULD BE FIXED (dry run)")
                            else:
                                # Actually update the repair
                                success = self.db_manager.update_anomaly_repair_to_fixed(
                                    repair_id, current_value, user_id
                                )
                                
                                if success:
                                    column_fixed_count += 1
                                    print(f"      ✅ MARKED AS FIXED")
                                else:
                                    print(f"      ❌ Failed to update repair {repair_id}")
                        else:
                            still_anomalous_count += 1
                            print(f"      ⚠️  Still anomalous (reason: {reason})")
                        
                        print()  # Empty line for readability
                    else:
                        no_change_count += 1
                
                action_text = "Would be fixed" if self.dry_run else "Fixed"
                deleted_text = "Would be marked deleted" if self.dry_run else "Marked deleted"
                print(f"    {column_name} Summary:")
                print(f"      Total checked: {checked_count}")
                print(f"      {deleted_text}: {deleted_count}")
                print(f"      No change: {no_change_count}")
                print(f"      Changed but still anomalous: {still_anomalous_count}")
                print(f"      {action_text}: {column_fixed_count}")
                print()
                fixed_count += column_fixed_count
                total_deleted_count += deleted_count
        
        summary_text = f"Total repairs that would be fixed: {fixed_count}" if self.dry_run else f"Total repairs marked as fixed: {fixed_count}"
        deleted_summary_text = f"Total repairs that would be marked deleted: {total_deleted_count}" if self.dry_run else f"Total repairs marked as deleted: {total_deleted_count}"
        print(f"\n{summary_text}")
        print(f"{deleted_summary_text}")
        return fixed_count
    
    def show_statistics(self):
        """Show current statistics about anomaly repairs"""
        print("\nAnomaly Repair Statistics:")
        print("=" * 40)
        
        try:
            stats = self.db_manager.get_repair_statistics()
            
            if len(stats) == 0:
                print("No anomaly repair records found.")
                return
            
            total = stats['count'].sum()
            print(f"Total repairs: {total:,}")
            print()
            
            for _, row in stats.iterrows():
                status = row['status']
                count = row['count']
                percentage = (count / total) * 100
                print(f"{status:>15}: {count:>8,} ({percentage:5.1f}%)")
                
        except Exception as e:
            print(f"Error getting statistics: {e}")
    
    def run_repair_check(self, business_type=None, limit=None, show_stats=True):
        """Run the complete anomaly repair checking process"""
        if not self.db_manager.connect():
            print("Failed to connect to database. Please check your connection settings.")
            return
        
        try:
            if show_stats:
                print("BEFORE REPAIR CHECK")
                self.show_statistics()
            
            print("\n" + "=" * 60)
            print("ANOMALY REPAIR CHECKING")
            print("=" * 60)
            
            fixed_count = self.check_repairs_by_business_type(business_type, limit)
            
            if show_stats:
                print("\n" + "=" * 60)
                print("AFTER REPAIR CHECK")
                self.show_statistics()
            
            print("\n" + "=" * 60)
            completion_text = "DRY RUN COMPLETE" if self.dry_run else "REPAIR CHECK COMPLETE"
            print(completion_text)
            print("=" * 60)
            final_summary = f"Total repairs that would be fixed: {fixed_count}" if self.dry_run else f"Total repairs marked as fixed: {fixed_count}"
            print(final_summary)
            
            return fixed_count
            
        except Exception as e:
            print(f"Error during repair check: {e}")
            import traceback
            traceback.print_exc()
            return 0
        finally:
            self.db_manager.disconnect()

# =====================================================================
# MAIN EXECUTION
# =====================================================================

def main():
    """Main entry point"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Check anomaly repairs and mark fixed ones')
    parser.add_argument('--business-type', type=str, help='Filter by business type (e.g., App\\Models\\SupplementBusiness)')
    parser.add_argument('--limit', type=int, help='Limit number of repairs to check')
    parser.add_argument('--no-stats', action='store_true', help='Skip showing statistics')
    parser.add_argument('--dry-run', action='store_true', help='Count repairs that would be fixed without actually updating them')
    
    args = parser.parse_args()
    
    checker = AnomalyRepairChecker(dry_run=args.dry_run)
    checker.run_repair_check(
        business_type=args.business_type,
        limit=args.limit,
        show_stats=not args.no_stats
    )

if __name__ == "__main__":
    main()