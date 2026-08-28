<?php

namespace Andremellow\Tasks\Contracts;

use Andremellow\Tasks\Models\Task;

interface TaskConnector
{
    public function key(): string;

    public function push(Task $task): string;

    public function delete(Task $task, string $remoteId): void;
}
