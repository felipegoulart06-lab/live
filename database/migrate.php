<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\SqliteSchema;

require __DIR__ . '/bootstrap.php';

$pdo = Database::pdo();
SqliteSchema::install($pdo);

echo "Estrutura PHP/SQLite pronta em storage/nexo.sqlite\n";
