{{-- Remplace confirm() : les actions destructrices demandent en plus la
     recopie de la référence de l'audit. --}}
<div class="modal fade" id="confirm-modal" tabindex="-1" aria-labelledby="confirm-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirm-modal-title" data-confirm-title>Confirmer l'action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p data-confirm-body class="mb-0"></p>

                <div data-confirm-phrase-wrap hidden class="mt-3">
                    <label for="confirm-phrase" class="form-label small">
                        Saisissez <strong data-confirm-phrase-label></strong> pour confirmer :
                    </label>
                    <input type="text" class="form-control" id="confirm-phrase" data-confirm-input autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" data-confirm-accept>Confirmer</button>
            </div>
        </div>
    </div>
</div>
