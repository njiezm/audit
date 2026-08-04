<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Instantané figé du contenu à chaque signature : c'est ce qui permet
     * de prouver a posteriori ce qui a réellement été signé.
     */
    public function up(): void
    {
        Schema::create('audit_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->string('content_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['audit_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_versions');
    }
};
