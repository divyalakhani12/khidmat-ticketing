<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('merged_into_ticket_id')
                ->nullable()
                ->after('assigned_to')
                ->constrained('tickets')
                ->nullOnDelete();

            $table->timestamp('merged_at')
                ->nullable()
                ->after('merged_into_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['merged_into_ticket_id']);
            $table->dropColumn([
                'merged_into_ticket_id',
                'merged_at',
            ]);
        });
    }
};