<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Audit $audit): bool
    {
        return $user->is_active && ($user->isAdmin() || $this->owns($user, $audit));
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    /**
     * Le verrou central du produit : un audit signé ou archivé n'est plus
     * modifiable. Sans cette règle, le PDF pouvait annoncer « signé le X »
     * avec un contenu réécrit après coup.
     */
    public function update(User $user, Audit $audit): Response
    {
        if (! $user->canWrite()) {
            return Response::deny("Votre compte est en lecture seule.");
        }

        if (! $user->isAdmin() && ! $this->owns($user, $audit)) {
            return Response::deny("Cet audit ne vous est pas attribué.");
        }

        if ($audit->isLocked()) {
            return Response::deny(
                "Cet audit est {$audit->status->label()} : son contenu est figé. Retirez la signature pour le modifier."
            );
        }

        return Response::allow();
    }

    public function delete(User $user, Audit $audit): Response
    {
        if (! $user->canWrite()) {
            return Response::deny("Votre compte est en lecture seule.");
        }

        if (! $user->isAdmin() && ! $this->owns($user, $audit)) {
            return Response::deny("Cet audit ne vous est pas attribué.");
        }

        if ($audit->isLocked()) {
            return Response::deny(
                "Un audit {$audit->status->label()} ne peut pas être supprimé. Retirez d'abord la signature."
            );
        }

        return Response::allow();
    }

    public function restore(User $user, Audit $audit): bool
    {
        return $user->canWrite() && ($user->isAdmin() || $this->owns($user, $audit));
    }

    public function forceDelete(User $user, Audit $audit): bool
    {
        return $user->isAdmin();
    }

    public function sign(User $user, Audit $audit): Response
    {
        if (! $user->canWrite()) {
            return Response::deny("Votre compte est en lecture seule.");
        }

        if (! $user->isAdmin() && ! $this->owns($user, $audit)) {
            return Response::deny("Cet audit ne vous est pas attribué.");
        }

        if ($audit->is_signed) {
            return Response::deny('Cet audit est déjà signé.');
        }

        if ($audit->categories()->count() === 0) {
            return Response::deny('Un audit sans catégorie ne peut pas être signé.');
        }

        return Response::allow();
    }

    public function unsign(User $user, Audit $audit): Response
    {
        if (! $user->canWrite()) {
            return Response::deny("Votre compte est en lecture seule.");
        }

        if (! $user->isAdmin() && ! $this->owns($user, $audit)) {
            return Response::deny("Cet audit ne vous est pas attribué.");
        }

        if (! $audit->is_signed) {
            return Response::deny("Cet audit n'est pas signé.");
        }

        if ($audit->is_countersigned) {
            return Response::deny(
                'Le client a contre-signé ce rapport : la signature ne peut plus être retirée.'
            );
        }

        return Response::allow();
    }

    private function owns(User $user, Audit $audit): bool
    {
        return $audit->user_id === $user->id || $audit->created_by === $user->id;
    }
}
