<?php
declare(strict_types=1);

// Lightweight health check for monitoring: no session, JSON only.
define('SKIP_SESSION', true);
require __DIR__ . '/includes/bootstrap.php';

$info = deployment_info();

header('Content-Type: application/json');
header('Cache-Control: no-store');

echo json_encode([
    'status'         => 'Healthy',
    'runningOnAzure' => $info['running_on_azure'],
    'siteName'       => $info['site_name'],
    'region'         => $info['region'],
    'php'            => $info['php_version'],
    'serverTime'     => date(DATE_ATOM),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
