<?php

namespace Andremellow\Tasks\Database\Factories;

use Andremellow\Tasks\Enums\TaskPriority;
use Andremellow\Tasks\Enums\TaskStatus;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Models\TaskType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $user = config('tasks.user_model');

        return ['task_type_id' => TaskType::factory(), 'created_by' => $user::factory(), 'title' => fake()->sentence(5), 'description' => fake()->optional()->paragraph(), 'priority' => TaskPriority::Medium, 'status' => TaskStatus::Backlog, 'board_position' => 0];
    }

    public function toDo(): static
    {
        return $this->state(['status' => TaskStatus::ToDo]);
    }

    public function done(): static
    {
        return $this->state(['status' => TaskStatus::Done, 'completed_at' => now()]);
    }
}
