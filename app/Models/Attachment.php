<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'audit_category_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'caption',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    protected static function booted(): void
    {
        // Le fichier physique suit la ligne : pas d'orphelins sur le disque.
        static::deleted(function (Attachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AuditCategory::class, 'audit_category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function absolutePath(): ?string
    {
        $path = Storage::disk($this->disk)->path($this->path);

        return file_exists($path) ? $path : null;
    }

    public function humanSize(): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size = max(0, (int) $this->size);
        $i = $size > 0 ? (int) floor(log($size, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($size / (1024 ** $i), $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
