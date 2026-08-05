<?php

namespace App\Http\Controllers;

use App\Enums\SpecificationStatus;
use App\Http\Requests\SpecificationRequest;
use App\Models\Audit;
use App\Models\Specification;
use App\Services\PdfRenderer;
use App\Services\SpecificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Cahier des charges d'un audit — module facultatif.
 *
 * Un audit n'en possède qu'un au plus, d'où des routes imbriquées sans
 * identifiant propre : /audits/{audit}/cahier-des-charges.
 */
class SpecificationController extends Controller
{
    public function __construct(
        private readonly SpecificationService $specifications,
        private readonly PdfRenderer $pdf,
    ) {
    }

    public function show(Audit $audit): View|RedirectResponse
    {
        $this->authorize('view', $audit);

        $specification = $audit->specification;

        if (! $specification) {
            return redirect()->route('audits.show', $audit)
                ->with('info', "Cet audit ne comporte pas encore de cahier des charges.");
        }

        $specification->load(['sections', 'lots']);

        return view('specifications.show', [
            'audit' => $audit,
            'specification' => $specification,
        ]);
    }

    public function create(Audit $audit): View|RedirectResponse
    {
        $this->authorize('update', $audit);

        if ($audit->specification) {
            return redirect()->route('audits.specification.edit', $audit);
        }

        return view('specifications.create', [
            'audit' => $audit,
            'specification' => new Specification([
                'title' => 'Cahier des charges — '.$audit->display_title,
                'version' => '1.0',
                'status' => SpecificationStatus::Draft->value,
                'currency' => 'EUR',
                'include_in_pdf' => true,
            ]),
            'statuses' => SpecificationStatus::options(),
            'sections' => $this->starterSections(),
            'lots' => [],
        ]);
    }

    public function store(SpecificationRequest $request, Audit $audit): RedirectResponse
    {
        $this->authorize('update', $audit);

        abort_if($audit->specification !== null, 409, 'Cet audit possède déjà un cahier des charges.');

        $specification = $this->specifications->createFor($audit, $request->validated(), Auth::user());

        return redirect()->route('audits.specification.show', $audit)
            ->with('success', 'Cahier des charges '.$specification->reference.' créé.');
    }

    public function edit(Audit $audit): View|RedirectResponse
    {
        $specification = $audit->specification;

        if (! $specification) {
            return redirect()->route('audits.specification.create', $audit);
        }

        $response = Gate::inspect('update', $specification);

        if ($response->denied()) {
            return redirect()->route('audits.specification.show', $audit)
                ->with('error', $response->message());
        }

        $specification->load(['sections', 'lots']);

        return view('specifications.edit', [
            'audit' => $audit,
            'specification' => $specification,
            'statuses' => SpecificationStatus::options(),
            'sections' => $specification->sections->map(fn ($s) => [
                'title' => $s->title,
                'body' => $s->body,
                'page_break_before' => $s->page_break_before,
            ])->all(),
            'lots' => $specification->lots->map(fn ($l) => [
                'code' => $l->code,
                'name' => $l->name,
                'content' => $l->content,
                'phase' => $l->phase,
                'days_min' => $l->days_min,
                'days_max' => $l->days_max,
                'is_option' => $l->is_option,
                'is_at_risk' => $l->is_at_risk,
                'risk_note' => $l->risk_note,
            ])->all(),
        ]);
    }

    public function update(SpecificationRequest $request, Audit $audit): RedirectResponse
    {
        $specification = $audit->specification;
        abort_if($specification === null, 404);

        $this->authorize('update', $specification);

        $this->specifications->update($specification, $request->validated(), Auth::user());

        return redirect()->route('audits.specification.show', $audit)
            ->with('success', 'Cahier des charges mis à jour.');
    }

    public function destroy(Audit $audit): RedirectResponse
    {
        $specification = $audit->specification;
        abort_if($specification === null, 404);

        $this->authorize('delete', $specification);

        $reference = $specification->reference;
        $specification->delete();

        return redirect()->route('audits.show', $audit)
            ->with('success', "Cahier des charges {$reference} supprimé.");
    }

    /** PDF du seul cahier des charges, sans le rapport d'audit. */
    public function pdf(Audit $audit): Response
    {
        $this->authorize('view', $audit);

        $specification = $audit->specification;
        abort_if($specification === null, 404);

        return $this->pdf->makeSpecification($specification)->stream($specification->pdfFilename());
    }

    /** Trame de départ : l'ossature d'un cahier des charges classique. */
    private function starterSections(): array
    {
        return [
            ['title' => 'Contraintes', 'body' => "Délais, budget, réglementaire, technique…", 'page_break_before' => false],
            ['title' => 'Hypothèses retenues', 'body' => "Ce sur quoi le chiffrage repose et qui reste à confirmer.", 'page_break_before' => false],
            ['title' => "Critères d'acceptation", 'body' => "Ce qui permettra de déclarer le chantier terminé.", 'page_break_before' => false],
            ['title' => 'Livrables', 'body' => "Ce qui sera remis, et sous quelle forme.", 'page_break_before' => false],
        ];
    }
}
