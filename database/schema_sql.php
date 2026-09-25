<?php

declare(strict_types=1);

/*
 * Prints the schema for a driver: `php database/schema_sql.php pgsql`.
 * The Postgres output also locks every table behind RLS, because Supabase exposes the
 * public schema through its REST API; the PHP app connects as the database owner.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Core/Schema.php';

$driver = $argv[1] ?? 'pgsql';
$statements = \App\Core\Schema::statements($driver);

if ($driver === 'pgsql') {
    array_unshift($statements, 'CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA extensions');
    $statements[] = 'CREATE INDEX IF NOT EXISTS idx_listings_title_trgm ON listings USING gin (lower(title) extensions.gin_trgm_ops)';
    foreach ($statements as $sql) {
        if (preg_match('/^CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $m) === 1) {
            $statements[] = "ALTER TABLE {$m[1]} ENABLE ROW LEVEL SECURITY";
        }
    }
    $statements[] = 'REVOKE ALL ON ALL TABLES IN SCHEMA public FROM anon, authenticated';
    $statements[] = 'REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM anon, authenticated';
    $statements[] = "INSERT INTO schema_migrations (version, applied_at) VALUES ('" . \App\Core\Schema::VERSION . "', now()) ON CONFLICT DO NOTHING";
}

echo implode(";\n\n", $statements) . ";\n";
