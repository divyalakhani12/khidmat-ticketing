<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_comments', function (Blueprint $table) {
            // Requester has no login, so user_id must be allowed to be null
            $table->foreignId('user_id')
                ->nullable()
                ->change();

            $table->string('sender_type')
                ->default('staff')
                ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropColumn('sender_type');

            $table->foreignId('user_id')
                ->nullable(false)
                ->change();
        });
    }
};