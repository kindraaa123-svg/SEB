<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'code',
        'supervisor_link',
        'generated_by_supervisor_id',
        'is_active',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(AccessCodeUsage::class);
    }
}

