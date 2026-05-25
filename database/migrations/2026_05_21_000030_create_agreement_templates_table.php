<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agreement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('body_html');
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Per-version snapshot of body_html so issued PDFs can always be reproduced
        // identically, even if the live template is edited later.
        Schema::create('agreement_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('agreement_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('body_html');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_template_versions');
        Schema::dropIfExists('agreement_templates');
    }
};
