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
        Schema::table('users', function (Blueprint $table) {
    $table->enum('role', [
        'super_admin',
        'branch_admin',
        'it_support'
    ])->default('it_support');

    $table->foreignId('branch_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
    $table->dropForeign(['branch_id']);
    $table->dropColumn(['role', 'branch_id']);
});
    }
};
