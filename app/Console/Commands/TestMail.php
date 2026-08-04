<?php

namespace App\Console\Commands;

use App\Mail\AuditReportMail;
use App\Models\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Vérifie la configuration de messagerie sans passer par l'interface.
 *
 *   php artisan mail:test destinataire@exemple.fr
 */
class TestMail extends Command
{
    protected $signature = 'mail:test {to : Adresse du destinataire} {--audit= : Référence ou identifiant d\'un audit à joindre}';

    protected $description = 'Envoie un message de contrôle et rapporte précisément ce qui se passe';

    public function handle(): int
    {
        $mailer = config('mail.default');

        $this->line('');
        $this->line('  Pilote     : '.$mailer);
        $this->line('  Hôte       : '.(config("mail.mailers.{$mailer}.host") ?: '—'));
        $this->line('  Port       : '.(config("mail.mailers.{$mailer}.port") ?: '—'));
        $this->line('  Chiffrement: '.(config("mail.mailers.{$mailer}.scheme") ?: '—'));
        $this->line('  Expéditeur : '.config('mail.from.address'));
        $this->line('');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->error("Le pilote « {$mailer} » n'envoie rien : le message est seulement journalisé.");
            $this->line('Renseignez MAIL_MAILER, MAIL_HOST et MAIL_PORT dans .env, puis `php artisan config:clear`.');

            return self::FAILURE;
        }

        if (blank(config("mail.mailers.{$mailer}.host"))) {
            $this->error('MAIL_HOST est vide : aucun serveur SMTP à contacter.');

            return self::FAILURE;
        }

        $audit = $this->resolveAudit();

        if (! $audit) {
            $this->error('Aucun audit disponible pour construire le message de test.');

            return self::FAILURE;
        }

        $to = $this->argument('to');
        $this->line("Envoi de {$audit->reference} vers {$to}…");

        try {
            Mail::to($to)->send(new AuditReportMail(
                $audit,
                "[Test] Rapport d'audit {$audit->reference}",
                'Message de contrôle émis par la commande mail:test.'
            ));
        } catch (\Throwable $e) {
            $this->error('Échec : '.get_class($e));
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Message accepté par le serveur SMTP.');
        $this->line("Vérifiez la boîte de réception de {$to} (et les indésirables).");

        return self::SUCCESS;
    }

    private function resolveAudit(): ?Audit
    {
        $reference = $this->option('audit');

        if (blank($reference)) {
            return Audit::orderByDesc('id')->first();
        }

        return Audit::where('reference', $reference)
            ->orWhere('id', is_numeric($reference) ? (int) $reference : 0)
            ->first();
    }
}
