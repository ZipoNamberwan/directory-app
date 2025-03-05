<?php

namespace App\Helpers;

class DatabaseSelector
{
    /**
     * Determine which database connection to use.
     * @param array $params (Optional criteria like user ID, request type, etc.)
     * @return string Database connection name
     */
    public static function getConnection($regency)
    {
        if (in_array($regency, ['3502'])) {
            return env('DB_2_CONNECTION', 'mysql_2');
        } elseif (in_array($regency, ['3503'])) {
            return env('DB_3_CONNECTION', 'mysql_3');
        }
        return env('DB_MAIN_CONNECTION', 'mysql_main');
    }

    public static function getListConnections()
    {
        return [
            env('DB_MAIN_CONNECTION', 'mysql_main'),
            env('DB_2_CONNECTION', 'mysql_2'),
            env('DB_3_CONNECTION', 'mysql_3')
        ];
    }

    public static function getSupportConnections()
    {
        return [
            env('DB_2_CONNECTION', 'mysql_2'),
            env('DB_3_CONNECTION', 'mysql_3')
        ];
    }
}
