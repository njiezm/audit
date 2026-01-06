<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'client_name',
        'audit_date',
        'audit_id',
        'conclusion'
    ];
    
    public function categories()
    {
        return $this->hasMany(AuditCategory::class);
    }
    
    public static function generateAuditId()
    {
        $year = date('Y');
        $random = mt_rand(1000, 9999);
        return "AUD-{$year}-{$random}";
    }
}