<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use App\Kernel;

$kernel = new Kernel($config);
$kernel->handle();
