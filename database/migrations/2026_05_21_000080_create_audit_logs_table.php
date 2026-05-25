<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only history of important actions. The application never
 * UPDATE/DELETEs rows here; consider adding a Postgres trigger that
 * blocks UPDATE/DELETE in production for stronger guarantees.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64); // IMPORT | TEMPLATE_UPDATE | AGREEMENT_CREATE | AGREEMENT_EXTEND | ARCHIVE | NUMBERING_CHANGE | LOGIN | LOGOUT
            $table->string('subject_type', 96)->nullable(); // e.g., App\Models\Agreement
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 500);
            $table->jsonb('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
