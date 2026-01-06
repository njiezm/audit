<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditCategory extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'audit_id',
        'title',
        'score',
        'observations',
        'recommendations'
    ];
    
    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}