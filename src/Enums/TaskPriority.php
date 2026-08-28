<?php

namespace Andremellow\Tasks\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __(str($this->value)->headline()->toString());
    }

    public function order(): int
    {
        return match ($this) {
            self::Low => 1, self::Medium => 2, self::High => 3, self::Urgent => 4
        };
    }
}
