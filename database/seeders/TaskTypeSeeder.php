<?php

namespace Andremellow\Tasks\Database\Seeders;

use Andremellow\Tasks\Models\TaskType;
use Illuminate\Database\Seeder;

class TaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['to-do' => 'To-Do', 'bug' => 'Bug', 'new-feature' => 'New Feature', 'improvement' => 'Improvement'] as $slug => $name) {
            TaskType::withTrashed()->updateOrCreate(['slug' => $slug], ['name' => $name, 'deleted_at' => null]);
        }
    }
}
