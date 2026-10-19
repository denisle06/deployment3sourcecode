<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$info = deployment_info();
$rows = [
    'Site name'        => $info['site_name'],
    'Host name'        => $info['host_name'],
    'Region'           => $info['region'],
    'Pricing tier'     => $info['sku'],
    'Instance'         => $info['instance_id'],
    'PHP version'      => $info['php_version'],
    'Server API'       => $info['server_api'],
    'Web server'       => $info['server_software'],
    'Operating system' => $info['operating_system'],
    'Time zone'        => $info['timezone'],
    'Server time'      => $info['server_time'],
];

render_header('Deployment status', 'status.php');
?>

<section class="intro">
    <p class="eyebrow">Status</p>
    <h1>Where is this app running?</h1>
    <p class="lede">These values come from the environment variables Azure App Service sets on the app, so this page shows the deployment is live.</p>
</section>

<div class="card status-card">
    <div class="status-head">
        <span class="dot <?= $info['running_on_azure'] ? 'dot-ok' : 'dot-local' ?>" aria-hidden="true"></span>
        <strong><?= $info['running_on_azure'] ? 'Running on Azure App Service' : 'Running locally (not on Azure)' ?></strong>
    </div>
    <dl class="kv">
        <?php foreach ($rows as $label => $value): ?>
            <dt><?= e($label) ?></dt><dd><?= e($value) ?></dd>
        <?php endforeach; ?>
    </dl>
    <p class="muted small">Machine-readable: <a href="health.php">health.php</a></p>
</div>

<?php render_footer(); ?>
