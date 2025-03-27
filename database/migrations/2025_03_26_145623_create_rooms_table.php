<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Room;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name');
            $table->timestamps();
        });

        // Insert 3 rooms with Japanese city names
        Room::insert([
            ['id' => 1, 'name' => 'Tokyo', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Osaka', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Kyoto', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
