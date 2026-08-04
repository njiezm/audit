<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Client $client): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->canWrite();
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }
}
