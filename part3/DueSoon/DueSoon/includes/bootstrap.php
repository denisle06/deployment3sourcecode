<?php
declare(strict_types=1);

/*
 * Shared setup for every page: configuration, secure session, CSRF and helpers.
 * Files in includes/ are only meant to be required by the pages, never opened directly.
 */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit;
}

define('APP_ROOT', dirname(__DIR__));

require_once __DIR__ . '/TaskStore.php';
require_once __DIR__ . '/TaskValidator.php';

/** Read a setting from the environment (Azure App Settings), with a fallback. */
function env(string $name, string $default = ''): string
{
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

// Student details shown in the footer. Override on Azure with the App Settings
// STUDENT_NAME / STUDENT_ID instead of editing code.
const STUDENT_UNIT = 'SWE40006 Software Deployment and Evolution';
define('STUDENT_NAME', env('STUDENT_NAME', 'Anh Duc Le'));
define('STUDENT_ID', env('STUDENT_ID', 'YOUR_STUDENT_ID'));

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'));

/** True when the visitor reached us over HTTPS (App Service terminates TLS in front of the app). */
function is_https(): bool
{
    return (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

if (PHP_SAPI !== 'cli' && !defined('SKIP_SESSION') && session_status() === PHP_SESSION_NONE) {
    session_name('duesoon');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}

/** Escape a value for HTML output. Every piece of user data goes through this. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/** One-off message shown after a redirect. */
function flash(?string $message = null, string $type = 'ok'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/** Post/Redirect/Get: avoids duplicate submissions when the page is refreshed. */
function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

/** Facts about where the app is running, read from variables App Service sets. */
function deployment_info(): array
{
    $site = env('WEBSITE_SITE_NAME');
    $instance = env('WEBSITE_INSTANCE_ID', '—');

    return [
        'running_on_azure' => $site !== '',
        'site_name'        => $site !== '' ? $site : 'Local machine',
        'host_name'        => env('WEBSITE_HOSTNAME', $_SERVER['HTTP_HOST'] ?? 'localhost'),
        'region'           => env('REGION_NAME', '—'),
        'sku'              => env('WEBSITE_SKU', '—'),
        'instance_id'      => strlen($instance) > 12 ? substr($instance, 0, 12) . '…' : $instance,
        'php_version'      => PHP_VERSION,
        'server_api'       => PHP_SAPI,
        'server_software'  => $_SERVER['SERVER_SOFTWARE'] ?? '—',
        'operating_system' => PHP_OS_FAMILY . ' ' . php_uname('r'),
        'timezone'         => date_default_timezone_get(),
        'server_time'      => date('Y-m-d H:i:s T'),
    ];
}

function render_header(string $title, string $active): void
{
    $nav = ['index.php' => 'Tracker', 'status.php' => 'Status'];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · DueSoon</title>
    <meta name="description" content="Track assignment due dates. A PHP web app deployed to Azure App Service.">
    <link rel="stylesheet" href="assets/style.css?v=1">
</head>
<body>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true">D</span><span class="brand-name">DueSoon</span></a>
        <nav class="nav" aria-label="Main">
            <?php foreach ($nav as $href => $label): ?>
                <a href="<?= e($href) ?>" class="<?= $href === $active ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<main class="wrap main">
    <?php
}

function render_footer(): void
{
    $info = deployment_info();
    ?>
</main>
<footer class="site-footer">
    <div class="wrap footer-inner">
        <span>Built by <strong><?= e(STUDENT_NAME) ?></strong> · <?= e(STUDENT_ID) ?></span>
        <span><?= e(STUDENT_UNIT) ?></span>
        <span class="env-pill <?= $info['running_on_azure'] ? 'is-azure' : 'is-local' ?>">
            <?= $info['running_on_azure'] ? 'Azure App Service · ' . e($info['site_name']) : 'Running locally' ?>
        </span>
    </div>
</footer>
</body>
</html>
    <?php
}
