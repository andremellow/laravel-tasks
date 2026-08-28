<?php

namespace Andremellow\Tasks\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case ToDo = 'to_do';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';

    public function label(): string
    {
        return __(match ($this) {
            self::Backlog => 'Backlog', self::ToDo => 'To Do', self::InProgress => 'In Progress',
            self::InReview => 'In Review', self::Done => 'Done',
        });
    }

    public function onBoard(): bool
    {
        return $this !== self::Backlog;
    }
}
