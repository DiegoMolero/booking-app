<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // Fields that can be mass assigned
    protected $fillable = ['room_id', 'user_name', 'date', 'start_time', 'end_time'];

    // Cast attributes to appropriate data types
    protected $casts = [
        'start_time' => 'datetime:Y-m-d\TH:i:s\Z', // Stores date and time in ISO 8601 format (YYYY-MM-DDTHH:MM:SSZ)
        'end_time' => 'datetime:Y-m-d\TH:i:s\Z',   // Stores date and time in ISO 8601 format (YYYY-MM-DDTHH:MM:SSZ)
    ];

    /**
     * A booking belongs to a room.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
