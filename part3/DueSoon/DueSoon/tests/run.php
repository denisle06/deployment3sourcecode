<?php
declare(strict_types=1);

/*
 * Minimal test runner (no Composer or PHPUnit needed).
 * Run from the project folder:  php tests/run.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../includes/bootstrap.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ✓ $name\n";
    } else {
        $failed++;
        echo "  ✗ $name\n";
    }
}

$today = new DateTimeImmutable('2026-10-19');

echo "TaskValidator\n";
[$clean, $errors] = TaskValidator::validate(['title' => '  Report ', 'unit' => 'swe40006', 'due' => '2026-10-25', 'priority' => 'high'], $today);
check('accepts valid input', $errors === []);
check('trims the title', $clean['title'] === 'Report');
check('upper-cases the unit code', $clean['unit'] === 'SWE40006');

[, $errors] = TaskValidator::validate(['title' => '', 'due' => '2026-10-25'], $today);
check('requires a title', isset($errors['title']));

[, $errors] = TaskValidator::validate(['title' => 'x', 'unit' => 'SWE4', 'due' => '2026-10-25'], $today);
check('rejects a malformed unit code', isset($errors['unit']));

[, $errors] = TaskValidator::validate(['title' => 'x', 'due' => '2026-02-30'], $today);
check('rejects an impossible date', isset($errors['due']));

[, $errors] = TaskValidator::validate(['title' => 'x', 'due' => '2030-01-01'], $today);
check('rejects a date too far ahead', isset($errors['due']));

[, $errors] = TaskValidator::validate(['title' => 'x', 'due' => '2026-10-25', 'priority' => 'urgent'], $today);
check('rejects an unknown priority', isset($errors['priority']));

[, $errors] = TaskValidator::validate(['title' => str_repeat('a', 101), 'due' => '2026-10-25'], $today);
check('rejects a title over 100 characters', isset($errors['title']));

echo "TaskStore\n";
$storage = [];
$store = new TaskStore($storage);
$a = $store->add(['title' => 'Later',   'unit' => '', 'due' => '2026-10-30', 'priority' => 'low']);
$b = $store->add(['title' => 'Overdue', 'unit' => '', 'due' => '2026-10-17', 'priority' => 'high']);
$c = $store->add(['title' => 'Soon',    'unit' => '', 'due' => '2026-10-21', 'priority' => 'medium']);
check('adds tasks to the backing storage', count($storage['tasks']) === 3);
check('sorts by due date', array_column($store->all(), 'title') === ['Overdue', 'Soon', 'Later']);

$store->toggle($b);
check('moves completed tasks to the end', array_column($store->all(), 'title') === ['Soon', 'Later', 'Overdue']);
check('filters open tasks', array_column($store->all('active'), 'title') === ['Soon', 'Later']);
check('filters done tasks', array_column($store->all('done'), 'title') === ['Overdue']);

$stats = $store->stats($today);
check('counts stats', $stats === ['total' => 3, 'open' => 2, 'done' => 1, 'overdue' => 0, 'due_week' => 1]);

check('deletes a task', $store->delete($a) && $store->count() === 2);
check('ignores unknown ids', !$store->delete('nope') && !$store->toggle('nope'));

$store->loadSample($today);
check('loads five sample tasks', $store->count() === 5);
$store->clear();
check('clears all tasks', $store->count() === 0);

echo "Due labels\n";
check('due today',     TaskStore::dueLabel('2026-10-19', $today) === 'Due today');
check('due tomorrow',  TaskStore::dueLabel('2026-10-20', $today) === 'Due tomorrow');
check('due in 5 days', TaskStore::dueLabel('2026-10-24', $today) === 'Due in 5 days');
check('1 day overdue', TaskStore::dueLabel('2026-10-18', $today) === '1 day overdue');
check('3 days overdue', TaskStore::dueLabel('2026-10-16', $today) === '3 days overdue');

echo "Escaping\n";
check('escapes HTML', e('<script>"x"</script>') === '&lt;script&gt;&quot;x&quot;&lt;/script&gt;');

echo "\n$passed passed, $failed failed\n";
exit($failed === 0 ? 0 : 1);
