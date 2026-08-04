/**
 * Éditeur d'audit : formulaire + aperçu A4 paginé.
 *
 * Ce module remplace les deux copies identiques du script qui vivaient dans
 * create.blade.php et edit.blade.php. Il corrige au passage :
 *  - l'injection de HTML non échappé dans l'aperçu ;
 *  - le redessin intégral à chaque frappe (désormais débouncé) ;
 *  - la perte de la position de scroll de l'aperçu ;
 *  - le seuil de pagination écrit en dur (mesuré depuis le DOM).
 */

import { SCORE_SCALE, escapeHtml, rich, formatDate } from './lib/format.js';

const DEBOUNCE_MS = 220;

export function initAuditEditor(options) {
    const form = document.getElementById(options.formId || 'audit-form');
    const preview = document.getElementById(options.previewId || 'preview-area');
    const list = document.getElementById(options.listId || 'category-list');
    const probe = document.getElementById('pagination-probe');
    const template = document.getElementById('category-template');

    if (!form || !preview || !list || !template) return;

    const state = {
        reference: options.reference || '—',
        nextIndex: list.querySelectorAll('[data-category]').length,
        dirty: false,
    };

    // ------------------------------------------------------------------
    // Lignes de catégorie
    // ------------------------------------------------------------------

    function buildCategory(values = {}) {
        const fragment = template.content.cloneNode(true);
        const node = fragment.querySelector('[data-category]');
        const index = state.nextIndex++;

        node.querySelectorAll('[data-name]').forEach((field) => {
            const key = field.dataset.name;
            field.name = `categories[${index}][${key}]`;

            if (field.type === 'radio') {
                field.id = `cat-${index}-score-${field.value}`;
                const label = node.querySelector(`[data-score-label="${field.value}"]`);
                if (label) label.setAttribute('for', field.id);
                field.checked = String(values.score ?? 3) === field.value;
                return;
            }

            if (values[key] !== undefined && values[key] !== null) {
                field.value = values[key];
            }
        });

        if (values.hint) {
            const hint = node.querySelector('[data-hint]');
            if (hint) {
                hint.textContent = values.hint;
                hint.hidden = false;
            }
        }

        paintScorePicker(node);
        return node;
    }

    function addCategory(values) {
        list.appendChild(buildCategory(values));
        renumber();
        scheduleRender();
    }

    function removeCategory(node) {
        if (list.querySelectorAll('[data-category]').length <= 1) {
            flash("Un audit doit conserver au moins une catégorie.");
            return;
        }

        node.remove();
        renumber();
        scheduleRender();
    }

    /** Réindexe les noms de champs après ajout, suppression ou déplacement. */
    function renumber() {
        list.querySelectorAll('[data-category]').forEach((node, index) => {
            node.querySelectorAll('[data-name]').forEach((field) => {
                field.name = `categories[${index}][${field.dataset.name}]`;

                if (field.type === 'radio') {
                    const id = `cat-${index}-score-${field.value}`;
                    field.id = id;
                    const label = node.querySelector(`[data-score-label="${field.value}"]`);
                    if (label) label.setAttribute('for', id);
                }
            });

            const position = node.querySelector('[data-position]');
            if (position) position.textContent = index + 1;
        });
    }

    /** Colore les 5 segments du sélecteur selon la note retenue. */
    function paintScorePicker(scope) {
        scope.querySelectorAll('[data-score-picker]').forEach((picker) => {
            const checked = picker.querySelector('input:checked');
            const value = checked ? Number(checked.value) : 0;

            picker.querySelectorAll('label[data-score-label]').forEach((label) => {
                const level = Number(label.dataset.scoreLabel);
                const active = level === value;
                label.style.background = active ? SCORE_SCALE[level].color : '';
                label.style.borderColor = active ? SCORE_SCALE[level].color : '';
                label.style.color = active ? '#fff' : '';
            });

            const caption = picker.parentElement.querySelector('[data-score-caption]');
            if (caption) caption.textContent = value ? SCORE_SCALE[value].label : '';
        });
    }

    // ------------------------------------------------------------------
    // Lecture du formulaire
    // ------------------------------------------------------------------

    function readCategories() {
        return Array.from(list.querySelectorAll('[data-category]')).map((node) => {
            const get = (key) => node.querySelector(`[data-name="${key}"]`)?.value ?? '';
            const score = node.querySelector('[data-name="score"]:checked');

            return {
                title: get('title'),
                score: score ? Number(score.value) : 3,
                weight: Number(get('weight') || 1),
                observations: get('observations'),
                recommendations: get('recommendations'),
                priority: get('priority'),
                due_on: get('due_on'),
                owner: get('owner'),
            };
        });
    }

    function readHeader() {
        const value = (name) => form.querySelector(`[name="${name}"]`)?.value ?? '';

        return {
            client: value('client_name') || '[Client]',
            title: value('title'),
            date: value('audit_date'),
            conclusion: value('conclusion'),
            mode: value('scoring_mode') || 'weighted',
        };
    }

    // ------------------------------------------------------------------
    // Rendu de l'aperçu
    // ------------------------------------------------------------------

    function categoryHtml(category) {
        const level = SCORE_SCALE[category.score] || SCORE_SCALE[3];
        const weightBadge = category.weight > 1
            ? `<span class="badge text-bg-light border ms-2" style="font-size:10px">×${category.weight}</span>`
            : '';

        const meta = [];
        if (category.priority) meta.push(escapeHtml(priorityLabel(category.priority)));
        if (category.due_on) meta.push('échéance ' + escapeHtml(formatDate(category.due_on)));
        if (category.owner) meta.push(escapeHtml(category.owner));

        return `
            <div class="category-block">
                <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                    <div>
                        <span class="category-title">${escapeHtml(category.title || 'Catégorie')}</span>${weightBadge}
                    </div>
                    <div class="text-end">
                        <div class="score-badge score-badge--sm" style="background:${level.color}">${category.score}</div>
                        <div style="font-size:9px;color:${level.color};font-weight:700">${escapeHtml(level.label)}</div>
                    </div>
                </div>
                <div class="finding-item">
                    <div class="fw-bold small">Observations</div>
                    <div class="mb-2 rich" style="font-size:.88rem">${rich(category.observations) || '<em class="text-muted">Non renseigné.</em>'}</div>
                    <div class="recommendation-box">
                        <strong>Recommandation</strong>${meta.length ? ` <span style="font-style:normal;font-size:.78rem;color:#555">— ${meta.join(' · ')}</span>` : ''}<br>
                        <div class="rich">${rich(category.recommendations) || 'À définir.'}</div>
                    </div>
                </div>
            </div>`;
    }

    function priorityLabel(value) {
        return { critical: 'Critique', high: 'Élevée', medium: 'Moyenne', low: 'Faible' }[value] || value;
    }

    function globalScore(categories, mode) {
        if (!categories.length) return null;

        if (mode === 'simple') {
            return categories.reduce((sum, c) => sum + c.score, 0) / categories.length;
        }

        const weight = categories.reduce((sum, c) => sum + Math.max(1, c.weight), 0);
        if (!weight) return null;

        return categories.reduce((sum, c) => sum + c.score * Math.max(1, c.weight), 0) / weight;
    }

    function pageHtml(number, header, isContinuation) {
        return `
            <div class="report-header">
                <div>
                    <h2 class="brand-font m-0" style="color:var(--nj-blue);font-size:20px">
                        ${isContinuation ? "RAPPORT D'AUDIT (SUITE)" : "RAPPORT D'AUDIT"}
                    </h2>
                    <p class="text-muted small mb-0">${escapeHtml(state.reference)}</p>
                </div>
                <div class="text-end">
                    <div class="brand-font fs-5">NJIEZM<small>.FR</small></div>
                    <div class="small">${escapeHtml(formatDate(header.date))}</div>
                </div>
            </div>
            ${isContinuation ? '' : `
                <div class="mb-3">
                    <div class="fw-bold" style="font-size:1.05rem">
                        Client : <span style="color:var(--nj-blue)">${escapeHtml(header.client)}</span>
                    </div>
                    ${header.title ? `<div class="text-muted small">${escapeHtml(header.title)}</div>` : ''}
                </div>`}
            <div class="content-container flex-grow-1"></div>
            <div class="report-footer">
                <span>© NJIEZM.FR — Expertise Stratégique</span>
                <span>Page ${number}</span>
            </div>`;
    }

    function newPage(number, header, isContinuation) {
        const page = document.createElement('div');
        page.className = 'report-page';
        page.innerHTML = pageHtml(number, header, isContinuation);
        preview.appendChild(page);
        return page.querySelector('.content-container');
    }

    /**
     * Hauteur utile réelle d'une page, mesurée sur le DOM plutôt que
     * codée en dur à 850 px comme dans l'ancienne version.
     */
    function usableHeight(page) {
        const styles = getComputedStyle(page);
        const total = page.getBoundingClientRect().height;
        const chrome = Array.from(page.children)
            .filter((child) => !child.classList.contains('content-container'))
            .reduce((sum, child) => sum + child.getBoundingClientRect().height, 0);

        return total
            - parseFloat(styles.paddingTop)
            - parseFloat(styles.paddingBottom)
            - chrome
            - 12;
    }

    function measure(html) {
        if (!probe) return 0;
        probe.innerHTML = html;
        const height = probe.getBoundingClientRect().height;
        probe.innerHTML = '';
        return height;
    }

    function render() {
        const scrollTop = preview.scrollTop; // la position était perdue à chaque frappe
        const header = readHeader();
        const categories = readCategories();

        preview.innerHTML = '';

        let pageNumber = 1;
        let container = newPage(pageNumber, header, false);
        let limit = usableHeight(container.closest('.report-page'));

        // La hauteur consommée est cumulée dans une variable plutôt que
        // relue sur le conteneur : celui-ci est en `flex-grow`, donc il
        // occupe toujours la page entière, et la comparaison était vraie
        // dès le premier bloc — d'où une catégorie par page.
        let used = 0;

        const push = (html) => {
            const height = measure(html);

            if (used > 0 && used + height > limit) {
                pageNumber += 1;
                container = newPage(pageNumber, header, true);
                limit = usableHeight(container.closest('.report-page'));
                used = 0;
            }

            container.insertAdjacentHTML('beforeend', html);
            used += height;
        };

        const score = globalScore(categories, header.mode);

        if (score !== null) {
            const level = SCORE_SCALE[Math.max(1, Math.min(5, Math.round(score)))];
            push(`
                <div class="d-flex align-items-center gap-3 mb-4 p-3" style="border:2px solid ${level.color};border-radius:6px">
                    <div class="score-badge" style="background:${level.color}">${score.toFixed(1).replace('.', ',')}</div>
                    <div>
                        <div class="brand-font" style="font-size:15px">SCORE GLOBAL</div>
                        <div style="color:${level.color};font-weight:700">${escapeHtml(level.label)}</div>
                        <div class="text-muted" style="font-size:.76rem">
                            ${header.mode === 'weighted' ? 'Moyenne pondérée' : 'Moyenne simple'} sur ${categories.length} catégorie(s)
                        </div>
                    </div>
                </div>`);
        }

        categories.forEach((category) => push(categoryHtml(category)));

        if (header.conclusion.trim()) {
            push(`
                <div class="mt-3 p-3" style="border:2px solid var(--nj-blue);background:#fdfdfd">
                    <div class="brand-font mb-2">SYNTHÈSE GLOBALE</div>
                    <p style="font-size:.9rem;margin:0">${rich(header.conclusion)}</p>
                </div>`);
        }

        preview.scrollTop = scrollTop;
    }

    let timer = null;

    function scheduleRender() {
        clearTimeout(timer);
        timer = setTimeout(render, DEBOUNCE_MS);
    }

    // ------------------------------------------------------------------
    // Glisser-déposer
    // ------------------------------------------------------------------

    let dragged = null;

    list.addEventListener('dragstart', (event) => {
        const card = event.target.closest('[data-category]');
        if (!card) return;
        dragged = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragend', () => {
        list.querySelectorAll('[data-category]').forEach((n) => n.classList.remove('is-dragging', 'is-drop-target'));
        dragged = null;
        renumber();
        scheduleRender();
    });

    list.addEventListener('dragover', (event) => {
        if (!dragged) return;
        event.preventDefault();
        const target = event.target.closest('[data-category]');
        if (!target || target === dragged) return;

        list.querySelectorAll('[data-category]').forEach((n) => n.classList.remove('is-drop-target'));
        target.classList.add('is-drop-target');

        const box = target.getBoundingClientRect();
        const after = event.clientY > box.top + box.height / 2;
        list.insertBefore(dragged, after ? target.nextSibling : target);
    });

    // Équivalent clavier du glisser-déposer, sans quoi le réordonnancement
    // serait inaccessible sans souris.
    list.addEventListener('keydown', (event) => {
        const handle = event.target.closest('[data-handle]');
        if (!handle) return;

        const card = handle.closest('[data-category]');
        if (event.key === 'ArrowUp' && card.previousElementSibling) {
            event.preventDefault();
            list.insertBefore(card, card.previousElementSibling);
        } else if (event.key === 'ArrowDown' && card.nextElementSibling) {
            event.preventDefault();
            list.insertBefore(card.nextElementSibling, card);
        } else {
            return;
        }

        handle.focus();
        renumber();
        scheduleRender();
    });

    // ------------------------------------------------------------------
    // Événements
    // ------------------------------------------------------------------

    form.addEventListener('input', (event) => {
        state.dirty = true;

        if (event.target.matches('[data-name="score"]')) {
            paintScorePicker(event.target.closest('[data-category]'));
        }

        scheduleRender();
    });

    form.addEventListener('change', () => {
        state.dirty = true;
        scheduleRender();
    });

    document.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-category]');
        if (remove) {
            removeCategory(remove.closest('[data-category]'));
            return;
        }

        if (event.target.closest('[data-add-category]')) {
            addCategory();
            list.lastElementChild?.querySelector('[data-name="title"]')?.focus();
        }
    });

    // Chargement d'un modèle sans quitter la page.
    document.querySelector('[data-template-picker]')?.addEventListener('change', async (event) => {
        const id = event.target.value;
        if (!id) return;

        if (!confirm('Charger ce modèle remplacera les catégories actuelles. Continuer ?')) {
            event.target.value = '';
            return;
        }

        const response = await fetch(event.target.dataset.urlTemplate.replace('__ID__', id), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            flash('Modèle introuvable.');
            return;
        }

        const { categories } = await response.json();
        list.innerHTML = '';
        state.nextIndex = 0;
        categories.forEach((category) => list.appendChild(buildCategory(category)));
        renumber();
        render();
    });

    // Onglets Édition / Aperçu sous 1200 px.
    document.querySelectorAll('[data-pane-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.paneTarget;

            document.querySelectorAll('[data-pane]').forEach((pane) => {
                pane.classList.toggle('is-active', pane.dataset.pane === target);
            });

            document.querySelectorAll('[data-pane-target]').forEach((tab) => {
                tab.setAttribute('aria-selected', String(tab === button));
            });

            if (target === 'preview') render();
        });
    });

    // Ctrl+S enregistre, Ctrl+Entrée ajoute une catégorie.
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            form.requestSubmit();
        }

        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
            event.preventDefault();
            addCategory();
        }
    });

    // Un formulaire non enregistré ne se perd plus en fermant l'onglet.
    window.addEventListener('beforeunload', (event) => {
        if (!state.dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    form.addEventListener('submit', () => {
        state.dirty = false;
        renumber();

        const submit = form.querySelector('[type="submit"]');
        if (submit) submit.dataset.loading = 'true';
    });

    function flash(message) {
        const zone = document.getElementById('editor-flash');
        if (!zone) return;

        zone.textContent = message;
        zone.hidden = false;
        setTimeout(() => { zone.hidden = true; }, 4000);
    }

    list.querySelectorAll('[data-category]').forEach((node) => paintScorePicker(node));
    renumber();
    render();

    window.addEventListener('resize', scheduleRender);
}
