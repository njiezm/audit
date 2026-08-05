<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Remplace l'ancien `AUD-{année}-{mt_rand(1000,9999)}` : sur une colonne
 * unique, ce tirage aléatoire entrait en collision avec ~50 % de probabilité
 * dès la centaine d'audits annuels, et l'échec remontait à l'utilisateur
 * sous forme d'erreur SQL brute avec perte du formulaire.
 */
class ReferenceGenerator
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $number = DB::transaction(function () use ($year) {
            $row = DB::table('audit_sequences')->where('year', $year)->lockForUpdate()->first();

            if ($row === null) {
                DB::table('audit_sequences')->insert([
                    'year' => $year,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $next = $row->last_number + 1;

            DB::table('audit_sequences')
                ->where('id', $row->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });

        return sprintf('AUD-%d-%04d', $year, $number);
    }

    /** Référence de cahier des charges : même compteur annuel, autre préfixe. */
    public function nextSpecification(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $count = DB::table('specifications')
            ->whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
            ->count();

        do {
            $count++;
            $reference = sprintf('CDC-%d-%04d', $year, $count);
        } while (DB::table('specifications')->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Aligne le compteur sur les références déjà présentes en base, pour que
     * la reprise d'un existant ne réutilise pas un numéro.
     */
    public function syncWithExisting(): void
    {
        $references = DB::table('audits')->whereNotNull('reference')->pluck('reference');
        $maxByYear = [];

        foreach ($references as $reference) {
            if (preg_match('/^AUD-(\d{4})-(\d+)$/', (string) $reference, $m)) {
                $year = (int) $m[1];
                $number = (int) $m[2];
                $maxByYear[$year] = max($maxByYear[$year] ?? 0, $number);
            }
        }

        foreach ($maxByYear as $year => $max) {
            $existing = DB::table('audit_sequences')->where('year', $year)->first();

            if ($existing === null) {
                DB::table('audit_sequences')->insert([
                    'year' => $year,
                    'last_number' => $max,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($existing->last_number < $max) {
                DB::table('audit_sequences')->where('id', $existing->id)
                    ->update(['last_number' => $max, 'updated_at' => now()]);
            }
        }
    }
}
