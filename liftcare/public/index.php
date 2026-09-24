<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$page = (string) get('page', 'dashboard');
$allowed = [
    'dashboard',
    'vehicles', 'vehicle_form', 'vehicle_delete',
    'work_orders', 'work_order_form', 'work_order_delete',
    'pm_plans', 'pm_plan_form', 'pm_plan_delete',
    'parts', 'part_form', 'part_delete',
    'customers', 'customer_form', 'customer_delete',
    'users', 'user_form', 'user_delete',
    'reports',
    'settings',
];

if (!in_array($page, $allowed, true)) {
    $page = 'dashboard';
}

$moduleFile = dirname(__DIR__) . '/modules/' . $page . '.php';
if (!is_file($moduleFile)) {
    http_response_code(404);
    echo 'ไม่พบหน้า';
    exit;
}

require $moduleFile;
