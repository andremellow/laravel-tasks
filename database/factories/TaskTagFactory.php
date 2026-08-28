<?php

namespace Andremellow\Tasks\Database\Factories;

use Andremellow\Tasks\Models\TaskTag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTagFactory extends Factory
{
    protected $model = TaskTag::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => str($name)->title(), 'slug' => str($name)->slug()];
    }
}
