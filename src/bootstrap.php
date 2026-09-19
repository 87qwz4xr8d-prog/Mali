<?php

declare(strict_types=1);

session_start();

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/BudgetService.php';
require_once __DIR__ . '/ReportService.php';
require_once __DIR__ . '/ReportExporter.php';

date_default_timezone_set('Asia/Bangkok');
