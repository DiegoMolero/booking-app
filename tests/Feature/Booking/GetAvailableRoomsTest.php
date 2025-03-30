<?php

namespace Tests\Feature\Booking;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Booking;
use App\Models\Room;

class GetAvailableRoomsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that available rooms are returned correctly for a given date and time.
     */
    public function test_it_returns_available_rooms()
    {
        $existingBooking = $this->postJson('/api/bookings', [
            'room_id' => 1,
            'user_name' => 'Diego1',
            'date' => '2025-04-01',
            'start_time' => '2025-04-01T12:00:00Z',
            'end_time' => '2025-04-01T14:00:00Z',
        ]);

        $existingBooking->assertStatus(201);

        $response = $this->getJson('/api/available-rooms?date=2025-04-01&time=12:30');

        // Expecting 2 available rooms since one is already booked.
        $response->assertStatus(200);
        $content = json_decode($response->getContent());
        $this->assertCount(2, $content);
    }

    /**
     * Test that all rooms are available when no bookings exist.
     */
    public function test_it_returns_all_rooms_when_no_bookings_exist()
    {
        $response = $this->getJson('/api/available-rooms?date=2025-04-01&time=12:30');

        // Expecting all 3 rooms to be available.
        $response->assertStatus(200)
                 ->assertJsonCount(3);

        $response = $this->getJson('/api/available-rooms');
    }

    /**
     * Test that an empty list is returned when all rooms are booked.
     */
    public function test_it_returns_no_available_rooms_when_all_are_booked()
    {
        $response = $this->getJson('/api/available-rooms?date=2025-04-01&time=12:30');

        // Expecting all 3 rooms to be available.
        $response->assertStatus(200)
                 ->assertJsonCount(3);

        // Book both rooms at the same time.
        $response = $this->postJson('/api/bookings', [
            'room_id' => 1,
            'user_name' => 'Diego1',
            'date' => '2025-04-01',
            'start_time' => '2025-04-01T12:00:00Z',
            'end_time' => '2025-04-01T14:00:00Z',
        ]);
        $response->assertStatus(201);

        $response = $this->postJson('/api/bookings', [
            'room_id' => 2,
            'user_name' => 'Diego2',
            'date' => '2025-04-01',
            'start_time' => '2025-04-01T12:00:00Z',
            'end_time' => '2025-04-01T14:00:00Z',
        ]);
        $response->assertStatus(201);

        $response = $this->postJson('/api/bookings', [
            'room_id' => 3,
            'user_name' => 'Diego3',
            'date' => '2025-04-01',
            'start_time' => '2025-04-01T12:00:00Z',
            'end_time' => '2025-04-01T14:00:00Z',
        ]);
        $response->assertStatus(201);

        $response = $this->getJson('/api/available-rooms?date=2025-04-01&time=12:30');

        // Expecting an empty array because all rooms are booked.
        $response->assertStatus(200)
                 ->assertExactJson([]);

        $response = $this->getJson('/api/available-rooms?date=2025-04-01&time=12:00');

        // Expecting an empty array because all rooms are booked.
        $response->assertStatus(200)
                ->assertExactJson([]);
    }

    /**
     * Test that the API returns an error when date and time parameters are missing.
     */
    public function test_it_returns_error_when_date_and_time_parameters_are_missing()
    {
        $response = $this->getJson('/api/available-rooms');

        $response->assertStatus(400)
                 ->assertJson([
                     'errors' => [
                         'date' => ['The date field is required.'],
                         'time' => ['The time field is required.']
                     ]
                 ]);
    }
}
