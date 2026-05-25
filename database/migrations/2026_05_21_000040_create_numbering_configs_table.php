<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable agreement number format and counter.
 * Format placeholders: {seq} {year} {month} {roman_month} {opd}
 * Example: "{seq}/PPPK/BKPSDMD/{roman_month}/{year}" -> "017/PPPK/BKPSDMD/V/2026"
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('numbering_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('format');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedTinyInteger('padding')->default(3);
            $table->enum('reset_policy', ['NEVER', 'YEARLY', 'MONTHLY'])->default('YEARLY');
            $table->unsignedSmallInteger('last_issued_year')->nullable();
            $table->unsignedTinyInteger('last_issued_month')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_configs');
    }
};
