/**
 * Éditeur de cahier des charges : lots de travaux et sections libres.
 *
 * Deux listes répétables, réordonnables à la souris comme au clavier, et un
 * total de charge recalculé en direct pour que l'écart avec l'enveloppe
 * annoncée saute aux yeux au moment de la saisie plutôt qu'à la relecture.
 */

const LISTS = [
    { listId: 'lot-list', templateId: 'lot-template', itemAttr: 'lot', field: 'lots', addAttr: 'addLot', removeAttr: 'removeLot' },
    { listId: 'section-list', templateId: 'section-template', itemAttr: 'section', field: 'sections', addAttr: 'addSection', removeAttr: 'removeSection' },
];

export function initSpecificationEditor() {
    const form = document.querySelector('form[data-specification-form]');
    if (!form) return;

    LISTS.forEach((config) => setupList(form, config));

    recomputeTotals();
    form.addEventListener('input', recomputeTotals);
    form.addEventListener('change', recomputeTotals);
}

function setupList(form, config) {
    const list = document.getElementById(config.listId);
    const template = document.getElementById(config.templateId);
    if (!list || !template) return;

    let nextIndex = list.querySelectorAll(`[data-${config.itemAttr}]`).length;

    const renumber = () => {
        list.querySelectorAll(`[data-${config.itemAttr}]`).forEach((node, index) => {
            node.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(
                    new RegExp(`^${config.field}\\[[^\\]]*\\]`),
                    `${config.field}[${index}]`
                );
            });

            // Les couples case à cocher / libellé doivent garder un identifiant
            // unique, sinon un clic sur l'étiquette bascule la mauvaise ligne.
            node.querySelectorAll('input[type="checkbox"][id]').forEach((box) => {
                const suffix = box.id.replace(/^.*-\d+-/, '');
                const id = `${config.itemAttr}-${index}-${suffix}`;
                const label = node.querySelector(`label[for="${box.id}"]`);
                box.id = id;
                if (label) label.setAttribute('for', id);
            });
        });
    };

    document.querySelector(`[data-${kebab(config.addAttr)}]`)?.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        const node = fragment.querySelector(`[data-${config.itemAttr}]`);

        node.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace('__INDEX__', String(nextIndex));
        });
        node.querySelectorAll('[id]').forEach((el) => {
            el.id = el.id.replace('__INDEX__', String(nextIndex));
        });
        node.querySelectorAll('label[for]').forEach((el) => {
            el.setAttribute('for', el.getAttribute('for').replace('__INDEX__', String(nextIndex)));
        });

        list.appendChild(node);
        nextIndex += 1;
        renumber();
        recomputeTotals();
        node.querySelector('input[type="text"]')?.focus();
    });

    list.addEventListener('click', (event) => {
        if (!event.target.closest(`[data-${kebab(config.removeAttr)}]`)) return;

        event.target.closest(`[data-${config.itemAttr}]`).remove();
        renumber();
        recomputeTotals();
    });

    // Réordonnancement
    let dragged = null;

    list.addEventListener('dragstart', (event) => {
        const card = event.target.closest(`[data-${config.itemAttr}]`);
        if (!card) return;
        dragged = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragend', () => {
        list.querySelectorAll(`[data-${config.itemAttr}]`)
            .forEach((n) => n.classList.remove('is-dragging', 'is-drop-target'));
        dragged = null;
        renumber();
    });

    list.addEventListener('dragover', (event) => {
        if (!dragged) return;
        event.preventDefault();

        const target = event.target.closest(`[data-${config.itemAttr}]`);
        if (!target || target === dragged) return;

        list.querySelectorAll(`[data-${config.itemAttr}]`).forEach((n) => n.classList.remove('is-drop-target'));
        target.classList.add('is-drop-target');

        const box = target.getBoundingClientRect();
        list.insertBefore(dragged, event.clientY > box.top + box.height / 2 ? target.nextSibling : target);
    });

    list.addEventListener('keydown', (event) => {
        const handle = event.target.closest('[data-handle]');
        if (!handle) return;

        const card = handle.closest(`[data-${config.itemAttr}]`);

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
    });
}

/** Somme des lots hors options, et comparaison à l'enveloppe annoncée. */
function recomputeTotals() {
    const list = document.getElementById('lot-list');
    if (!list) return;

    let min = 0;
    let max = 0;

    list.querySelectorAll('[data-lot]').forEach((node) => {
        const isOption = node.querySelector('[data-name="is_option"]')?.checked;
        if (isOption) return;

        min += Number(node.querySelector('[data-name="days_min"]')?.value || 0);
        max += Number(node.querySelector('[data-name="days_max"]')?.value || 0);
    });

    const total = document.querySelector('[data-lot-total]');
    if (total) total.textContent = `${min} – ${max} j`;

    const note = document.querySelector('[data-envelope-note]');
    if (!note) return;

    const announcedMin = document.getElementById('announced_days_min')?.value;
    const announcedMax = document.getElementById('announced_days_max')?.value;

    if (announcedMin === '' && announcedMax === '') {
        note.hidden = true;
        return;
    }

    const deltaMin = Number(announcedMin || min) - min;
    const deltaMax = Number(announcedMax || max) - max;

    note.hidden = false;

    if (deltaMin === 0 && deltaMax === 0) {
        note.className = 'alert alert-secondary small mb-3';
        note.textContent = `L'enveloppe annoncée correspond exactement à la somme des lots (${min} – ${max} j).`;
        return;
    }

    if (deltaMin < 0 || deltaMax < 0) {
        note.className = 'alert alert-danger small mb-3';
        note.textContent =
            `L'enveloppe annoncée est inférieure à la somme des lots (${min} – ${max} j). `
            + `Le chiffrage ne couvre pas le chantier décrit.`;
        return;
    }

    note.className = 'alert alert-secondary small mb-3';
    note.textContent =
        `Somme des lots : ${min} – ${max} j. Marge de cadrage retenue : `
        + `+${deltaMin} à +${deltaMax} j.`;
}

function kebab(value) {
    return value.replace(/[A-Z]/g, (c) => '-' + c.toLowerCase());
}
