<?php

use App\Models\Audit;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Calcule le score global des audits antérieurs à son introduction.
     * Sans ce rattrapage, tous les audits existants s'affichent sans score
     * et sont exclus des moyennes du tableau de bord.
     */
    public function up(): void
    {
        Audit::withTrashed()->with('categories')->chunkById(100, function ($audits) {
            foreach ($audits as $audit) {
                $audit->forceFill(['global_score' => $audit->computeGlobalScore()])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Audit::withTrashed()->update(['global_score' => null]);
    }
};
