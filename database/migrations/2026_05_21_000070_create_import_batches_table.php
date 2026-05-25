<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('stored_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('inserted_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);

            $table->enum('status', ['PENDING', 'RUNNING', 'SUCCESS', 'PARTIAL', 'FAILED'])->default('PENDING');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_summary')->nullable();

            $table->timestamps();
        });

        Schema::create('import_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->jsonb('payload');
            $table->enum('outcome', ['INSERTED', 'DUPLICATE', 'INVALID', 'ERROR']);
            $table->text('error_message')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['import_batch_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_rows');
        Schema::dropIfExists('import_batches');
    }
};
