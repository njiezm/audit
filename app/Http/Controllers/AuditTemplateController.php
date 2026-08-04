<?php

namespace App\Http\Controllers;

use App\Models\AuditTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditTemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AuditTemplate::class);

        return view('templates.index', [
            'templates' => AuditTemplate::withCount('categories')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AuditTemplate::class);

        return view('templates.create', ['template' => new AuditTemplate]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AuditTemplate::class);

        $data = $this->validated($request);

        $template = DB::transaction(function () use ($data) {
            $template = AuditTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false),
                'created_by' => Auth::id(),
            ]);

            $this->syncCategories($template, $data['categories']);

            return $template;
        });

        return redirect()->route('templates.index')->with('success', 'Modèle « '.$template->name.' » créé.');
    }

    public function edit(AuditTemplate $template): View
    {
        $this->authorize('update', $template);

        $template->load('categories');

        return view('templates.edit', ['template' => $template]);
    }

    public function update(Request $request, AuditTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $data = $this->validated($request);

        DB::transaction(function () use ($template, $data) {
            $template->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);

            $template->categories()->delete();
            $this->syncCategories($template, $data['categories']);
        });

        return redirect()->route('templates.index')->with('success', 'Modèle mis à jour.');
    }

    public function destroy(AuditTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return back()->with('success', 'Modèle supprimé.');
    }

    /** Consommé par l'éditeur pour recharger les catégories sans quitter la page. */
    public function categories(AuditTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $template->load('categories');

        return response()->json(['categories' => $template->toCategoryPayload()]);
    }

    private function syncCategories(AuditTemplate $template, array $categories): void
    {
        foreach (array_values($categories) as $position => $category) {
            $template->categories()->create([
                'position' => $position,
                'title' => trim($category['title']),
                'weight' => max(1, (int) ($category['weight'] ?? 1)),
                'hint' => $category['hint'] ?? null,
            ]);
        }

        if ($template->is_default) {
            AuditTemplate::whereKeyNot($template->id)->update(['is_default' => false]);
        }
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'categories' => collect($request->input('categories', []))
                ->reject(fn ($c) => blank($c['title'] ?? null))
                ->values()
                ->all(),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['nullable', 'boolean'],
            'categories' => ['required', 'array', 'min:1', 'max:40'],
            'categories.*.title' => ['required', 'string', 'max:255'],
            'categories.*.weight' => ['required', 'integer', 'min:1', 'max:5'],
            'categories.*.hint' => ['nullable', 'string', 'max:500'],
        ], [
            'categories.required' => 'Un modèle doit comporter au moins une catégorie.',
        ]);
    }
}
