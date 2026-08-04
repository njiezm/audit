<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_template_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('title');
            $table->unsignedTinyInteger('weight')->default(1);
            $table->text('hint')->nullable();
            $table->timestamps();

            $table->index(['audit_template_id', 'position']);
        });

        // Bibliothèque de catégories réutilisables (autocomplétion du formulaire).
        Schema::create('category_library', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->unsignedTinyInteger('default_weight')->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_library');
        Schema::dropIfExists('audit_template_categories');
        Schema::dropIfExists('audit_templates');
    }
};
