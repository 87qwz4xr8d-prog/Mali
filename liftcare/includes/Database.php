<?php

declare(strict_types=1);

/**
 * การเชื่อมต่อฐานข้อมูลด้วย mysqli
 */
final class Database
{
    private static ?mysqli $conn = null;

    public static function conn(): mysqli
    {
        if (self::$conn instanceof mysqli) {
            return self::$conn;
        }

        $configFile = dirname(__DIR__) . '/config/database.php';
        if (!is_file($configFile)) {
            $configFile = dirname(__DIR__) . '/config/database.example.php';
        }

        /** @var array{host:string,port:int,username:string,password:string,database:string,charset:string} $cfg */
        $cfg = require $configFile;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $conn = new mysqli(
            $cfg['host'],
            $cfg['username'],
            $cfg['password'],
            $cfg['database'],
            (int) ($cfg['port'] ?? 3306)
        );
        $conn->set_charset($cfg['charset'] ?? 'utf8mb4');
        $conn->query("SET time_zone = '+07:00'");

        self::$conn = $conn;
        return self::$conn;
    }
}
