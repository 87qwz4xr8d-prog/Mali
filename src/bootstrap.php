<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$root = dirname(__DIR__);
$example = require $root . '/config/config.example.php';
$configFile = $root . '/config/config.php';

if (is_file($configFile)) {
    $local = require $configFile;
    $config = array_replace_recursive($example, is_array($local) ? $local : []);
} else {
    $config = $example;
}

date_default_timezone_set((string) ($config['app']['timezone'] ?? 'Asia/Bangkok'));

$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = $root . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('company_calendar_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

return $config;
