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
    
        // Define allowed booking hours (09:00 - 18:00 UTC)
        $openingTime = Carbon::parse($booking['date'] . ' 09:00:00', 'UTC');
        $closingTime = Carbon::parse($booking['date'] . ' 18:00:00', 'UTC');
    
        // Check if the booking time is within allowed hours
        if ($startUtc < $openingTime || $endUtc > $closingTime) {
            return response()->json(['error' => 'Bookings are only allowed between 09:00 AM - 06:00 PM UTC.'], 400);
        }

        // Check for overlapping bookings
        $alreadyBooked = Booking::where('room_id', $booking['room_id'])
            ->where('date', $booking['date'])
            ->where(function ($q1) use ($startUtc, $endUtc) { // 11:00 12:00 - 11:30 12:30
                $q1->where('end_time', '>', $startUtc)
                    ->where('start_time', '<', $endUtc)
            ->orWhere(function ($q2) use ($startUtc, $endUtc) {
                    $q2->where('start_time', '=', $startUtc); // 11:00 12:00 - 11:00 12:30
                });
            })
            ->exists();

        // Log the values for debugging
        Log::info('Booking Details:', ['room_id' => $booking['room_id'], 'date' => $booking['date'], 'start_time' => $booking['start_time'], 'end_time' => $booking['end_time']]);
    
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
        $bookings = Booking::with('room:id,name')->orderBy('start_time')->get()->map(function ($booking) {
            return [
                'room_name' => $booking->room->name,
                'user_name' => $booking->user_name,
                'date' => $booking->date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
            ];
        });
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
    
        // Construct requested datetime in SQL datetime format
        $requestedDateTime = $filters['date'] . ' ' . $filters['time'] . ':00';
        $requestedDate = $filters['date'];

        Log::info('Requested DateTime:', ['requestedDateTime' => $requestedDateTime]);
        Log::info('Requested Date:', ['requestedDate' => $requestedDate]);
        
         // Check for overlapping bookings
         $alreadyBooked = Booking::where('date', $requestedDate)
            ->where(function ($q1) use ($requestedDateTime) { // 11:00 12:00 - 11:30 12:30
                $q1->where('end_time', '>', $requestedDateTime)
                    ->where('start_time', '<=', $requestedDateTime)
            ->orWhere(function ($q2) use ($requestedDateTime) {
                    $q2->where('start_time', '=', $requestedDateTime); // 11:00 12:00 - 11:00
                });
            })
            ->pluck('room_id');
        

        Log::info('Booked Room IDs:', ['alreadyBooked' => $alreadyBooked]);

        // 2. Get available rooms by excluding booked ones
        $availableRooms = Room::whereNotIn('id', $alreadyBooked)->get();

        Log::info('Available Rooms:', ['availableRooms' => $availableRooms]);
    
        return response()->json($availableRooms->values());
    }
}
