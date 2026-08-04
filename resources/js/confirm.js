/**
 * Modale de confirmation Bootstrap en remplacement de confirm().
 *
 * Un formulaire porteur de `data-confirm` ne part qu'après validation. Si
 * `data-confirm-phrase` est présent, l'utilisateur doit recopier la phrase :
 * c'est la double confirmation demandée pour les actions destructrices.
 */
import { Modal } from 'bootstrap';

export function initConfirmations() {
    const dialog = document.getElementById('confirm-modal');
    if (!dialog) return;

    const modal = new Modal(dialog);
    const titleEl = dialog.querySelector('[data-confirm-title]');
    const bodyEl = dialog.querySelector('[data-confirm-body]');
    const phraseWrap = dialog.querySelector('[data-confirm-phrase-wrap]');
    const phraseLabel = dialog.querySelector('[data-confirm-phrase-label]');
    const phraseInput = dialog.querySelector('[data-confirm-input]');
    const acceptBtn = dialog.querySelector('[data-confirm-accept]');

    let pendingForm = null;
    let expected = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();
        pendingForm = form;
        expected = form.dataset.confirmPhrase || null;

        titleEl.textContent = form.dataset.confirmTitle || 'Confirmer l\'action';
        bodyEl.textContent = form.dataset.confirm;
        acceptBtn.textContent = form.dataset.confirmAccept || 'Confirmer';
        acceptBtn.className = 'btn ' + (form.dataset.confirmDanger === 'true' ? 'btn-danger' : 'btn-nj');

        phraseWrap.hidden = !expected;
        acceptBtn.disabled = Boolean(expected);

        if (expected) {
            phraseLabel.textContent = expected;
            phraseInput.value = '';
        }

        modal.show();
    });

    phraseInput?.addEventListener('input', () => {
        acceptBtn.disabled = phraseInput.value.trim() !== expected;
    });

    acceptBtn?.addEventListener('click', () => {
        if (!pendingForm) return;

        pendingForm.dataset.confirmed = 'true';
        modal.hide();
        pendingForm.submit();
        pendingForm = null;
    });

    dialog.addEventListener('hidden.bs.modal', () => {
        // Le bouton d'origine reste utilisable si l'utilisateur renonce.
        pendingForm?.querySelectorAll('[type="submit"]').forEach((button) => {
            delete button.dataset.loading;
        });
        pendingForm = null;
    });
}
