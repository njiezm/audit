import './bootstrap';

// Bootstrap 5 était installé mais son JS n'était jamais chargé : aucun
// composant interactif (modale, dropdown, offcanvas, toast) ne fonctionnait.
import * as bootstrap from 'bootstrap';

import { initAuditEditor } from './audit-editor.js';
import { initTheme } from './theme.js';
import { initConfirmations } from './confirm.js';

window.bootstrap = bootstrap;
window.initAuditEditor = initAuditEditor;

initTheme();
initConfirmations();

// Empêche la double soumission sur tous les formulaires du site.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.noLoading === 'true') return;

    form.querySelectorAll('[type="submit"]').forEach((button) => {
        button.dataset.loading = 'true';
    });
});

// Auto-masquage des messages flash après lecture.
document.querySelectorAll('[data-autohide]').forEach((alert) => {
    setTimeout(() => {
        bootstrap.Alert.getOrCreateInstance(alert).close();
    }, Number(alert.dataset.autohide) || 6000);
});

// Cases à cocher des actions groupées.
const selectAll = document.querySelector('[data-select-all]');

if (selectAll) {
    const boxes = () => Array.from(document.querySelectorAll('[data-row-select]'));
    const bar = document.querySelector('[data-bulk-bar]');

    const sync = () => {
        const checked = boxes().filter((b) => b.checked).length;
        if (bar) bar.hidden = checked === 0;
        const counter = document.querySelector('[data-bulk-count]');
        if (counter) counter.textContent = checked;
        selectAll.indeterminate = checked > 0 && checked < boxes().length;
        selectAll.checked = checked > 0 && checked === boxes().length;
    };

    selectAll.addEventListener('change', () => {
        boxes().forEach((box) => { box.checked = selectAll.checked; });
        sync();
    });

    boxes().forEach((box) => box.addEventListener('change', sync));
    sync();
}
