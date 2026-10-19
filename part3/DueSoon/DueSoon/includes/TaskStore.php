<?php
declare(strict_types=1);

/**
 * Keeps each visitor's tasks in their own PHP session, so the app needs no
 * database and visitors never see each other's tasks.
 * The storage array is passed by reference, which also makes the class easy to unit test.
 */
final class TaskStore
{
    public const PRIORITIES = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
    private const MAX_TASKS = 50;

    /** @param array<string, mixed> $storage usually $_SESSION */
    public function __construct(private array &$storage)
    {
        $this->storage['tasks'] ??= [];
    }

    /** @return list<array<string, mixed>> tasks sorted: open first, then by due date, then priority */
    public function all(string $filter = 'all'): array
    {
        $tasks = array_values($this->storage['tasks']);

        $tasks = match ($filter) {
            'active' => array_values(array_filter($tasks, fn ($t) => !$t['done'])),
            'done'   => array_values(array_filter($tasks, fn ($t) => $t['done'])),
            default  => $tasks,
        };

        $rank = array_flip(array_keys(self::PRIORITIES));
        usort($tasks, fn ($a, $b) =>
            [$a['done'], $a['due'], $rank[$a['priority']]] <=> [$b['done'], $b['due'], $rank[$b['priority']]]);

        return $tasks;
    }

    public function count(): int
    {
        return count($this->storage['tasks']);
    }

    /** @return array{total:int, open:int, done:int, overdue:int, due_week:int} */
    public function stats(DateTimeImmutable $today): array
    {
        $stats = ['total' => 0, 'open' => 0, 'done' => 0, 'overdue' => 0, 'due_week' => 0];
        foreach ($this->storage['tasks'] as $task) {
            $stats['total']++;
            if ($task['done']) {
                $stats['done']++;
                continue;
            }
            $stats['open']++;
            $days = self::daysUntil($task['due'], $today);
            if ($days < 0) {
                $stats['overdue']++;
            } elseif ($days <= 7) {
                $stats['due_week']++;
            }
        }
        return $stats;
    }

    /** @param array{title:string, unit:string, due:string, priority:string} $data already validated */
    public function add(array $data): string
    {
        if ($this->count() >= self::MAX_TASKS) {
            throw new OverflowException('You can keep up to ' . self::MAX_TASKS . ' tasks.');
        }
        $id = bin2hex(random_bytes(6));
        $this->storage['tasks'][$id] = [
            'id'       => $id,
            'title'    => $data['title'],
            'unit'     => $data['unit'],
            'due'      => $data['due'],
            'priority' => $data['priority'],
            'done'     => false,
        ];
        return $id;
    }

    public function toggle(string $id): bool
    {
        if (!isset($this->storage['tasks'][$id])) {
            return false;
        }
        $this->storage['tasks'][$id]['done'] = !$this->storage['tasks'][$id]['done'];
        return true;
    }

    public function delete(string $id): bool
    {
        if (!isset($this->storage['tasks'][$id])) {
            return false;
        }
        unset($this->storage['tasks'][$id]);
        return true;
    }

    public function clear(): void
    {
        $this->storage['tasks'] = [];
    }

    /** Fills the list with example tasks relative to today, for demos and screenshots. */
    public function loadSample(DateTimeImmutable $today): void
    {
        $this->clear();
        $samples = [
            ['Task 3 deployment report',        'SWE40006', '+3 days',  'high'],
            ['Cloud architecture lab 6',        'COS20019', '+1 day',   'medium'],
            ['Read chapter 7 before tutorial',  'COS20007', '+9 days',  'low'],
            ['Database assignment part B',      'COS20031', '-2 days',  'high'],
            ['Group project meeting notes',     '',         '+14 days', 'medium'],
        ];
        foreach ($samples as [$title, $unit, $offset, $priority]) {
            $this->add(['title' => $title, 'unit' => $unit,
                        'due' => $today->modify($offset)->format('Y-m-d'), 'priority' => $priority]);
        }
        // Show one completed task so the "done" state is visible too.
        $this->toggle((string) array_key_last($this->storage['tasks']));
    }

    public static function daysUntil(string $due, DateTimeImmutable $today): int
    {
        $dueDate = DateTimeImmutable::createFromFormat('!Y-m-d', $due, $today->getTimezone());
        $start = $today->setTime(0, 0);
        return (int) $start->diff($dueDate)->format('%r%a');
    }

    /** Human label for how close a due date is. */
    public static function dueLabel(string $due, DateTimeImmutable $today): string
    {
        $days = self::daysUntil($due, $today);
        return match (true) {
            $days < -1  => abs($days) . ' days overdue',
            $days === -1 => '1 day overdue',
            $days === 0 => 'Due today',
            $days === 1 => 'Due tomorrow',
            default     => "Due in $days days",
        };
    }
}
