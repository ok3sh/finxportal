<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationMaster extends Model
{
    protected $table = 'location_master';

    protected $fillable = [
        'unique_location',
        'total_assets'
    ];

    protected $casts = [
        'total_assets' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function assets()
    {
        return $this->hasMany(AssetMaster::class, 'location', 'unique_location');
    }
} 