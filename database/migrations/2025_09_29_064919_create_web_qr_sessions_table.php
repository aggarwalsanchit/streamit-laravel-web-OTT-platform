<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('web_qr_sessions', function (Blueprint $table) {
            $table->id();
            $table->char('session_id', 36)->unique(); // Unique QR token
            $table->foreignId('user_id')->nullable(); // Populated after scanning
            $table->enum('status', ['pending', 'authenticated', 'expired'])->default('pending');
            $table->timestamp('confirmed_at')->nullable(); // When scanned
            $table->timestamp('expires_at'); // Token expiry
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_qr_sessions');
    }
};
