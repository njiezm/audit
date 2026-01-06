<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $audits = Audit::latest()->paginate(10);
        return view('audits.index', compact('audits'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('audits.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        $audit->load('categories');
        return view('audits.show', compact('audit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Audit $audit)
    {
        $audit->load('categories');
        return view('audits.edit', compact('audit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Audit $audit)
    {
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
     * Remove the specified resource from storage.
     */
    public function destroy(Audit $audit)
    {
        try {
            $audit->delete();
            return redirect()->route('audits.index')
                ->with('success', 'Audit supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'audit: ' . $e->getMessage());
        }
    }
}