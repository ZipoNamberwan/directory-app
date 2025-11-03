#!/usr/bin/env python3
"""
Business Duplicate AI Recommendation Generator

This script uses OpenAI's GPT-4 to generate AI recommendations for duplicate business candidates.
It loads unprocessed duplicate candidates, sends them to GPT in batches, and saves the recommendations.

Features:
- Batch processing for efficient API usage
- Configurable batch size
- Customizable AI prompt
- Automatic retry logic for API failures
- Progress tracking and statistics
- Can be run standalone or called from duplicate_detection.py

Usage:
    python duplicate_detection_ai_recommendation.py

Or import and call programmatically:
    from duplicate_detection_ai_recommendation import AIRecommendationGenerator
    generator = AIRecommendationGenerator()
    generator.run()

Requirements:
    - openai
    - mysql-connector-python
    - python-dotenv

Install with: pip install openai mysql-connector-python python-dotenv
"""

import os
import sys
import json
import time
import mysql.connector
import pytz
from datetime import datetime
from typing import List, Dict, Tuple, Optional
from dotenv import load_dotenv

try:
    from openai import OpenAI
except ImportError:
    print("❌ Error: openai package not found. Please install it with: pip install openai")
    sys.exit(1)

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

# OpenAI API Configuration
OPENAI_API_KEY = os.getenv('OPENAI_API_KEY', '')
OPENAI_MODEL = "gpt-5-nano"  # or "gpt-4", "gpt-3.5-turbo", etc.

# Batch processing configuration
BATCH_SIZE = 200  # Number of duplicate candidates to process per API call
MAX_RETRIES = 3  # Maximum number of retries for failed API calls
RETRY_DELAY = 5  # Seconds to wait between retries

# Processing limit (for testing)
DEBUG_MODE = True
DEBUG_LIMIT = 1000  # Limit number of candidates to process in debug mode

# AI Prompt Configuration
DEFAULT_SYSTEM_PROMPT = """Anda adalah seorang ahli analisis data yang berspesialisasi dalam mengidentifikasi duplikasi data usaha.
Tugas Anda adalah mengevaluasi pasangan data usaha dan menentukan apakah mereka adalah duplikat berdasarkan NAMA USAHA SAJA.

PENTING: Hanya fokus pada NAMA USAHA, ABAIKAN informasi pemilik usaha (owner) dalam analisis Anda.

Kedua usaha berjarak ≤70 meter. Artinya jika nama dan konteks mirip sedikit saja, besar kemungkinan itu usaha yang sama.
Untuk setiap pasangan, berikan nilai klasifikasi:
- 1: BUKAN duplikat (nama usaha yang benar-benar berbeda)
- 2: DUPLIKAT (nama usaha yang sama atau sangat mirip)

Pertimbangkan faktor-faktor berikut:
- Kemiripan nama (mempertimbangkan singkatan, kesalahan ketik, dan kata-kata umum usaha)
- Terkadang hanya disertakan nama perusahaannya saja misal Gojek, Tokopedia, dll.
- Terkadang disertakan jenis usaha seperti "Toko", "Warung", "Klinik", dll.
- Kamu harus memahami konteks dari nama usaha tersebut

Kembalikan respons Anda sebagai array JSON yang valid dengan struktur persis seperti ini:
[
  {"duplicate_id": "uuid-di-sini", "value": 2},
  {"duplicate_id": "uuid-di-sini", "value": 1},
  ...
]

PENTING: Kembalikan HANYA array JSON dengan nilai 1 atau 2 saja, tanpa teks atau penjelasan tambahan."""

# Custom prompt (can be overridden)
SYSTEM_PROMPT = DEFAULT_SYSTEM_PROMPT

# Database connection settings
DB_CONFIG = {
    'host': os.getenv('DB_MAIN_HOST', 'localhost'),
    'port': int(os.getenv('DB_MAIN_PORT', 3306)),
    'user': os.getenv('DB_MAIN_USERNAME', 'root'),
    'password': os.getenv('DB_MAIN_PASSWORD', ''),
    'database': os.getenv('DB_MAIN_DATABASE', 'database'),
    'charset': 'utf8mb4'
}

# =====================================================================
# DATA MODELS
# =====================================================================

class DuplicateCandidate:
    """Represents a duplicate candidate record"""
    def __init__(self, candidate_id: str, center_name: str, center_owner: str,
                 nearby_name: str, nearby_owner: str):
        self.id = candidate_id
        self.center_name = center_name or ""
        self.center_owner = center_owner or ""
        self.nearby_name = nearby_name or ""
        self.nearby_owner = nearby_owner or ""
    
    def to_dict(self) -> Dict:
        """Convert to dictionary for API request"""
        return {
            'duplicate_id': self.id,
            'center_business': {
                'name': self.center_name,
                'owner': self.center_owner
            },
            'nearby_business': {
                'name': self.nearby_name,
                'owner': self.nearby_owner
            }
        }
    
    def __repr__(self):
        return f"DuplicateCandidate(id={self.id}, center='{self.center_name}', nearby='{self.nearby_name}')"

# =====================================================================
# DATABASE MANAGER
# =====================================================================

class DatabaseManager:
    """Handles database operations for duplicate candidates"""
    
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
    
    def get_unprocessed_candidates(self, limit: Optional[int] = None) -> List[DuplicateCandidate]:
        """
        Load duplicate candidates that haven't been processed by AI yet
        
        Args:
            limit: Optional limit on number of candidates to load
            
        Returns:
            List of DuplicateCandidate objects
        """
        try:
            cursor = self.connection.cursor(dictionary=True)
            
            # Build query
            query = """
            SELECT 
                id,
                center_business_name,
                center_business_owner,
                nearby_business_name,
                nearby_business_owner
            FROM duplicate_candidates
            WHERE ai_recommendation IS NULL
            ORDER BY created_at DESC
            """
            
            if limit:
                query += f" LIMIT {limit}"
            
            print(f"📊 Loading unprocessed duplicate candidates...")
            cursor.execute(query)
            results = cursor.fetchall()
            cursor.close()
            
            # Convert to DuplicateCandidate objects
            candidates = []
            for row in results:
                candidate = DuplicateCandidate(
                    candidate_id=str(row['id']),
                    center_name=row['center_business_name'],
                    center_owner=row['center_business_owner'],
                    nearby_name=row['nearby_business_name'],
                    nearby_owner=row['nearby_business_owner']
                )
                candidates.append(candidate)
            
            print(f"✓ Loaded {len(candidates)} unprocessed candidates")
            return candidates
            
        except Exception as e:
            print(f"❌ Error loading candidates: {e}")
            raise
    
    def get_exact_match_candidates(self, candidates: List[DuplicateCandidate]) -> Tuple[List[Dict], List[DuplicateCandidate]]:
        """
        Separate candidates with exact name matches from those needing AI analysis
        
        Args:
            candidates: List of all DuplicateCandidate objects
            
        Returns:
            Tuple of (exact_matches_recommendations, remaining_candidates)
        """
        exact_matches = []
        remaining = []
        
        for candidate in candidates:
            # Normalize names for comparison (case-insensitive)
            center_name_normalized = (candidate.center_name or "").strip().lower()
            nearby_name_normalized = (candidate.nearby_name or "").strip().lower()
            
            # Check if names are exactly identical
            if center_name_normalized and nearby_name_normalized and center_name_normalized == nearby_name_normalized:
                # Exact match - automatically mark as duplicate (value=2)
                exact_matches.append({
                    'duplicate_id': candidate.id,
                    'value': 2  # Duplicate
                })
            else:
                # Need AI analysis
                remaining.append(candidate)
        
        if exact_matches:
            print(f"  ⚡ Found {len(exact_matches)} candidates with exact name matches (auto-marked as duplicates)")
        
        return exact_matches, remaining
    
    def update_ai_recommendation(self, candidate_id: str, ai_value: int) -> bool:
        """
        Update a single candidate's AI recommendation
        
        Args:
            candidate_id: UUID of the duplicate candidate
            ai_value: AI recommendation value (1=not duplicate, 2=duplicate)
            
        Returns:
            True if successful, False otherwise
        """
        try:
            cursor = self.connection.cursor()
            current_timestamp = get_jakarta_now()
            
            query = """
            UPDATE duplicate_candidates
            SET ai_recommendation = %s,
                updated_at = %s
            WHERE id = %s
            """
            
            cursor.execute(query, (ai_value, current_timestamp, candidate_id))
            self.connection.commit()
            cursor.close()
            return True
            
        except Exception as e:
            print(f"⚠️  Error updating candidate {candidate_id}: {e}")
            return False
    
    def batch_update_ai_recommendations(self, recommendations: List[Dict]) -> Tuple[int, int]:
        """
        Batch update AI recommendations
        
        Args:
            recommendations: List of dicts with 'duplicate_id' and 'value' keys
            
        Returns:
            Tuple of (successful_updates, failed_updates)
        """
        if not recommendations:
            return 0, 0
        
        successful = 0
        failed = 0
        current_timestamp = get_jakarta_now()
        
        try:
            cursor = self.connection.cursor()
            
            for rec in recommendations:
                try:
                    candidate_id = rec.get('duplicate_id')
                    ai_value = rec.get('value')
                    
                    if not candidate_id or ai_value is None:
                        print(f"⚠️  Skipping invalid recommendation: {rec}")
                        failed += 1
                        continue
                    
                    query = """
                    UPDATE duplicate_candidates
                    SET ai_recommendation = %s,
                        updated_at = %s
                    WHERE id = %s
                    """
                    
                    cursor.execute(query, (ai_value, current_timestamp, candidate_id))
                    successful += 1
                    
                except Exception as e:
                    print(f"⚠️  Error updating candidate {rec.get('duplicate_id')}: {e}")
                    failed += 1
            
            self.connection.commit()
            cursor.close()
            
        except Exception as e:
            print(f"❌ Fatal error in batch update: {e}")
            failed = len(recommendations)
            successful = 0
        
        return successful, failed

# =====================================================================
# OPENAI API MANAGER
# =====================================================================

class OpenAIManager:
    """Handles OpenAI API interactions"""
    
    def __init__(self, api_key: str, model: str = OPENAI_MODEL):
        if not api_key:
            raise ValueError("OpenAI API key not found. Please set OPENAI_API_KEY in .env file")
        
        self.client = OpenAI(api_key=api_key)
        self.model = model
        self.system_prompt = SYSTEM_PROMPT
    
    def set_system_prompt(self, prompt: str):
        """Update the system prompt"""
        self.system_prompt = prompt
    
    def create_user_prompt(self, candidates: List[DuplicateCandidate]) -> str:
        """
        Create user prompt from batch of candidates (names only)
        
        Args:
            candidates: List of DuplicateCandidate objects
            
        Returns:
            Formatted prompt string with only business names
        """
        # Create simplified data with only business names
        candidates_data = []
        for c in candidates:
            candidates_data.append({
                'duplicate_id': c.id,
                'center_business_name': c.center_name,
                'nearby_business_name': c.nearby_name
            })
        
        prompt = f"""Silakan evaluasi {len(candidates)} pasangan usaha berikut dan tentukan apakah mereka adalah duplikat berdasarkan NAMA saja.
Untuk setiap pasangan, berikan nilai klasifikasi: 1 (bukan duplikat) atau 2 (duplikat).

Pasangan usaha yang akan dievaluasi (HANYA NAMA):
{json.dumps(candidates_data, indent=2, ensure_ascii=False)}

Ingat untuk mengembalikan HANYA array JSON dengan struktur ini:
[
  {{"duplicate_id": "uuid", "value": skor}},
  ...
]"""
        
        return prompt
    
    def get_recommendations(self, candidates: List[DuplicateCandidate], 
                          retry_count: int = 0) -> Optional[List[Dict]]:
        """
        Get AI recommendations for a batch of candidates
        
        Args:
            candidates: List of DuplicateCandidate objects
            retry_count: Current retry attempt
            
        Returns:
            List of recommendation dicts or None if failed
        """
        try:
            user_prompt = self.create_user_prompt(candidates)
            
            print(f"  🤖 Calling OpenAI API ({self.model})...")
            
            response = self.client.chat.completions.create(
                model=self.model,
                messages=[
                    {"role": "system", "content": self.system_prompt},
                    {"role": "user", "content": user_prompt}
                ],
                # temperature=0.3,  # Lower temperature for more consistent results
                response_format={"type": "json_object"} if "gpt-4" in self.model else None
            )
            
            # Extract response content
            content = response.choices[0].message.content.strip()
            
            # Parse JSON response
            try:
                # Try to extract JSON array from response
                if content.startswith('['):
                    recommendations = json.loads(content)
                elif '{' in content:
                    # Try to find JSON array in the response
                    start_idx = content.find('[')
                    end_idx = content.rfind(']') + 1
                    if start_idx != -1 and end_idx > start_idx:
                        json_str = content[start_idx:end_idx]
                        recommendations = json.loads(json_str)
                    else:
                        raise ValueError("No JSON array found in response")
                else:
                    raise ValueError("Response is not valid JSON")
                
                # Validate response structure
                if not isinstance(recommendations, list):
                    raise ValueError("Response is not a list")
                
                # Validate each recommendation
                valid_recommendations = []
                for rec in recommendations:
                    if isinstance(rec, dict) and 'duplicate_id' in rec and 'value' in rec:
                        # Ensure value is integer 1 or 2
                        value = int(rec['value'])
                        if value in [1, 2]:
                            valid_recommendations.append({
                                'duplicate_id': str(rec['duplicate_id']),
                                'value': value
                            })
                        else:
                            print(f"⚠️  Invalid value {value} for {rec['duplicate_id']} (must be 1 or 2), skipping")
                    else:
                        print(f"⚠️  Invalid recommendation format: {rec}")
                
                print(f"  ✓ Received {len(valid_recommendations)} valid recommendations")
                return valid_recommendations
                
            except json.JSONDecodeError as e:
                print(f"⚠️  Failed to parse JSON response: {e}")
                print(f"  Response content: {content[:200]}...")
                raise
            
        except Exception as e:
            print(f"⚠️  API call failed: {e}")
            
            # Retry logic
            if retry_count < MAX_RETRIES:
                print(f"  🔄 Retrying in {RETRY_DELAY} seconds... (attempt {retry_count + 1}/{MAX_RETRIES})")
                time.sleep(RETRY_DELAY)
                return self.get_recommendations(candidates, retry_count + 1)
            else:
                print(f"  ❌ Max retries reached, giving up on this batch")
                return None

# =====================================================================
# MAIN AI RECOMMENDATION GENERATOR
# =====================================================================

class AIRecommendationGenerator:
    """Main engine for generating AI recommendations"""
    
    def __init__(self, batch_size: int = BATCH_SIZE, system_prompt: Optional[str] = None):
        """
        Initialize the AI recommendation generator
        
        Args:
            batch_size: Number of candidates to process per API call
            system_prompt: Optional custom system prompt for the AI
        """
        self.batch_size = batch_size
        self.db_manager = DatabaseManager(DB_CONFIG)
        self.openai_manager = OpenAIManager(OPENAI_API_KEY, OPENAI_MODEL)
        
        if system_prompt:
            self.openai_manager.set_system_prompt(system_prompt)
    
    def run(self) -> Dict:
        """
        Execute the complete AI recommendation generation process
        
        Returns:
            Dictionary with statistics about the run
        """
        start_time = time.time()
        stats = {
            'total_candidates': 0,
            'processed': 0,
            'successful': 0,
            'failed': 0,
            'exact_matches': 0,
            'batches': 0,
            'api_calls': 0,
            'duration': 0
        }
        
        try:
            print("🤖 Starting AI Recommendation Generation")
            print("=" * 70)
            print(f"Configuration:")
            print(f"  - OpenAI Model: {self.openai_manager.model}")
            print(f"  - Batch size: {self.batch_size}")
            print(f"  - Max retries: {MAX_RETRIES}")
            print(f"  - Analysis mode: NAME ONLY (ignoring owner)")
            if DEBUG_MODE:
                print(f"  - Debug mode: ON (limit: {DEBUG_LIMIT})")
            print("-" * 70)
            
            # Connect to database
            self.db_manager.connect()
            
            # Load unprocessed candidates
            limit = DEBUG_LIMIT if DEBUG_MODE else None
            candidates = self.db_manager.get_unprocessed_candidates(limit)
            
            if not candidates:
                print("✅ No unprocessed candidates found. All candidates have AI recommendations!")
                return stats
            
            stats['total_candidates'] = len(candidates)
            print(f"✓ Found {len(candidates)} candidates to process")
            
            # Separate exact matches from candidates needing AI analysis
            print(f"\n🔍 Checking for exact name matches...")
            exact_match_recommendations, remaining_candidates = self.db_manager.get_exact_match_candidates(candidates)
            
            # Process exact matches
            if exact_match_recommendations:
                print(f"  💾 Saving {len(exact_match_recommendations)} exact match recommendations to database...")
                successful, failed = self.db_manager.batch_update_ai_recommendations(exact_match_recommendations)
                stats['exact_matches'] = successful
                stats['successful'] += successful
                stats['failed'] += failed
                print(f"  ✓ Exact matches saved: {successful} successful, {failed} failed")
            
            # Process remaining candidates with AI if any
            if not remaining_candidates:
                print("\n✅ All candidates were exact matches!")
                end_time = time.time()
                stats['duration'] = end_time - start_time
                
                print("\n" + "=" * 70)
                print("✅ AI Recommendation Generation Complete")
                print("=" * 70)
                print(f"� Final Statistics:")
                print(f"  - Total candidates: {stats['total_candidates']:,}")
                print(f"  - Exact matches (auto-detected): {stats['exact_matches']:,}")
                print(f"  - Successfully processed: {stats['successful']:,}")
                print(f"  - Failed: {stats['failed']:,}")
                print(f"  - Duration: {stats['duration']:.1f}s" if stats['duration'] > 0 else "  - Duration: 0s")
                print(f"  - Success rate: {(stats['successful']/stats['total_candidates']*100):.1f}%" if stats['total_candidates'] > 0 else "  - Success rate: 0%")
                
                return stats
            
            print(f"\n�🔄 Processing remaining {len(remaining_candidates)} candidates with AI in batches of {self.batch_size}...")
            
            # Process remaining candidates in batches
            for i in range(0, len(remaining_candidates), self.batch_size):
                batch_num = (i // self.batch_size) + 1
                batch = remaining_candidates[i:i + self.batch_size]
                
                print(f"\n📦 Batch {batch_num}/{(len(remaining_candidates) + self.batch_size - 1) // self.batch_size}")
                print(f"  Processing {len(batch)} candidates (IDs: {i+1}-{i+len(batch)})")
                
                # Get AI recommendations
                stats['api_calls'] += 1
                recommendations = self.openai_manager.get_recommendations(batch)
                
                if recommendations:
                    # Update database
                    print(f"  💾 Saving {len(recommendations)} recommendations to database...")
                    successful, failed = self.db_manager.batch_update_ai_recommendations(recommendations)
                    
                    stats['processed'] += len(batch)
                    stats['successful'] += successful
                    stats['failed'] += failed
                    stats['batches'] += 1
                    
                    print(f"  ✓ Batch complete: {successful} successful, {failed} failed")
                else:
                    print(f"  ❌ Batch failed: Could not get AI recommendations")
                    stats['failed'] += len(batch)
                
                # Progress update
                progress_pct = ((i + len(batch)) / len(remaining_candidates)) * 100
                print(f"  📊 Batch progress: {progress_pct:.1f}% ({i + len(batch)}/{len(remaining_candidates)})")
            
            # Final statistics
            end_time = time.time()
            stats['duration'] = end_time - start_time
            
            print("\n" + "=" * 70)
            print("✅ AI Recommendation Generation Complete")
            print("=" * 70)
            print(f"📊 Final Statistics:")
            print(f"  - Total candidates: {stats['total_candidates']:,}")
            print(f"  - Exact matches (auto-detected): {stats['exact_matches']:,}")
            print(f"  - Processed via AI: {stats['processed']:,}")
            print(f"  - Successfully processed: {stats['successful']:,}")
            print(f"  - Failed: {stats['failed']:,}")
            print(f"  - Batches processed: {stats['batches']}")
            print(f"  - API calls made: {stats['api_calls']}")
            print(f"  - Duration: {stats['duration']:.1f}s ({stats['successful']/stats['duration']:.1f} candidates/sec)" if stats['duration'] > 0 else "  - Duration: 0s")
            print(f"  - Success rate: {(stats['successful']/stats['total_candidates']*100):.1f}%" if stats['total_candidates'] > 0 else "  - Success rate: 0%")
            
        except Exception as e:
            print(f"\n❌ Error during AI recommendation generation: {e}")
            import traceback
            traceback.print_exc()
        finally:
            self.db_manager.disconnect()
        
        return stats

# =====================================================================
# MAIN EXECUTION
# =====================================================================

def main():
    """Main function for standalone execution"""
    try:
        # Check for API key
        if not OPENAI_API_KEY:
            print("❌ Error: OPENAI_API_KEY not found in environment variables")
            print("   Please add it to your .env file:")
            print("   OPENAI_API_KEY=your-api-key-here")
            sys.exit(1)
        
        # Create generator and run
        generator = AIRecommendationGenerator(
            batch_size=BATCH_SIZE,
            system_prompt=SYSTEM_PROMPT
        )
        
        stats = generator.run()
        
        # Exit with appropriate code
        if stats['failed'] > 0:
            sys.exit(1)  # Partial failure
        elif stats['successful'] == 0:
            sys.exit(2)  # Complete failure
        else:
            sys.exit(0)  # Success
        
    except KeyboardInterrupt:
        print("\n\n⚠️  Process interrupted by user")
        sys.exit(130)
    except Exception as e:
        print(f"\n❌ Fatal error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
