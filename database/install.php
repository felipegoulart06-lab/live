<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\SqliteSchema;

require __DIR__ . '/bootstrap.php';

SqliteSchema::install(Database::pdo());
echo "Estrutura PHP pronta (arquivo storage/nexo.sqlite).\n";

require __DIR__ . '/seed.php';
require __DIR__ . '/seed_media.php';
require __DIR__ . '/seed_service_detail.php';
