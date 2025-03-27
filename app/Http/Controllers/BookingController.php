<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon; // For handling date and time operations

class BookingController extends Controller
{

    public function store(Request $request)
    {
        $booking = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'user_name' => 'required|string',
            'date' => [
                'required',
                'regex:/^\d{4}-\d{2}-\d{2}$/', // YYYY-MM-DD format
            ],
            'start_time' => [
                'required',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', // ISO 8601 with Z (UTC)
            ],
            'end_time' => [
                'required',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
                'after:start_time',
            ],
        ]);
    
        // Parse incoming UTC timestamps
        $startUtc = Carbon::parse($booking['start_time']);
        $endUtc = Carbon::parse($booking['end_time']);
    
        // Define allowed booking hours (09:00 - 18:00 UTC)
        $openingTime = Carbon::parse($booking['date'] . ' 09:00:00', 'UTC');
        $closingTime = Carbon::parse($booking['date'] . ' 18:00:00', 'UTC');
    
        if ($startUtc < $openingTime || $endUtc > $closingTime) {
            return response()->json(['error' => 'Bookings are only allowed between 09:00 AM - 06:00 PM UTC.'], 400);
        }
    
        // Check for overlapping bookings
        $alreadyBooked = Booking::where('room_id', $booking['room_id'])
            ->where('date', $booking['date'])
            ->where('start_time', '<', $booking['end_time'])
            ->where('end_time', '>', $booking['start_time'])
            ->exists();
    
        if ($alreadyBooked) {
            return response()->json(['error' => 'Room is already booked for the selected time.'], 409);
        }
    
        // Store booking in UTC (since frontend already sends UTC)
        $booking = Booking::create([
            'room_id' => $booking['room_id'],
            'user_name' => $booking['user_name'],
            'date' => $booking['date'],
            'start_time' => $startUtc->toDateTimeString(),
            'end_time' => $endUtc->toDateTimeString(),
        ]);
    
        return response()->json($booking, 201);
    }
    
    public function index()
    {
        $bookings = Booking::all();
        return response()->json($bookings);
    }

    public function availableRooms(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i'
        ]);

        $requestedDateTime = $validated['date'] . 'T' . $validated['time'] . ':00Z';
        $requestedDate = $validated['date'];

        // Get available rooms (excluding booked ones for requested time)
        $availableRooms = Room::whereNotExists(function ($query) use ($requestedDate, $requestedDateTime) {
            $query->from('bookings')
                ->whereColumn('bookings.room_id', 'rooms.id')
                ->where('date', $requestedDate)
                ->where('start_time', '<=', $requestedDateTime)
                ->where('end_time', '>', $requestedDateTime);
        })->select('id', 'name')->get();

        return response()->json($availableRooms->values());
    }
}
