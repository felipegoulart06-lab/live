<?php

declare(strict_types=1);

namespace App\Core;

use PDOStatement;
use Throwable;

final class Db
{
    /** Append after `LIKE :param` so escaped wildcards behave the same in SQLite and Postgres. */
    public const ESCAPE = " ESCAPE '\\'";

    /** @param array<string, mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = Database::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $type = match (true) {
                is_int($value) => \PDO::PARAM_INT,
                is_bool($value) => \PDO::PARAM_INT,
                $value === null => \PDO::PARAM_NULL,
                default => \PDO::PARAM_STR,
            };
            $stmt->bindValue($name, is_bool($value) ? (int) $value : $value, $type);
        }
        $stmt->execute();

        return $stmt;
    }

    /** @param array<string, mixed> $params @return array<int, array<string, mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<string, mixed> $params @return array<string, mixed>|null */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /** @param array<string, mixed> $data */
    public static function insert(string $table, array $data): int
    {
        self::assertIdentifier($table);
        $columns = array_keys($data);
        foreach ($columns as $column) {
            self::assertIdentifier($column);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
            $table,
            implode(', ', $columns),
            implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns))
        );

        return (int) self::run($sql, $data)->fetchColumn();
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $where */
    public static function update(string $table, array $data, array $where): int
    {
        self::assertIdentifier($table);
        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            self::assertIdentifier($column);
            $sets[] = "{$column} = :set_{$column}";
            $params['set_' . $column] = $value;
        }
        $conditions = [];
        foreach ($where as $column => $value) {
            self::assertIdentifier($column);
            $conditions[] = "{$column} = :where_{$column}";
            $params['where_' . $column] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $sets), implode(' AND ', $conditions));

        return self::run($sql, $params)->rowCount();
    }

    /** @template T @param callable():T $callback @return T */
    public static function transaction(callable $callback): mixed
    {
        $pdo = Database::pdo();
        if ($pdo->inTransaction()) {
            return $callback();
        }

        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function paginate(string $select, string $from, array $params, int $page, int $perPage = 20, string $order = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $total = (int) self::value("SELECT COUNT(*) {$from}", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $rows = self::all("SELECT {$select} {$from} " . ($order !== '' ? "ORDER BY {$order} " : '') . "LIMIT {$perPage} OFFSET {$offset}", $params);

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    public static function like(string $term): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower(trim($term))) . '%';
    }

    private static function assertIdentifier(string $name): void
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException('Identificador inválido.');
        }
    }
}
