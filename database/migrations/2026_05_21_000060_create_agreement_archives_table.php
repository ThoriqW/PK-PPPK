<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tombstone for agreements that transitioned out of AKTIF (extended or cancelled).
 * agreements.status alone tells you the current state; this table tells you when
 * and why the change happened, plus who did it.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('agreement_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->timestamp('archived_at')->useCurrent();
            $table->string('archived_reason'); // e.g., "Perpanjangan oleh admin: Budi"
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_archives');
    }
};
