<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $fillable = [
        'name',
        'url',
        'logo_path',
        'logo_url',
        'background_color',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Scope to get only active links ordered by sort_order
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get the logo URL - either from uploaded file or external URL
     */
    public function getLogoAttribute()
    {
        if ($this->logo_url) {
            return $this->logo_url;
        }
        
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }
        
        return null;
    }
} 