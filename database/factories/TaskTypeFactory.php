<?php

namespace Andremellow\Tasks\Database\Factories;

use Andremellow\Tasks\Models\TaskType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTypeFactory extends Factory
{
    protected $model = TaskType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => str($name)->title(), 'slug' => str($name)->slug(), 'sort_order' => 0];
    }
}
