<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Auth.php';

Auth::startSession();
