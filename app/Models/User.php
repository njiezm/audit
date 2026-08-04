<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'job_title',
        'signature_path',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canWrite(): bool
    {
        return $this->is_active && $this->role->canWrite();
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    /** Signature manuscrite propre à l'auditeur, avec repli sur l'image livrée. */
    public function signatureFile(): ?string
    {
        if ($this->signature_path && file_exists(storage_path('app/public/'.$this->signature_path))) {
            return storage_path('app/public/'.$this->signature_path);
        }

        $fallback = public_path('images/signature.png');

        return file_exists($fallback) ? $fallback : null;
    }

    public function signatureUrl(): ?string
    {
        if ($this->signature_path && file_exists(storage_path('app/public/'.$this->signature_path))) {
            return asset('storage/'.$this->signature_path);
        }

        return file_exists(public_path('images/signature.png')) ? asset('images/signature.png') : null;
    }
}
