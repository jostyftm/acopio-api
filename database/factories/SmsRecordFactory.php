<?php

namespace Database\Factories;

use App\Models\SmsRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsRecord>
 */
class SmsRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'origin_phone' => '57'.fake()->numerify('3#########'),
            'body' => fake()->sentence(),
            'direction' => 'incoming',
            'status' => 'pending',
        ];
    }
}
