@extends('layouts.app')

@section('title', 'Modifier l\'audit ' . $audit->audit_id . ' - Audit Master')

@section('content')
<div class="main-container">
    <!-- FORMULAIRE -->
    <aside class="audit-form">
        <form action="{{ route('audits.update', $audit->id) }}" method="POST" id="audit-form">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="fw-bold small text-uppercase">Client & Date</label>
                <input type="text" name="client_name" class="form-control mb-2" placeholder="Nom de l'entreprise" value="{{ old('client_name', $audit->client_name) }}" required>
                <input type="date" name="audit_date" class="form-control mb-2" value="{{ old('audit_date', $audit->audit_date) }}" required>
            </div>

            <hr>

            <div id="form-sections">
                @foreach($audit->categories as $index => $category)
                    <div class="section-card category-item">
                        <button type="button" class="delete-btn" onclick="removeCategory(this)">×</button>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <input type="text" name="categories[{{ $index }}][title]" class="form-control form-control-sm fw-bold border-0 bg-transparent p-0" placeholder="Titre de la catégorie" value="{{ old('categories.'.$index.'.title', $category->title) }}" required>
                            <select name="categories[{{ $index }}][score]" class="form-select form-select-sm w-25">
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('categories.'.$index.'.score', $category->score) == $i ? 'selected' : '' }}>{{ $i }}/5</option>
                                @endfor
                            </select>
                        </div>
                        <textarea name="categories[{{ $index }}][observations]" class="form-control form-control-sm mb-2" rows="2" placeholder="Observations...">{{ old('categories.'.$index.'.observations', $category->observations) }}</textarea>
                        <textarea name="categories[{{ $index }}][recommendations]" class="form-control form-control-sm" rows="2" placeholder="Recommandations...">{{ old('categories.'.$index.'.recommendations', $category->recommendations) }}</textarea>
                    </div>
                @endforeach
            </div>
            
            <button type="button" class="btn btn-dark w-100 brand-font mt-3" onclick="addNewCategory()">+ Ajouter une catégorie</button>

            <div class="mt-4 p-3 border bg-light">
                <label class="fw-bold small text-uppercase">Conclusion de l'Expert</label>
                <textarea name="conclusion" class="form-control" rows="5" placeholder="Votre synthèse globale...">{{ old('conclusion', $audit->conclusion) }}</textarea>
            </div>
            
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-nj w-50">Mettre à jour</button>
                <button type="button" class="btn btn-outline-secondary w-50" onclick="window.print()">Aperçu PDF</button>
            </div>
        </form>
    </aside>

    <!-- PREVIEW DYNAMIQUE -->
    <main class="audit-preview" id="preview-area">
        <!-- Les pages seront générées ici via JS -->
    </main>
</div>

<!-- Div invisible pour mesurer la hauteur réelle du contenu avant injection -->
<div id="temp-measurement"></div>
@endsection

@push('scripts')
<script>
    let categoryIndex = {{ $audit->categories->count() }}; // Commence au nombre actuel de catégories

    function addNewCategory() {
        const container = document.getElementById('form-sections');
        const newCategory = document.createElement('div');
        newCategory.className = 'section-card category-item';
        newCategory.innerHTML = `
            <button type="button" class="delete-btn" onclick="removeCategory(this)">×</button>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <input type="text" name="categories[${categoryIndex}][title]" class="form-control form-control-sm fw-bold border-0 bg-transparent p-0" placeholder="Titre de la catégorie" required>
                <select name="categories[${categoryIndex}][score]" class="form-select form-select-sm w-25">
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }}/5</option>
                    @endfor
                </select>
            </div>
            <textarea name="categories[${categoryIndex}][observations]" class="form-control form-control-sm mb-2" rows="2" placeholder="Observations..."></textarea>
            <textarea name="categories[${categoryIndex}][recommendations]" class="form-control form-control-sm" rows="2" placeholder="Recommandations..."></textarea>
        `;
        container.appendChild(newCategory);
        categoryIndex++;
        updateReport();
    }

    function removeCategory(button) {
        const categories = document.querySelectorAll('.category-item');
        if (categories.length > 1) {
            button.parentElement.remove();
            updateReport();
        }
    }

    function getCategoryHTML(title, score, obs, rec) {
        let color = '#003366';
        if(score <= 2) color = '#ff4757';
        if(score >= 4) color = '#2ed573';

        return `
            <div class="category-block mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="category-title">${title}</div>
                    <div class="score-badge" style="background-color: ${color}">${score}</div>
                </div>
                <div class="finding-item">
                    <div class="fw-bold small">Observations :</div>
                    <div class="mb-2">${obs || "N/A"}</div>
                    <div class="recommendation-box">
                        <strong>Conseil NJIEZM :</strong><br>
                        ${rec || "À définir."}
                    </div>
                </div>
            </div>`;
    }

    function updateReport() {
        const previewArea = document.getElementById('preview-area');
        const clientName = document.querySelector('input[name="client_name"]').value || "[Client]";
        const dateStr = document.querySelector('input[name="audit_date"]').value;
        const conclusion = document.querySelector('textarea[name="conclusion"]').value;
        const auditId = "{{ $audit->audit_id }}";

        previewArea.innerHTML = ""; // Clear existing pages

        // Page Creation Logic
        let currentPage = createPage(1, clientName, dateStr, auditId);
        let currentContentDiv = currentPage.querySelector('.content-container');
        previewArea.appendChild(currentPage);

        const temp = document.getElementById('temp-measurement');
        let pageNum = 1;

        // Add categories
        const categories = document.querySelectorAll('.category-item');
        categories.forEach(category => {
            const title = category.querySelector('input[name*="[title]"]').value;
            const score = category.querySelector('select[name*="[score]"]').value;
            const obs = category.querySelector('textarea[name*="[observations]"]').value;
            const rec = category.querySelector('textarea[name*="[recommendations]"]').value;
            
            const html = getCategoryHTML(title, score, obs, rec);
            temp.innerHTML = html;
            
            // Check if adding this category overflows the current page
            // A4 height ~1122px, minus margins/headers/footers ~900px usable
            if (currentContentDiv.offsetHeight + temp.offsetHeight > 850) {
                pageNum++;
                currentPage = createPage(pageNum, clientName, dateStr, auditId, true);
                currentContentDiv = currentPage.querySelector('.content-container');
                previewArea.appendChild(currentPage);
            }
            currentContentDiv.innerHTML += html;
        });

        // Add Conclusion
        if(conclusion) {
            const concHTML = `
                <div class="mt-4" style="padding: 20px; border: 2px solid var(--nj-blue); background: #fdfdfd;">
                    <div class="brand-font mb-2">SYNTHÈSE GLOBALE</div>
                    <p style="font-size: 0.9rem; margin:0;">${conclusion}</p>
                </div>`;
            temp.innerHTML = concHTML;

            if (currentContentDiv.offsetHeight + temp.offsetHeight > 850) {
                pageNum++;
                currentPage = createPage(pageNum, clientName, dateStr, auditId, true);
                currentContentDiv = currentPage.querySelector('.content-container');
                previewArea.appendChild(currentPage);
            }
            currentContentDiv.innerHTML += concHTML;
        }
    }

    function createPage(num, client, date, id, isSubsequent = false) {
        const page = document.createElement('div');
        page.className = 'report-page';
        page.innerHTML = `
            <div class="report-header">
                <div>
                    <h1 class="brand-font" style="color: var(--nj-blue); margin: 0; font-size: 22px;">
                        ${isSubsequent ? 'SUITE D\'AUDIT' : 'RAPPORT D\'AUDIT'}
                    </h1>
                    <p class="text-muted small mb-0">${id}</p>
                </div>
                <div class="text-end">
                    <div class="brand-font fs-5">NJIEZM<small>.FR</small></div>
                    <div class="small">${date ? new Date(date).toLocaleDateString('fr-FR') : ''}</div>
                </div>
            </div>
            ${!isSubsequent ? `
            <div class="mb-4">
                <h5 class="fw-bold">Client : <span style="color: var(--nj-blue);">${client}</span></h5>
            </div>` : ''}
            <div class="content-container"></div>
            <div style="margin-top: auto; padding-top: 10px; border-top: 1px solid #eee; font-size: 10px;" class="d-flex justify-content-between opacity-50">
                <span>© NJIEZM.FR - Expertise Stratégique</span>
                <span>Page ${num}</span>
            </div>
        `;
        return page;
    }

    // Écouter les changements dans le formulaire
    document.getElementById('audit-form').addEventListener('input', updateReport);
    
    // Initialiser l'aperçu au chargement
    document.addEventListener('DOMContentLoaded', updateReport);
</script>
@endpush