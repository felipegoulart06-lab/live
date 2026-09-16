<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    /** @var array<int, string> */
    protected array $fillable = [];

    protected bool $timestamps = true;

    protected function db(): PDO
    {
        return Database::pdo();
    }

    public function find(int|string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findBy(string $column, mixed $value): ?array
    {
        $this->assertColumn($column);
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['value' => $value]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $filtered = $this->filterFillable($data);
        if ($this->timestamps) {
            $filtered['created_at'] = $filtered['created_at'] ?? now();
            $filtered['updated_at'] = $filtered['updated_at'] ?? now();
        }

        $columns = implode(', ', array_keys($filtered));
        $placeholders = implode(', ', array_map(static fn (string $k) => ':' . $k, array_keys($filtered)));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($filtered);

        return (int) $this->db()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateById(int $id, array $data): bool
    {
        $filtered = $this->filterFillable($data);
        $filtered['updated_at'] = now();

        $sets = implode(', ', array_map(static fn (string $k) => "{$k} = :{$k}", array_keys($filtered)));
        $filtered['id'] = $id;

        $sql = "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db()->prepare($sql);

        return $stmt->execute($filtered);
    }

    /** @param array<string, mixed> $params */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /** @param array<string, mixed> $data */
    protected function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function assertColumn(string $column): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException('Coluna inválida.');
        }
    }
}
