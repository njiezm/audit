<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\AuditTemplate;
use App\Models\Client;
use App\Policies\AuditPolicy;
use App\Policies\AuditTemplatePolicy;
use App\Policies\ClientPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Audit::class, AuditPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(AuditTemplate::class, AuditTemplatePolicy::class);

        // Dates et libellés en français dans toute l'application.
        \Carbon\Carbon::setLocale('fr');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Deux directives qui reviennent partout dans les vues et le PDF.
        Blade::directive('score', fn ($expression) => "<?php echo e(number_format((float) ($expression), 1, ',', ' ')); ?>");

        Blade::directive('nl', fn ($expression) => "<?php echo nl2br(e($expression)); ?>");

        // Balisage léger des champs libres : *gras*, `code`, puces.
        Blade::directive('rich', fn ($expression) => "<?php echo \App\Support\RichText::render($expression); ?>");
    }
}
