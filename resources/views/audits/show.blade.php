@extends('layouts.app')

@section('title', 'Audit ' . $audit->audit_id . ' - Audit Master')

@section('content')
<div class="container">
    <!-- ... (le début du fichier reste inchangé) ... -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Audit: {{ $audit->audit_id }}</h1>
    <div class="btn-group">
        <a href="{{ route('audits.edit', $audit->id) }}" class="btn btn-outline-secondary">Modifier</a>
        
        @if($audit->is_signed)
            <!-- Si l'audit est signé, on affiche le badge et le bouton pour retirer -->
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success">Signé le {{ \Carbon\Carbon::parse($audit->signed_at)->format('d/m/Y') }}</span>
                <form action="{{ route('audits.unsign', $audit->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir retirer la signature ? Cette action est irréversible.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">Retirer la signature</button>
                </form>
            </div>
        @else
            <!-- Si l'audit n'est pas signé, on affiche le bouton pour signer -->
            <form action="{{ route('audits.sign', $audit->id) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-success">Signer</button>
            </form>
        @endif
        
        <a href="{{ route('audits.downloadPdf', $audit->id) }}" class="btn btn-nj">Télécharger PDF</a>
        <a href="{{ route('audits.index') }}" class="btn btn-outline-primary">Retour à la liste</a>
    </div>
</div>
<!-- ... (le reste du fichier reste inchangé) ... -->
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <div class="audit-preview">
        <!-- Page 1 -->
        <div class="report-page">
            <div class="report-header">
                <div>
                    <h1 class="brand-font" style="color: var(--nj-blue); margin: 0; font-size: 22px;">
                        RAPPORT D'AUDIT
                    </h1>
                    <p class="text-muted small mb-0">{{ $audit->audit_id }}</p>
                </div>
                <div class="text-end">
                    <div class="brand-font fs-5">NJIEZM<small>.FR</small></div>
                    <div class="small">{{ \Carbon\Carbon::parse($audit->audit_date)->format('d/m/Y') }}</div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5 class="fw-bold">Client : <span style="color: var(--nj-blue);">{{ $audit->client_name }}</span></h5>
            </div>
            
            <div class="content-container">
                @foreach($audit->categories as $category)
                    @php
                        $color = '#003366';
                        if($category->score <= 2) $color = '#ff4757';
                        if($category->score >= 4) $color = '#2ed573';
                    @endphp
                    <div class="category-block mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="category-title">{{ $category->title }}</div>
                            <div class="score-badge" style="background-color: {{ $color }}">{{ $category->score }}</div>
                        </div>
                        <div class="finding-item">
                            <div class="fw-bold small">Observations :</div>
                            <div class="mb-2">{{ $category->observations ?: "N/A" }}</div>
                            <div class="recommendation-box">
                                <strong>Conseil NJIEZM :</strong><br>
                                {{ $category->recommendations ?: "À définir." }}
                            </div>
                        </div>
                    </div>
                @endforeach
                
                @if($audit->conclusion)
                    <div class="mt-4" style="padding: 20px; border: 2px solid var(--nj-blue); background: #fdfdfd;">
                        <div class="brand-font mb-2">SYNTHÈSE GLOBALE</div>
                        <p style="font-size: 0.9rem; margin:0;">{{ $audit->conclusion }}</p>
                    </div>
                @endif
            </div>
            
            @if($audit->is_signed)
                <div class="mt-4 p-3 border bg-light text-center">
                    <p class="mb-2 fw-bold">Document signé électroniquement</p>
                    <p class="mb-2 small">Signé le {{ \Carbon\Carbon::parse($audit->signed_at)->format('d/m/Y à H:i') }} par {{ $audit->signed_by }}</p>
                    <img src="{{ asset('images/signature.png') }}" alt="Signature" style="max-height: 60px;">
                </div>
            @endif
            
            <div style="margin-top: auto; padding-top: 10px; border-top: 1px solid #eee; font-size: 10px;" class="d-flex justify-content-between opacity-50">
                <span>© NJIEZM.FR - Expertise Stratégique</span>
                <span>Page 1</span>
            </div>
        </div>
    </div>
</div>
@endsection