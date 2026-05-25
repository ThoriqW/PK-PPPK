<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('template_id')->constrained('agreement_templates');
            $table->foreignId('template_version_id')->constrained('agreement_template_versions');
            $table->foreignId('numbering_config_id')->constrained('numbering_configs');

            // Final rendered agreement number, e.g., "017/PPPK/BKPSDMD/V/2026".
            $table->string('agreement_number')->unique();
            // The raw {seq} value at issuance (for audit / re-rendering).
            $table->unsignedBigInteger('agreement_sequence');

            $table->enum('kind', ['BARU', 'PERPANJANGAN'])->default('BARU');
            // Self-reference: extension chains.
            $table->foreignId('parent_agreement_id')->nullable()
                ->constrained('agreements')->nullOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->enum('status', ['DRAFT', 'AKTIF', 'ARSIP', 'DIBATALKAN'])->default('DRAFT');

            $table->date('signed_at')->nullable();
            $table->string('signed_by_name')->nullable();
            $table->string('signed_by_position')->nullable();

            $table->string('pdf_path')->nullable();
            $table->string('pdf_hash', 64)->nullable();

            // Random URL-safe token embedded in the QR code. Lookup key for the public verifier.
            $table->string('qr_token', 64)->unique();

            // Frozen copy of employee data + computed fields at issuance time.
            $table->jsonb('snapshot');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('period_end');
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
