<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compteur par année, verrouillé en base, qui remplace le mt_rand()
     * de l'ancien generateAuditId() et sa collision garantie.
     */
    public function up(): void
    {
        Schema::create('audit_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_sequences');
    }
};
