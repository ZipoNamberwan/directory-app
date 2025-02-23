import pandas as pd
import pymysql
from datetime import datetime
import os
from dotenv import load_dotenv
from pathlib import Path


def load_environment():
    """Load environment variables from parent directory's .env file"""
    # Get the parent directory of the current script
    env_path = Path('./.env')

    if not env_path.exists():
        raise FileNotFoundError(f"Environment file not found at: {env_path}")

    # Load environment variables from the .env file
    load_dotenv(env_path)

    # required_vars = ['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE', 'DB_PORT']
    # missing_vars = [var for var in required_vars if not os.getenv(var)]

    # if missing_vars:
    #     raise ValueError(f"Missing required environment variables: {', '.join(missing_vars)}")


def connect_to_mysql():
    """Create MySQL database connection using environment variables"""
    print("Connecting to mysql server...")
    try:
        conn = pymysql.connect(
            host=os.getenv('DB_HOST'),
            user=os.getenv('DB_USERNAME'),
            password=os.getenv('DB_PASSWORD'),
            database=os.getenv('DB_DATABASE'),
            # Default to 3306 if not specified
            port=int(os.getenv('DB_PORT', '3306'))
        )
        print("Successfully connected to MySQL database")
        return conn
    except Exception as e:
        print(f"Error connecting to MySQL database: {str(e)}")
        raise


def get_data_from_db(connection, query):
    """Retrieve data from MySQL database using the provided query"""
    try:
        df = pd.read_sql_query(query, connection)
        print(f"Successfully retrieved {len(df)} records from database")
        return df
    except Exception as e:
        print(f"Error executing query: {str(e)}")
        raise


def save_to_excel(df, output_path=None):
    """Save the DataFrame to an Excel file"""
    try:
        if output_path is None:
            # Save Excel file in the parent directory
            current_dir = Path(__file__).resolve().parent
            parent_dir = current_dir.parent
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            output_path = parent_dir / f'mysql_export_{timestamp}.xlsx'

        # Create Excel writer with xlsxwriter engine
        with pd.ExcelWriter(output_path, engine='xlsxwriter') as writer:
            df.to_excel(writer, sheet_name='Data', index=False)

            # Auto-adjust columns width
            worksheet = writer.sheets['Data']
            for i, col in enumerate(df.columns):
                column_len = max(df[col].astype(
                    str).apply(len).max(), len(col)) + 2
                worksheet.set_column(i, i, column_len)

        print(f"Data successfully exported to {output_path}")
        return output_path
    except Exception as e:
        print(f"Error saving to Excel: {str(e)}")
        raise


def main():
    """Main function to orchestrate the export process"""

    try:
        # Load environment variables
        load_environment()

        # Your SQL query - modify this according to your needs
        query = """
        SELECT *
        FROM non_sls_business;
        """

        # Connect to MySQL database
        conn = connect_to_mysql()
        # Get data
        df = get_data_from_db(conn, query)
        # Save to Excel
        output_file = save_to_excel(df)

        print(f"Export completed successfully. File saved as: {output_file}")

    except Exception as e:
        print(f"Export failed: {str(e)}")
    finally:
        if conn:
            conn.close()

if __name__ == "__main__":
    main()
