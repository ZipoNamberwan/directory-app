<?php

namespace App\Helpers;

class DatabaseSelector
{
    /**
     * Map database identifiers to actual connections.
     *
     * @return array
     */
    private static function connectionMapping()
    {
        return [
            1 => env('DB_MAIN_CONNECTION', 'mysql_main'),
            2 => env('DB_2_CONNECTION', 'mysql_2'),
            3 => env('DB_3_CONNECTION', 'mysql_3'),
        ];
    }

    /**
     * Regency-to-database mapping (uses numeric identifiers).
     *
     * @return array
     */
    private static function regencyMapping()
    {
        return [
            '3501' => 1,
            '3502' => 1,
            '3503' => 1,
            '3504' => 1,
            '3505' => 1,
            '3506' => 1,
            '3507' => 1,
            '3508' => 1,
            '3509' => 1,
            '3510' => 1,
            '3511' => 1,
            '3512' => 1,
            '3513' => 1,
            '3514' => 2,
            '3515' => 2,
            '3516' => 2,
            '3517' => 2,
            '3518' => 2,
            '3519' => 2,
            '3520' => 2,
            '3521' => 2,
            '3522' => 2,
            '3523' => 2,
            '3524' => 2,
            '3525' => 2,
            '3526' => 3,
            '3527' => 3,
            '3528' => 3,
            '3529' => 3,
            '3571' => 3,
            '3572' => 3,
            '3573' => 3,
            '3574' => 3,
            '3575' => 3,
            '3576' => 3,
            '3577' => 3,
            '3578' => 3,
            '3579' => 3,
        ];
    }

    /**
     * Determine which database connection to use for a given regency.
     *
     * @param string $regency Regency code.
     * @return string Database connection name.
     */
    public static function getConnection($regency)
    {
        $regencyMap = self::regencyMapping();
        $connectionMap = self::connectionMapping();

        $dbIdentifier = $regencyMap[$regency] ?? 1; // Default to 1 (Main DB)
        return $connectionMap[$dbIdentifier] ?? $connectionMap[1]; // Default to Main DB
    }

    /**
     * Get all database connection names.
     *
     * @return array List of database connection names.
     */
    public static function getListConnections()
    {
        return array_values(array_unique(self::connectionMapping()));
    }

    /**
     * Get supported (non-main) database connections.
     *
     * @return array List of supported database connections.
     */
    public static function getSupportConnections()
    {
        return array_values(array_unique(array_diff(self::connectionMapping(), [self::connectionMapping()[1]])));
    }

    /**
     * Get all regencies assigned to a specific database connection.
     *
     * @param string $connection Database connection name.
     * @return array List of regency codes.
     */
    public static function getRegenciesForConnection($connection)
    {
        $connectionMap = array_flip(self::connectionMapping()); // Flip keys & values
        $dbIdentifier = $connectionMap[$connection] ?? 1; // Default to 1 (Main DB)

        return array_keys(array_filter(self::regencyMapping(), fn($id) => $id === $dbIdentifier));
    }
}
