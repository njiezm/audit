<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filigrane optionnel imprimé en diagonale sur chaque page du PDF :
     * « BROUILLON », « CONFIDENTIEL », « DIAGNOSTIC GRATUIT »…
     * Vide ou nul = aucun filigrane.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->string('watermark', 40)->nullable()->after('scoring_mode');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn('watermark');
        });
    }
};
