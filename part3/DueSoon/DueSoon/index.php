<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$today  = new DateTimeImmutable('today');
$store  = new TaskStore($_SESSION);
$filter = in_array($_GET['filter'] ?? 'all', ['all', 'active', 'done'], true) ? ($_GET['filter'] ?? 'all') : 'all';
$self   = 'index.php' . ($filter !== 'all' ? '?filter=' . $filter : '');

$errors = [];
$old    = ['title' => '', 'unit' => '', 'due' => $today->modify('+7 days')->format('Y-m-d'), 'priority' => 'medium'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        http_response_code(400);
        flash('Your session expired. Please try again.', 'error');
        redirect($self);
    }

    $action = (string) ($_POST['action'] ?? '');
    $id     = (string) ($_POST['id'] ?? '');

    switch ($action) {
        case 'add':
            [$old, $errors] = TaskValidator::validate($_POST, $today);
            if (!$errors) {
                try {
                    $store->add($old);
                    flash('Added “' . $old['title'] . '”.');
                    redirect($self);
                } catch (OverflowException $ex) {
                    $errors['title'] = $ex->getMessage();
                }
            }
            http_response_code(422);
            break;

        case 'toggle':
            $store->toggle($id) || flash('That task no longer exists.', 'error');
            redirect($self);

        case 'delete':
            $store->delete($id) ? flash('Task deleted.') : flash('That task no longer exists.', 'error');
            redirect($self);

        case 'sample':
            $store->loadSample($today);
            flash('Loaded sample tasks.');
            redirect('index.php');

        case 'clear':
            $store->clear();
            flash('All tasks cleared.');
            redirect('index.php');

        default:
            http_response_code(400);
            flash('Unknown action.', 'error');
            redirect($self);
    }
}

$tasks   = $store->all($filter);
$stats   = $store->stats($today);
$message = flash();

render_header('Assignment tracker', 'index.php');
?>

<section class="intro">
    <p class="eyebrow">Tracker</p>
    <h1>What's due soon?</h1>
    <p class="lede">Add your assignments and DueSoon sorts them by due date and flags anything overdue. Tasks are kept in your browser session on the server, so only you can see them.</p>
</section>

<?php if ($message): ?>
    <div class="flash flash-<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
<?php endif; ?>

<div class="stats">
    <div class="stat"><span class="stat-label">Open</span><span class="stat-value"><?= $stats['open'] ?></span></div>
    <div class="stat <?= $stats['overdue'] ? 'stat-bad' : '' ?>"><span class="stat-label">Overdue</span><span class="stat-value"><?= $stats['overdue'] ?></span></div>
    <div class="stat"><span class="stat-label">Due in 7 days</span><span class="stat-value"><?= $stats['due_week'] ?></span></div>
    <div class="stat"><span class="stat-label">Done</span><span class="stat-value"><?= $stats['done'] ?></span></div>
</div>

<div class="layout">
    <section class="card">
        <div class="list-head">
            <h2>Your tasks</h2>
            <nav class="tabs" aria-label="Filter tasks">
                <?php foreach (['all' => 'All', 'active' => 'Open', 'done' => 'Done'] as $key => $label): ?>
                    <a href="index.php<?= $key === 'all' ? '' : '?filter=' . $key ?>" class="<?= $filter === $key ? 'active' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </nav>
        </div>

        <?php if (!$tasks): ?>
            <div class="empty">
                <p><?= $store->count() ? 'No tasks match this filter.' : 'No tasks yet. Add one, or load some examples.' ?></p>
                <?php if (!$store->count()): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost" name="action" value="sample">Load sample tasks</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <ul class="tasks">
                <?php foreach ($tasks as $task):
                    $days  = TaskStore::daysUntil($task['due'], $today);
                    $state = $task['done'] ? 'done' : ($days < 0 ? 'overdue' : ($days <= 2 ? 'soon' : 'later'));
                ?>
                    <li class="task task-<?= $state ?>">
                        <form method="post" class="task-check">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($task['id']) ?>">
                            <button class="check" name="action" value="toggle"
                                    aria-label="<?= $task['done'] ? 'Mark as not done' : 'Mark as done' ?>"
                                    title="<?= $task['done'] ? 'Mark as not done' : 'Mark as done' ?>"><?= $task['done'] ? '✓' : '' ?></button>
                        </form>
                        <div class="task-body">
                            <span class="task-title"><?= e($task['title']) ?></span>
                            <span class="task-meta">
                                <?php if ($task['unit'] !== ''): ?><span class="unit"><?= e($task['unit']) ?></span><?php endif; ?>
                                <span class="prio prio-<?= e($task['priority']) ?>"><?= e(TaskStore::PRIORITIES[$task['priority']]) ?></span>
                                <span class="due"><?= e(date('D j M', strtotime($task['due']))) ?> · <?= e($task['done'] ? 'Completed' : TaskStore::dueLabel($task['due'], $today)) ?></span>
                            </span>
                        </div>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($task['id']) ?>">
                            <button class="icon-btn" name="action" value="delete" aria-label="Delete task" title="Delete task">×</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form method="post" class="list-foot">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" name="action" value="sample">Reset to sample</button>
                <button class="btn btn-ghost" name="action" value="clear">Clear all</button>
            </form>
        <?php endif; ?>
    </section>

    <aside class="card">
        <h2>Add a task</h2>
        <form method="post" class="stack" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">

            <label>
                <span>Task</span>
                <input name="title" maxlength="100" value="<?= e($old['title']) ?>" placeholder="e.g. Task 3 report"
                       class="<?= isset($errors['title']) ? 'invalid' : '' ?>" autofocus>
                <?php if (isset($errors['title'])): ?><span class="field-error"><?= e($errors['title']) ?></span><?php endif; ?>
            </label>

            <label>
                <span>Unit code <em class="muted">(optional)</em></span>
                <input name="unit" maxlength="8" value="<?= e($old['unit']) ?>" placeholder="SWE40006"
                       class="<?= isset($errors['unit']) ? 'invalid' : '' ?>">
                <?php if (isset($errors['unit'])): ?><span class="field-error"><?= e($errors['unit']) ?></span><?php endif; ?>
            </label>

            <div class="row-2">
                <label>
                    <span>Due date</span>
                    <input type="date" name="due" value="<?= e($old['due']) ?>"
                           class="<?= isset($errors['due']) ? 'invalid' : '' ?>">
                    <?php if (isset($errors['due'])): ?><span class="field-error"><?= e($errors['due']) ?></span><?php endif; ?>
                </label>
                <label>
                    <span>Priority</span>
                    <select name="priority" class="<?= isset($errors['priority']) ? 'invalid' : '' ?>">
                        <?php foreach (TaskStore::PRIORITIES as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $old['priority'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['priority'])): ?><span class="field-error"><?= e($errors['priority']) ?></span><?php endif; ?>
                </label>
            </div>

            <button class="btn btn-primary full">Add task</button>
        </form>
    </aside>
</div>

<?php render_footer(); ?>
