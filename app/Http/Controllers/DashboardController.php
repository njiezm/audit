<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Client;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AuditService $audits)
    {
    }

    public function __invoke(): View
    {
        $this->authorize('viewAny', Audit::class);

        $stats = $this->audits->portfolioStats(Auth::user());

        return view('dashboard', [
            'stats' => $stats,
            'unsigned' => Audit::query()
                ->visibleTo(Auth::user())
                ->where('is_signed', false)
                ->with('client')
                ->orderBy('audit_date')
                ->limit(5)
                ->get(),
            // has() plutôt que having() : PostgreSQL n'autorise pas un HAVING
            // portant sur l'alias d'une sous-requête de comptage.
            'topClients' => Client::query()
                ->withCount('audits')
                ->has('audits')
                ->orderByDesc('audits_count')
                ->limit(5)
                ->get(),
        ]);
    }
}
