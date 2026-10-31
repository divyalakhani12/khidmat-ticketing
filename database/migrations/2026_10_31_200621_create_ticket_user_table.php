<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['ticket_id', 'user_id']);
        });

        /*
         * Preserve existing single-technician assignments.
         * Any current tickets.assigned_to value is copied
         * into the new pivot table.
         */
        $existingAssignments = DB::table('tickets')
            ->whereNotNull('assigned_to')
            ->select('id', 'assigned_to')
            ->get();

        foreach ($existingAssignments as $assignment) {
            DB::table('ticket_user')->insertOrIgnore([
                'ticket_id' => $assignment->id,
                'user_id' => $assignment->assigned_to,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_user');
    }
};