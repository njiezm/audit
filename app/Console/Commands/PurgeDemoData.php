<?php

namespace App\Console\Commands;

use App\Models\Audit;
use App\Models\Client;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class PurgeDemoData extends Command
{
    protected $signature = 'demo:purge';

    protected $description = 'Supprime définitivement les clients et audits du jeu de démonstration';

    public function handle(): int
    {
        $clients = Client::withTrashed()
            ->where('notes', 'like', DemoSeeder::DEMO_TAG.'%')
            ->get();

        if ($clients->isEmpty()) {
            $this->info('Aucune donnée de démonstration trouvée.');

            return self::SUCCESS;
        }

        $this->warn($clients->count().' client(s) de démonstration et leurs audits vont être supprimés.');

        if (! $this->option('no-interaction') && ! $this->confirm('Confirmer ?', true)) {
            return self::SUCCESS;
        }

        $audits = Audit::withTrashed()->whereIn('client_id', $clients->pluck('id'))->get();

        foreach ($audits as $audit) {
            $audit->forceDelete();
        }

        foreach ($clients as $client) {
            $client->forceDelete();
        }

        $this->info("{$audits->count()} audit(s) et {$clients->count()} client(s) supprimés.");

        return self::SUCCESS;
    }
}
