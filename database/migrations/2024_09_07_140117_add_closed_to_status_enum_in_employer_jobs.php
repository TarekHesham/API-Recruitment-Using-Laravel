<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void 
    {
        DB::statement("ALTER TABLE employer_jobs MODIFY COLUMN status ENUM('pending','accepted', 'rejected', 'cancelled') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE employer_jobs MODIFY COLUMN status ENUM('pending','accepted', 'rejected') NOT NULL");
    }
};
