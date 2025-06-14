<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Memo extends Model
{
    protected $fillable = [
        'description', 
        'raised_by', 
        'raised_by_email', 
        'raised_by_name', 
        'issued_on', 
        'document_path'
    ];

    protected $casts = [
        'issued_on' => 'date',
    ];

    public function raiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }
} 