<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'section_id' => Section::factory(),
        ];
    }
}
