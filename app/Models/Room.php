<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * A room can have multiple bookings.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
