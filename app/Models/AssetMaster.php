<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMaster extends Model
{
    protected $table = 'asset_master';

    protected $fillable = [
        'type',
        'ownership',
        'warranty',
        'warranty_start',
        'warranty_end',
        'serial_number',
        'tag',
        'model',
        'location',
        'status'
    ];

    protected $casts = [
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function allocations()
    {
        return $this->hasMany(AllocatedAssetMaster::class, 'asset_tag', 'tag');
    }

    public function currentAllocation()
    {
        return $this->hasOne(AllocatedAssetMaster::class, 'asset_tag', 'tag')
            ->where('status', 'active');
    }

    public function assetType()
    {
        return $this->belongsTo(AssetTypeMaster::class, 'type', 'type');
    }

    public function location()
    {
        return $this->belongsTo(LocationMaster::class, 'location', 'unique_location');
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

    public function scopeDecommissioned($query)
    {
        return $query->where('status', 'decommissioned');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByOwnership($query, $ownership)
    {
        return $query->where('ownership', $ownership);
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

    public function isDecommissioned()
    {
        return $this->status === 'decommissioned';
    }

    public function isAllocated()
    {
        return $this->currentAllocation()->exists();
    }
} 