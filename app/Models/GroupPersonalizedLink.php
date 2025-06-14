<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupPersonalizedLink extends Model
{
    protected $fillable = [
        'microsoft_group_name',
        'link_name',
        'link_url',
        'sort_order',
        'replaces_link',
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
     * Scope to get links for specific Microsoft groups
     */
    public function scopeForGroups($query, array $groupNames)
    {
        return $query->whereIn('microsoft_group_name', $groupNames);
    }

    /**
     * Scope to get replacement links (that replace default links)
     */
    public function scopeReplacements($query)
    {
        return $query->whereNotNull('replaces_link');
    }

    /**
     * Scope to get additional links (that don't replace anything)
     */
    public function scopeAdditions($query)
    {
        return $query->whereNull('replaces_link');
    }
} 