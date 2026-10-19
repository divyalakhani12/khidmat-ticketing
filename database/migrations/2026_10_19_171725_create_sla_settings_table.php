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
    Schema::create('sla_settings', function (Blueprint $table) {
        $table->id();

        $table->string('priority')->unique();

        // Time allowed before the first response
        $table->unsignedInteger('response_hours')->nullable();

        // Time allowed before the ticket should be resolved
        $table->unsignedInteger('resolution_hours')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_settings');
    }
};
