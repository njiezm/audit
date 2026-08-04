<?php

namespace App\Http\Controllers;

use App\Enums\AuditStatus;
use App\Http\Requests\AuditRequest;
use App\Mail\AuditReportMail;
use App\Models\ActivityLog;
use App\Models\Audit;
use App\Models\AuditTemplate;
use App\Models\CategoryLibraryEntry;
use App\Models\Client;
use App\Services\AuditService;
use App\Services\PdfRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    private const SORTABLE = ['reference', 'client_name', 'audit_date', 'global_score', 'status', 'created_at'];

    public function __construct(private readonly AuditService $audits, private readonly PdfRenderer $pdf)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Audit::class);

        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $audits = Audit::query()
            ->visibleTo(Auth::user())
            ->with(['client', 'owner'])
            ->withCount('categories')
            ->search($request->query('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->query('client_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('audit_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('audit_date', '<=', $request->query('to')))
            ->when($request->filled('min_score'), fn ($q) => $q->where('global_score', '>=', (float) $request->query('min_score')))
            ->when($request->filled('max_score'), fn ($q) => $q->where('global_score', '<=', (float) $request->query('max_score')))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('audits.index', [
            'audits' => $audits,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'statuses' => AuditStatus::options(),
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Audit::class);

        $template = $request->filled('template')
            ? AuditTemplate::with('categories')->find($request->query('template'))
            : AuditTemplate::with('categories')->where('is_default', true)->first();

        return view('audits.create', [
            'templates' => AuditTemplate::orderBy('name')->get(),
            'selectedTemplate' => $template,
            'initialCategories' => $template?->toCategoryPayload() ?? $this->blankCategories(),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'suggestions' => CategoryLibraryEntry::suggestions(),
        ]);
    }

    public function store(AuditRequest $request): RedirectResponse
    {
        $this->authorize('create', Audit::class);

        $audit = $this->audits->create($request->validated(), Auth::user());

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Audit '.$audit->reference.' créé.');
    }

    public function show(Audit $audit): View
    {
        $this->authorize('view', $audit);

        $audit->load(['categories.attachments', 'client', 'owner', 'signatory', 'versions.author']);

        return view('audits.show', [
            'audit' => $audit,
            'history' => $audit->activities()->limit(20)->get(),
            'previous' => $audit->client_id
                ? Audit::where('client_id', $audit->client_id)
                    ->whereKeyNot($audit->id)
                    ->whereNotNull('global_score')
                    ->where('audit_date', '<', $audit->audit_date)
                    ->latest('audit_date')
                    ->first()
                : null,
        ]);
    }

    public function edit(Audit $audit): View|RedirectResponse
    {
        $response = \Illuminate\Support\Facades\Gate::inspect('update', $audit);

        if ($response->denied()) {
            return redirect()->route('audits.show', $audit)->with('error', $response->message());
        }

        $audit->load('categories');

        return view('audits.edit', [
            'audit' => $audit,
            'templates' => AuditTemplate::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'suggestions' => CategoryLibraryEntry::suggestions(),
        ]);
    }

    public function update(AuditRequest $request, Audit $audit): RedirectResponse
    {
        $this->authorize('update', $audit);

        $this->audits->update($audit, $request->validated(), Auth::user());

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Audit '.$audit->reference.' mis à jour.');
    }

    public function destroy(Audit $audit): RedirectResponse
    {
        $this->authorize('delete', $audit);

        $reference = $audit->reference;
        ActivityLog::record('deleted', $audit, $reference);
        $audit->delete();

        return redirect()->route('audits.index')
            ->with('success', "Audit {$reference} placé dans la corbeille.")
            ->with('undo', route('audits.restore', $audit->id));
    }

    public function trash(): View
    {
        $this->authorize('viewAny', Audit::class);

        return view('audits.trash', [
            'audits' => Audit::onlyTrashed()
                ->visibleTo(Auth::user())
                ->with('client')
                ->latest('deleted_at')
                ->paginate(20),
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $audit = Audit::onlyTrashed()->visibleTo(Auth::user())->findOrFail($id);
        $this->authorize('restore', $audit);

        $audit->restore();
        ActivityLog::record('restored', $audit, $audit->reference);

        return back()->with('success', 'Audit '.$audit->reference.' restauré.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $audit = Audit::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $audit);

        $reference = $audit->reference;
        $audit->forceDelete();

        return back()->with('success', "Audit {$reference} supprimé définitivement.");
    }

    public function duplicate(Audit $audit): RedirectResponse
    {
        $this->authorize('view', $audit);
        $this->authorize('create', Audit::class);

        $copy = $this->audits->duplicate($audit, Auth::user());

        return redirect()->route('audits.edit', $copy)
            ->with('success', 'Audit dupliqué sous la référence '.$copy->reference.'.');
    }

    // ------------------------------------------------------------------
    // Cycle de vie
    // ------------------------------------------------------------------

    public function sign(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('sign', $audit);

        // Une signature engage l'auditeur : on redemande le mot de passe.
        $request->validate(
            ['password' => ['required', 'string']],
            ['password.required' => 'Confirmez votre mot de passe pour signer.']
        );

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect. L\'audit n\'a pas été signé.']);
        }

        $audit = $this->audits->sign($audit, Auth::user());

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Audit signé. Code de vérification : '.$audit->verification_code);
    }

    public function unsign(Audit $audit): RedirectResponse
    {
        $this->authorize('unsign', $audit);

        $this->audits->unsign($audit);

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Signature retirée. L\'audit repasse en brouillon et redevient modifiable.');
    }

    public function finalize(Audit $audit): RedirectResponse
    {
        $this->authorize('update', $audit);

        $this->audits->finalize($audit);

        return back()->with('success', 'Audit finalisé.');
    }

    public function archive(Audit $audit): RedirectResponse
    {
        $this->authorize('update', $audit);

        $this->audits->archive($audit);

        return back()->with('success', 'Audit archivé.');
    }

    public function unarchive(Audit $audit): RedirectResponse
    {
        $this->authorize('view', $audit);
        abort_unless(Auth::user()->canWrite(), 403);

        $this->audits->unarchive($audit);

        return back()->with('success', 'Audit sorti des archives.');
    }

    // ------------------------------------------------------------------
    // PDF & diffusion
    // ------------------------------------------------------------------

    public function downloadPdf(Audit $audit): Response
    {
        $this->authorize('view', $audit);

        ActivityLog::record('downloaded', $audit, $audit->reference);

        return $this->pdf->make($audit)->download($audit->pdfFilename());
    }

    /** Aperçu dans le navigateur : le vrai PDF, pas l'impression du HTML. */
    public function previewPdf(Audit $audit): Response
    {
        $this->authorize('view', $audit);

        return $this->pdf->make($audit)->stream($audit->pdfFilename());
    }

    public function sendForm(Audit $audit): View
    {
        $this->authorize('view', $audit);

        $audit->load('client');

        return view('audits.send', ['audit' => $audit]);
    }

    public function send(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('view', $audit);

        $data = $request->validate([
            'to' => ['required', 'email'],
            'cc' => ['nullable', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $mail = Mail::to($data['to']);

            if (! empty($data['cc'])) {
                $mail->cc($data['cc']);
            }

            $mail->send(new AuditReportMail($audit, $data['subject'], $data['message'] ?? null));
        } catch (\Throwable $e) {
            Log::error('Envoi du rapport impossible', ['audit' => $audit->id, 'exception' => $e]);

            return back()->withInput()
                ->with('error', "L'envoi a échoué. Vérifiez la configuration de messagerie.");
        }

        $audit->forceFill(['sent_at' => now()])->save();
        ActivityLog::record('sent', $audit, $data['to']);

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Rapport envoyé à '.$data['to'].'.');
    }

    /** Export CSV du portefeuille, filtres de la liste inclus. */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Audit::class);

        $query = Audit::query()
            ->visibleTo(Auth::user())
            ->with('client')
            ->withCount('categories')
            ->search($request->query('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->query('client_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('audit_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('audit_date', '<=', $request->query('to')))
            ->orderByDesc('audit_date');

        $filename = 'audits-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'wb');
            // BOM UTF-8 : sans lui, Excel casse les accents.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Référence', 'Intitulé', 'Client', 'Date', 'Statut', 'Score global',
                'Catégories', 'Signé le', 'Signé par', 'Code de vérification',
            ], ';');

            $query->chunk(200, function ($chunk) use ($out) {
                foreach ($chunk as $audit) {
                    fputcsv($out, [
                        $audit->reference,
                        $audit->title,
                        $audit->client_name,
                        optional($audit->audit_date)->format('d/m/Y'),
                        $audit->status->label(),
                        $audit->global_score !== null ? number_format($audit->global_score, 2, ',', '') : '',
                        $audit->categories_count,
                        optional($audit->signed_at)->format('d/m/Y H:i'),
                        $audit->signed_by,
                        $audit->verification_code,
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Actions groupées depuis la liste. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['archive', 'delete', 'unarchive'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $audits = Audit::query()->visibleTo(Auth::user())->whereKey($data['ids'])->get();
        $done = 0;
        $skipped = 0;

        foreach ($audits as $audit) {
            $ability = $data['action'] === 'delete' ? 'delete' : 'update';

            if (\Illuminate\Support\Facades\Gate::denies($ability, $audit)) {
                $skipped++;

                continue;
            }

            match ($data['action']) {
                'archive' => $this->audits->archive($audit),
                'unarchive' => $this->audits->unarchive($audit),
                'delete' => tap($audit)->delete(),
            };

            $done++;
        }

        $message = "{$done} audit(s) traité(s).";

        if ($skipped > 0) {
            $message .= " {$skipped} ignoré(s) (audit signé ou droits insuffisants).";
        }

        return back()->with($skipped > 0 ? 'warning' : 'success', $message);
    }

    private function blankCategories(): array
    {
        return [[
            'title' => '',
            'score' => 3,
            'weight' => 1,
            'observations' => '',
            'recommendations' => '',
            'priority' => '',
            'due_on' => '',
            'owner' => '',
        ]];
    }
}
