<?php

namespace Tests\Feature\Booking;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Room;
use App\Models\Booking;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a booking is created successfully with valid data.
     */
    public function test_it_creates_a_booking_with_valid_data()
    {
        $response = $this->postJson('/api/bookings', [
            'room_id' => 1,
            'user_name' => 'Diego',
            'date' => '2025-04-01',
            'start_time' => '2025-04-01T12:00:00Z',
            'end_time' => '2025-04-01T14:00:00Z',
        ]);

        // The response should have a 201 status (Created) and match the expected structure.
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id', 'room_id', 'user_name', 'date', 'start_time', 'end_time'
                 ]);
    }

    /**
     * Test that the API returns an error when the room_id is missing.
     */
    public function test_it_fails_if_room_id_is_missing()
    {
        $response = $this->postJson('/api/bookings', [
            'user_name' => 'John Doe',
            'date' => '2025-04-01',
            'start_time' => '2025-04-01T12:00:00Z',
            'end_time' => '2025-04-01T14:00:00Z',
        ]);

        // Expecting a 400 validation error with a missing 'room_id' field.
        $response->assertStatus(400)
                 ->assertJsonValidationErrors('room_id');
    }

/**
     * Test that the API returns an error when the date is in an invalid format.
     */
    /** @test */
    public function it_should_return_an_error_when_the_date_is_in_invalid_format()
    {
        // Invalid date format (DD-MM-YYYY instead of YYYY-MM-DD)
        $response = $this->postJson('/api/bookings', [
            'room_id' => 1,
            'user_name' => 'John Doe',
            'date' => '26-03-2025', // Invalid date format
            'start_time' => '2025-03-26T14:00:00Z',
            'end_time' => '2025-03-26T15:00:00Z',
        ]);

        $response->assertStatus(400); // Unprocessable Entity (validation error)
        $response->assertJsonValidationErrors(['date']);
    }

    /**
     * Test that the API returns an error when the start_time is in an invalid format.
     */
    /** @test */
    public function it_should_return_an_error_when_the_start_time_is_in_invalid_format()
    {
        // Invalid start_time format (missing date and UTC "Z")
        $response = $this->postJson('/api/bookings', [
            'room_id' => 1,
            'user_name' => 'John Doe',
            'date' => '2025-03-26',
            'start_time' => '14:00:00', // Missing date and UTC "Z"
            'end_time' => '2025-03-26T15:00:00Z',
        ]);

        $response->assertStatus(400); // Unprocessable Entity (validation error)
        $response->assertJsonValidationErrors(['start_time']);
    }

    /**
     * Test that the API returns an error when the end_time has an invalid offset.
     */
    /** @test */
    public function it_should_return_an_error_when_the_end_time_has_invalid_offset()
    {
        // Invalid end_time format (wrong offset, should be 'Z' for UTC)
        $response = $this->postJson('/api/bookings', [
            'room_id' => 1,
            'user_name' => 'John Doe',
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T14:00:00Z',
            'end_time' => '2025-03-26T15:00:00+02:00', // Wrong offset, should be 'Z'
        ]);

        $response->assertStatus(400); // Unprocessable Entity (validation error)
        $response->assertJsonValidationErrors(['end_time']);
    }

    /**
     * Test that the API returns an error when the start time is after the end time.
     */
    public function test_it_fails_if_start_time_is_after_end_time()
    {
        $response = $this->postJson('/api/bookings', [
            'room_id' => 1,
            'user_name' => 'John Doe',
            'date' => '2025-04-01',
            'start_time' => '2025-04-01T15:00:00Z',
            'end_time' => '2025-04-01T14:00:00Z',
        ]);

        // Expecting a validation error due to invalid time range.
        $response->assertStatus(400)
                 ->assertJsonValidationErrors('end_time');
    }

    /** 
     * Test that a user cannot book a room that is already taken for the selected time.
     */
    public function test_users_cannot_book_a_room_that_is_already_taken()
    {
        // Create a room
        $room = Room::factory()->create();

        // Create an existing booking for the room
        $existingBooking = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'user_name' => 'Diego1',
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T14:00:00Z',
            'end_time' => '2025-03-26T15:00:00Z', 
        ]);

        $existingBooking->assertStatus(201);

        // Should fail - overlaps same time
        $response = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'user_name' => 'Diego2',
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T14:00:00Z',
            'end_time' => '2025-03-26T15:00:00Z', 
        ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'error' => 'Room is already booked for the selected time.'
                 ]);

        // Should fail - overlaps by 1 second at end
        $response = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'user_name' => 'John Doe',
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T13:00:00Z',
            'end_time' => '2025-03-26T14:00:01Z',
        ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'error' => 'Room is already booked for the selected time.'
                 ]);

        // Should fail - overlaps by 1 second at start
        $response = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'user_name' => 'John Doe', 
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T14:59:59Z',
            'end_time' => '2025-03-26T16:00:00Z',
        ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'error' => 'Room is already booked for the selected time.'
                 ]);

        // Should fail - completely within existing booking
        $response = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'user_name' => 'John Doe',
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T14:00:01Z',
            'end_time' => '2025-03-26T14:59:59Z',
        ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'error' => 'Room is already booked for the selected time.'
                 ]);

        // Should succeed - ends exactly when existing booking starts
        $response = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'user_name' => 'John Doe',
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T13:00:00Z',
            'end_time' => '2025-03-26T14:00:00Z',
        ]);

        $response->assertStatus(201);

        // Should succeed - starts exactly when existing booking ends
        $response = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'user_name' => 'John Doe',
            'date' => '2025-03-26',
            'start_time' => '2025-03-26T15:00:00Z',
            'end_time' => '2025-03-26T16:00:00Z',
        ]);

        $response->assertStatus(201);
    }

}
