<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE sales 
            SET installments = (
                SELECT COUNT(*) 
                FROM sale_installments 
                WHERE sale_installments.sale_id = sales.id
            )
            WHERE installments IS NULL OR installments = 0
        ");
    }

    public function down(): void
    {
    }
};