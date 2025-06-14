<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllocatedAssetMaster extends Model
{
    protected $table = 'allocated_asset_master';

    protected $fillable = [
        'asset_tag',
        'user_email',
        'assign_on',
        'status',
        'end_date'
    ];

    protected $casts = [
        'assign_on' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(AssetMaster::class, 'asset_tag', 'tag');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByUser($query, $email)
    {
        return $query->where('user_email', $email);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isInactive()
    {
        return $this->status === 'inactive';
    }
} 