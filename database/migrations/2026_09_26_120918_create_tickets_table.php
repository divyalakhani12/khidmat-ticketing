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
        Schema::create('tickets', function (Blueprint $table) {
    $table->id();

    $table->string('ticket_number')->unique();
    $table->string('tracking_token')->unique();

    $table->string('requester_name');
    $table->string('requester_email');
    $table->string('contact_number')->nullable();

    $table->foreignId('department_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    $table->foreignId('branch_id')
        ->constrained();

    $table->foreignId('category_id')
        ->constrained();

    $table->enum('priority', [
        'Low',
        'Medium',
        'High',
        'Critical'
    ])->default('Medium');

    $table->string('subject');
    $table->text('description');

    $table->enum('status', [
        'New',
        'Assigned',
        'In Progress',
        'On Hold',
        'Resolved',
        'Closed',
        'Reopened'
    ])->default('New');

    $table->foreignId('assigned_to')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamp('due_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamp('closed_at')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
