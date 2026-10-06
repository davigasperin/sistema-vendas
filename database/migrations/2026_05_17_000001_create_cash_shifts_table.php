<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('initial_amount', 12, 2)->default(0);
            $table->decimal('final_amount_reported', 12, 2)->nullable();
            $table->decimal('final_amount_expected', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['opened_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_shifts');
    }
};
