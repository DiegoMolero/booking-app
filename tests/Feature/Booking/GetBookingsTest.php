<?php

namespace Tests\Feature\Booking;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Booking;

class GetBookingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the API returns a list of bookings.
     */
    public function test_it_returns_a_list_of_bookings()
    {
        // Create 3 fake bookings with valid room_id
        Booking::factory()->create([
            'room_id' => 1,
            'start_time' => '2025-04-01T12:00:00',
            'end_time' => '2025-04-01T14:00:00',
            'date' => '2025-04-01'
        ]);
        Booking::factory()->create([
            'room_id' => 2,
            'start_time' => '2025-04-01T12:00:00',
            'end_time' => '2025-04-01T14:00:00',
            'date' => '2025-04-01'
        ]);
        Booking::factory()->create([
            'room_id' => 3,
            'start_time' => '2025-04-01T12:00:00',
            'end_time' => '2025-04-01T14:00:00',
            'date' => '2025-04-01'
        ]);

        $response = $this->getJson('/api/bookings');

        // Expecting a successful response with 3 records in the data.
        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /**
     * Test that an empty list is returned if there are no bookings.
     */
    public function test_it_returns_empty_list_when_no_bookings_exist()
    {
        $response = $this->getJson('/api/bookings');

        // Expecting an empty array in the response.
        $response->assertStatus(200)
                 ->assertExactJson([]);
    }
}
