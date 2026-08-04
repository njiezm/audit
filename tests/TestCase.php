<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function admin(array $attributes = []): User
    {
        return $this->userWithRole(UserRole::Admin, $attributes);
    }

    protected function auditor(array $attributes = []): User
    {
        return $this->userWithRole(UserRole::Auditor, $attributes);
    }

    protected function viewer(array $attributes = []): User
    {
        return $this->userWithRole(UserRole::Viewer, $attributes);
    }

    protected function userWithRole(UserRole $role, array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create(array_merge([
            'name' => $role->label().' '.$sequence,
            'email' => $role->value.$sequence.'@example.test',
            'password' => Hash::make('motdepasse-solide-1'),
            'role' => $role->value,
            'is_active' => true,
        ], $attributes));
    }

    /** Audit complet, tel que le produirait le formulaire. */
    protected function makeAudit(User $author, array $overrides = []): Audit
    {
        return app(AuditService::class)->create(array_merge([
            'client_name' => 'Client de test',
            'title' => 'Mission de test',
            'audit_date' => now()->toDateString(),
            'scoring_mode' => 'weighted',
            'watermark' => null,
            'conclusion' => 'Synthèse de test.',
            'categories' => [
                ['title' => 'Sécurité', 'score' => 2, 'weight' => 3, 'observations' => 'Constat A'],
                ['title' => 'Infrastructure', 'score' => 4, 'weight' => 1, 'observations' => 'Constat B'],
            ],
        ], $overrides), $author);
    }
}
