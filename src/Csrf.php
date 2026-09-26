<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        $token = $_SESSION['csrf'] ?? '';
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf'] = $token;
        }

        return $token;
    }

    public static function rotate(): string
    {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        return $_SESSION['csrf'];
    }

    public static function check(?string $token): bool
    {
        $expected = $_SESSION['csrf'] ?? '';
        if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function fromRequest(): ?string
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (is_string($header) && $header !== '') {
            return $header;
        }
        $post = $_POST['_csrf'] ?? null;

        return is_string($post) ? $post : null;
    }
}
