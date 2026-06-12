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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('created_by_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('phone')->nullable()->after('password');
            $table->string('stack_name')->nullable()->after('phone');

            $table->index(['organization_id', 'stack_name']);
            $table->index(['created_by_id', 'stack_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'stack_name']);
            $table->dropIndex(['created_by_id', 'stack_name']);
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropColumn(['phone', 'stack_name']);
        });
    }
};
