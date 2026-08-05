<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « cahier des charges », facultatif et rattaché à un audit.
 *
 * L'audit constate ; le cahier des charges engage. Ce sont deux documents
 * de nature différente, d'où des tables séparées : un audit peut vivre sans
 * cahier des charges, et le cahier des charges porte ses propres notions —
 * périmètre, lots, charges, phases — qui n'ont pas de sens dans un constat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('title');
            $table->string('version', 20)->default('1.0');
            $table->string('status')->default('draft');

            // Blocs de tête, distincts des sections libres parce qu'ils
            // structurent la page de garde du document.
            $table->text('context')->nullable();
            $table->text('objectives')->nullable();
            $table->text('scope_in')->nullable();
            $table->text('scope_out')->nullable();

            // Enveloppe annoncée au client. Elle peut différer de la somme
            // des lots : l'écart est la marge de cadrage, et le document
            // affiche les deux plutôt que de masquer la différence.
            $table->unsignedSmallInteger('announced_days_min')->nullable();
            $table->unsignedSmallInteger('announced_days_max')->nullable();
            $table->unsignedInteger('daily_rate')->nullable();
            $table->string('currency', 3)->default('EUR');

            $table->date('starts_on')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('include_in_pdf')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Sections libres : contexte détaillé, contraintes, hypothèses,
        // critères d'acceptation, annexes techniques…
        Schema::create('specification_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specification_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('page_break_before')->default(false);
            $table->timestamps();

            $table->index(['specification_id', 'position']);
        });

        // Lots de travaux : c'est la partie chiffrée du document.
        Schema::create('specification_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specification_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('code', 12)->nullable();
            $table->string('name');
            $table->text('content')->nullable();
            $table->string('phase')->nullable();
            $table->unsignedSmallInteger('days_min')->default(0);
            $table->unsignedSmallInteger('days_max')->default(0);
            // Une option est chiffrée mais exclue du total du périmètre de base.
            $table->boolean('is_option')->default(false);
            $table->boolean('is_at_risk')->default(false);
            $table->string('risk_note')->nullable();
            $table->timestamps();

            $table->index(['specification_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specification_lots');
        Schema::dropIfExists('specification_sections');
        Schema::dropIfExists('specifications');
    }
};
