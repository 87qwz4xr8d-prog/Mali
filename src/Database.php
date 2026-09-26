<?php

declare(strict_types=1);

namespace App;

use mysqli;
use mysqli_stmt;

final class Database
{
    private mysqli $mysqli;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->mysqli = new mysqli(
            (string) $config['host'],
            (string) $config['user'],
            (string) $config['pass'],
            (string) $config['name'],
            (int) $config['port']
        );
        $this->mysqli->set_charset((string) ($config['charset'] ?? 'utf8mb4'));
        $this->mysqli->query("SET time_zone = '+07:00'");
    }

    public function mysqli(): mysqli
    {
        return $this->mysqli;
    }

    /**
     * @param list<int|float|string|null> $params
     * @return list<array<string, mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        $stmt = $this->run($sql, $params);
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return $rows;
    }

    /**
     * @param list<int|float|string|null> $params
     * @return array<string, mixed>|null
     */
    public function one(string $sql, array $params = []): ?array
    {
        return $this->all($sql, $params)[0] ?? null;
    }

    /** @param list<int|float|string|null> $params */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->run($sql, $params);
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected;
    }

    /** @param list<int|float|string|null> $params */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->run($sql, $params);
        $id = (int) $this->mysqli->insert_id;
        $stmt->close();

        return $id;
    }

    /** @param list<int|float|string|null> $params */
    private function run(string $sql, array $params): mysqli_stmt
    {
        $stmt = $this->mysqli->prepare($sql);
        if ($params !== []) {
            $types = '';
            $values = [];
            foreach ($params as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $value;
            }
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();

        return $stmt;
    }
}
