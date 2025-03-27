<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade'); // Foreign key
            $table->string('user_name'); // Name of the user making the booking
            $table->date('date'); // Booking date
            $table->time('start_time'); // Start time of the booking
            $table->time('end_time'); // End time of the booking
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
