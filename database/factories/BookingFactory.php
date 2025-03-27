<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition()
    {
        return [
            'room_id' => $this->faker->randomDigitNotNull,
            'user_name' => $this->faker->name,
            // Ensuring the date is in ISO format (YYYY-MM-DD)
            'date' => $this->faker->date('Y-m-d'),
            // Ensuring start_time and end_time are in ISO format with 'Z' (UTC)
            'start_time' => $this->faker->dateTimeThisYear()->format('Y-m-d\TH:i:s\Z'),
            'end_time' => $this->faker->dateTimeThisYear()->modify('+1 hour')->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}