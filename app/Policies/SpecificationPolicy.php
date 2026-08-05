<?php

namespace App\Policies;

use App\Models\Specification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Le cahier des charges suit le sort de son audit : s'il est consultable,
 * le cahier l'est ; si l'audit est figé par sa signature, le cahier l'est
 * aussi — sans quoi on pourrait réécrire le chiffrage d'un rapport signé.
 */
class SpecificationPolicy
{
    public function view(User $user, Specification $specification): bool
    {
        return $user->can('view', $specification->audit);
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, Specification $specification): Response
    {
        return app(AuditPolicy::class)->update($user, $specification->audit);
    }

    public function delete(User $user, Specification $specification): Response
    {
        return app(AuditPolicy::class)->update($user, $specification->audit);
    }
}
