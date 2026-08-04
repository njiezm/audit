<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        return view('clients.index', [
            'clients' => Client::query()
                ->search($request->query('q'))
                ->withCount('audits')
                ->withAvg('audits as average_score', 'global_score')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('clients.create', ['client' => new Client]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $client = Client::create($this->validated($request));
        $this->handleLogo($request, $client);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client créé.');
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $audits = $client->audits()->visibleTo(Auth::user())->withCount('categories')->get();

        return view('clients.show', [
            'client' => $client,
            'audits' => $audits,
            // Série chronologique des scores : la progression du client.
            'trend' => $audits
                ->filter(fn ($a) => $a->global_score !== null)
                ->sortBy('audit_date')
                ->map(fn ($a) => [
                    'label' => $a->audit_date->format('m/Y'),
                    'value' => (float) $a->global_score,
                    'reference' => $a->reference,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.edit', ['client' => $client]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($this->validated($request, $client));
        $this->handleLogo($request, $client);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        if ($client->audits()->exists()) {
            return back()->with('error', 'Ce client porte des audits : archivez-les avant de le supprimer.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprimé.');
    }

    private function validated(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'siret' => ['nullable', 'string', 'max:32'],
            'sector' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [], [
            'name' => 'raison sociale',
            'contact_email' => 'e-mail du contact',
        ]);
    }

    private function handleLogo(Request $request, Client $client): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        if ($client->logo_path) {
            Storage::disk('public')->delete($client->logo_path);
        }

        $client->update(['logo_path' => $request->file('logo')->store('client-logos', 'public')]);
    }
}
