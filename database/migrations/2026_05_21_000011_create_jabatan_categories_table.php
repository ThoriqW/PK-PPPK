<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drives retirement age calculation. Seeded with the five categories
 * mandated by BKPSDMD: Fungsional Guru = 60, others = 58.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('jabatan_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->unsignedTinyInteger('retirement_age');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_categories');
    }
};
