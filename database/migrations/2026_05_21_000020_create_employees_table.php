<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Identity. nip is preferred, nik is fallback for duplicate detection.
            $table->string('nip', 32)->nullable()->unique();
            $table->string('nik', 32)->nullable()->unique();

            $table->string('full_name');
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth'); // required: drives retirement
            $table->enum('gender', ['L', 'P'])->nullable();

            $table->string('education')->nullable();
            $table->string('jabatan'); // free-text job title
            $table->foreignId('jabatan_category_id')->constrained('jabatan_categories');
            $table->string('golongan', 16)->nullable();

            $table->foreignId('opd_id')->constrained('opds');
            $table->string('unit_kerja')->nullable();

            $table->unsignedSmallInteger('appointment_year'); // tahun pengangkatan

            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();

            $table->enum('status', ['AKTIF', 'PENSIUN', 'NONAKTIF'])->default('AKTIF');

            // Bag of any extra columns from Excel that we don't have a dedicated column for.
            $table->jsonb('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('appointment_year');
            $table->index('status');
            $table->index(['opd_id', 'appointment_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
