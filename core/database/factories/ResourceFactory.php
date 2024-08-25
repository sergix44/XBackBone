<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'code' => $this->faker->word(),
            'filename' => $this->faker->word(),
            'storage_path' => $this->faker->word(),
            'is_published' => $this->faker->boolean(),

            'user_id' => User::factory(),
        ];
    }
}
