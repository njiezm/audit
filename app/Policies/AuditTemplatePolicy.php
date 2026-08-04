<?php

namespace App\Policies;

use App\Models\AuditTemplate;
use App\Models\User;

class AuditTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, AuditTemplate $template): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, AuditTemplate $template): bool
    {
        return $user->canWrite();
    }

    public function delete(User $user, AuditTemplate $template): bool
    {
        return $user->isAdmin();
    }
}
