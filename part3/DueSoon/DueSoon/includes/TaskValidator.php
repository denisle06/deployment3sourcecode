<?php
declare(strict_types=1);

/** Server-side validation for the "add task" form. Never trusts browser checks. */
final class TaskValidator
{
    /**
     * @param array<string, mixed> $input raw $_POST
     * @return array{0: array{title:string, unit:string, due:string, priority:string}, 1: array<string, string>}
     *         [clean values, errors keyed by field]
     */
    public static function validate(array $input, DateTimeImmutable $today): array
    {
        $title    = trim((string) ($input['title'] ?? ''));
        $unit     = strtoupper(trim((string) ($input['unit'] ?? '')));
        $due      = trim((string) ($input['due'] ?? ''));
        $priority = (string) ($input['priority'] ?? 'medium');
        $errors   = [];

        if ($title === '') {
            $errors['title'] = 'Enter what needs to be done.';
        } elseif (mb_strlen($title) > 100) {
            $errors['title'] = 'Keep the title under 100 characters.';
        }

        if ($unit !== '' && !preg_match('/^[A-Z]{3}\d{5}$/', $unit)) {
            $errors['unit'] = 'Use a unit code like SWE40006, or leave it blank.';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $due);
        if ($due === '' || $date === false || $date->format('Y-m-d') !== $due) {
            $errors['due'] = 'Pick a valid due date.';
        } elseif ($date < $today->setTime(0, 0)->modify('-1 year') || $date > $today->modify('+2 years')) {
            $errors['due'] = 'Choose a date within the next two years.';
        }

        if (!array_key_exists($priority, TaskStore::PRIORITIES)) {
            $errors['priority'] = 'Choose a priority.';
        }

        return [compact('title', 'unit', 'due', 'priority'), $errors];
    }
}
