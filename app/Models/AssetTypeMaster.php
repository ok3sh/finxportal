<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTypeMaster extends Model
{
    protected $table = 'asset_type_master';

    protected $fillable = [
        'type',
        'keyword'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function assets()
    {
        return $this->hasMany(AssetMaster::class, 'type', 'type');
    }
} 