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
        Schema::create('question_test_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('input')->nullable();
            $table->text('expected_output');
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('sort_order')->default(1);
            $table->unsignedInteger('points')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'is_hidden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_test_cases');
    }
};
