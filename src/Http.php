<?php

declare(strict_types=1);

namespace App;

final class Http
{
    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($uri) || $uri === '') {
            $uri = '/';
        }
        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : $uri;
    }

    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    /** @return array<string, mixed> */
    public static function input(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $data = json_decode(is_string($raw) ? $raw : '', true);

            return is_array($data) ? $data : [];
        }

        return $_POST;
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    /** @param array<string, mixed> $data */
    public static function view(string $name, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');

        $contentView = dirname(__DIR__) . '/views/' . $name . '.php';
        if (!is_file($contentView)) {
            $contentView = dirname(__DIR__) . '/views/not_found.php';
        }
        $data['contentView'] = $contentView;
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/views/layout.php';
        exit;
    }

    public static function redirect(string $path): never
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            $path = '/calendar';
        }
        header('Location: ' . $path, true, 302);
        exit;
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** @param array<string, mixed> $payload */
    public static function jsonScript(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }
}
