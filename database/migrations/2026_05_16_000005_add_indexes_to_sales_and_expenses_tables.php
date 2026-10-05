<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['created_at', 'status']);
            $table->index('customer_id');
            $table->index('payment_method_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['due_date', 'status']);
            $table->index(['paid_date', 'status']);
            $table->index(['type', 'status']);
            $table->index('category_id');
        });

        Schema::table('sale_installments', function (Blueprint $table) {
            $table->index(['due_date', 'is_paid']);
            $table->index(['paid_date', 'is_paid']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['active', 'stock']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'status']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['payment_method_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['due_date', 'status']);
            $table->dropIndex(['paid_date', 'status']);
            $table->dropIndex(['type', 'status']);
            $table->dropIndex(['category_id']);
        });

        Schema::table('sale_installments', function (Blueprint $table) {
            $table->dropIndex(['due_date', 'is_paid']);
            $table->dropIndex(['paid_date', 'is_paid']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['active', 'stock']);
        });
    }
};
