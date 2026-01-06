<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; 

class AuditController extends Controller
{
    /**
     * Vérifie si l'utilisateur est authentifié.
     * Sinon, le redirige vers la page de connexion.
     *
     * @return \Illuminate\Http\RedirectResponse|null
     */
    private function checkAuth()
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }
        return null; // Retourne null si l'utilisateur est authentifié
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        $audits = Audit::latest()->paginate(10);
        return view('audits.index', compact('audits'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        return view('audits.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        $request->validate([
            'client_name' => 'required|string|max:255',
            'audit_date' => 'required|date',
            'conclusion' => 'nullable|string',
            'categories' => 'required|array|min:1',
            'categories.*.title' => 'required|string|max:255',
            'categories.*.score' => 'required|integer|min:1|max:5',
            'categories.*.observations' => 'nullable|string',
            'categories.*.recommendations' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $audit = Audit::create([
                'client_name' => $request->client_name,
                'audit_date' => $request->audit_date,
                'audit_id' => Audit::generateAuditId(),
                'conclusion' => $request->conclusion,
            ]);

            foreach ($request->categories as $category) {
                $audit->categories()->create($category);
            }

            DB::commit();
            return redirect()->route('audits.show', $audit->id)
                ->with('success', 'Audit créé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la création de l\'audit: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Audit $audit)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        $audit->load('categories');
        return view('audits.show', compact('audit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Audit $audit)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        $audit->load('categories');
        return view('audits.edit', compact('audit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Audit $audit)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        $request->validate([
            'client_name' => 'required|string|max:255',
            'audit_date' => 'required|date',
            'conclusion' => 'nullable|string',
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'nullable|integer|exists:audit_categories,id',
            'categories.*.title' => 'required|string|max:255',
            'categories.*.score' => 'required|integer|min:1|max:5',
            'categories.*.observations' => 'nullable|string',
            'categories.*.recommendations' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $audit->update([
                'client_name' => $request->client_name,
                'audit_date' => $request->audit_date,
                'conclusion' => $request->conclusion,
            ]);

            // Supprimer les anciennes catégories
            $audit->categories()->delete();

            // Créer les nouvelles catégories
            foreach ($request->categories as $category) {
                $audit->categories()->create($category);
            }

            DB::commit();
            return redirect()->route('audits.show', $audit->id)
                ->with('success', 'Audit mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'audit: ' . $e->getMessage());
        }
    }

    /**
     * Télécharge le rapport d'audit au format PDF.
     */
    public function downloadPdf(Audit $audit)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        // Charger les catégories associées à l'audit
        $audit->load('categories');

        // Partager la variable $audit avec toutes les vues
        view()->share('audit', $audit);

        // Charger la vue PDF et la convertir en objet PDF
        $pdf = Pdf::loadView('audits.pdf');

        // Télécharger le fichier avec un nom personnalisé
        return $pdf->download('audit-' . $audit->audit_id . '.pdf');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Audit $audit)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        try {
            $audit->delete();
            return redirect()->route('audits.index')
                ->with('success', 'Audit supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'audit: ' . $e->getMessage());
        }
    }

    /**
     * Signe l'audit
     */
    public function sign(Audit $audit)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        // Mettre à jour l'audit avec les informations de signature
        // On utilise session('user.name') car nous n'avons pas de système d'authentification Laravel complet
        $audit->update([
            'is_signed' => true,
            'signed_at' => now(),
            'signed_by' => session('user.name') ?? 'Expert N\'jie ZAMON'
        ]);
        
        return redirect()->route('audits.show', $audit->id)
            ->with('success', 'L\'audit a été signé avec succès.');
    }

    /**
     * Retire la signature de l'audit
     */
    public function unsign(Audit $audit)
    {
        $redirect = $this->checkAuth();
        if ($redirect) {
            return $redirect;
        }

        // Mettre à jour l'audit pour retirer les informations de signature
        $audit->update([
            'is_signed' => false,
            'signed_at' => null,
            'signed_by' => null
        ]);
        
        return redirect()->route('audits.show', $audit->id)
            ->with('success', 'La signature de l\'audit a été retirée avec succès.');
    }
}