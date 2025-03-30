<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon; // For handling date and time operations
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{

    public function store(Request $request)
    {
        Log::info('Request body:', ['request' => $request->all()]);
        // Validate input data
        $validator = Validator::make($request->all(), [
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
    
        // If validation fails, return a 400 response with errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        // Retrieve validated data
        $booking = $validator->validated();
    
        // Parse incoming UTC timestamps
        $startUtc = Carbon::parse($booking['start_time']);
        $endUtc = Carbon::parse($booking['end_time']);
    
        Log::info('2...');
    
        // Define allowed booking hours (09:00 - 18:00 UTC)
        $openingTime = Carbon::parse($booking['date'] . ' 09:00:00', 'UTC');
        $closingTime = Carbon::parse($booking['date'] . ' 18:00:00', 'UTC');
    
        Log::info('3...');
    
        // Check if the booking time is within allowed hours
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
    
        // Store the booking in UTC (since frontend already sends UTC)
        $newBooking = Booking::create([
            'room_id' => $booking['room_id'],
            'user_name' => $booking['user_name'],
            'date' => $booking['date'],
            'start_time' => $startUtc->toDateTimeString(),
            'end_time' => $endUtc->toDateTimeString(),
        ]);
    
        return response()->json($newBooking, 201);
    }
    
    public function index()
    {
        $bookings = Booking::all();
        return response()->json($bookings);
    }

    public function availableRooms(Request $request)
    {
        // Validate input data
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);
    
        // If validation fails, return a 400 response with errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        // Retrieve validated data
        $filters = $validator->validated();
    
        // Construct requested datetime in ISO 8601 format (UTC)
        $requestedDateTime = $filters['date'] . 'T' . $filters['time'] . ':00Z';
        $requestedDate = $filters['date'];
    
        Log::info('Available rooms request received', ['date' => $filters['date'], 'time' => $filters['time']]);
    
        // Get available rooms (excluding those already booked for the requested time)
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
