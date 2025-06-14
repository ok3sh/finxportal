<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\GroupPersonalizedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LinkController extends Controller
{
    /**
     * Get personalized links for the current user based on their Microsoft groups
     */
    public function index()
    {
        try {
            // Get user's Microsoft groups from session
            $user = Session::get('user');
            $userGroups = [];
            
            if ($user && isset($user['authenticated']) && $user['authenticated']) {
                // Extract group names from Microsoft Graph session data
                $userGraphGroups = $user['groups']['value'] ?? $user['groups'] ?? [];
                foreach ($userGraphGroups as $group) {
                    if (isset($group['displayName'])) {
                        $userGroups[] = $group['displayName'];
                    }
                }
            }

            Log::info('LinkController: Processing links for user groups', [
                'user_groups' => $userGroups,
                'total_groups' => count($userGroups)
            ]);

            // Get default links
            $defaultLinks = Link::active()->get();
            
            Log::info('LinkController: Default links found', [
                'default_links_count' => $defaultLinks->count(),
                'default_link_names' => $defaultLinks->pluck('name')->toArray()
            ]);
            
            // Get personalized links for user's groups
            $personalizedLinks = [];
            $replacements = [];
            
            if (!empty($userGroups)) {
                // Get replacement links (that replace default links)
                $replacementLinks = GroupPersonalizedLink::active()
                    ->forGroups($userGroups)
                    ->replacements()
                    ->get();
                
                Log::info('LinkController: Searching for replacement links', [
                    'user_groups' => $userGroups,
                    'replacement_links_found' => $replacementLinks->count(),
                    'replacement_details' => $replacementLinks->map(function($link) {
                        return [
                            'group' => $link->microsoft_group_name,
                            'replaces' => $link->replaces_link,
                            'with' => $link->link_name
                        ];
                    })->toArray()
                ]);
                
                // Build replacement map - simplified without visual attributes
                foreach ($replacementLinks as $link) {
                    $replacements[$link->replaces_link] = [
                        'id' => $link->id,
                        'name' => $link->link_name,
                        'url' => $link->link_url,
                        'sort_order' => $link->sort_order,
                        'is_personalized' => true,
                        'group_access' => $link->microsoft_group_name
                    ];
                }
                
                Log::info('LinkController: Built replacement map', [
                    'replacements' => array_keys($replacements),
                    'replacement_count' => count($replacements)
                ]);
                
                // Get additional links (that don't replace anything)
                $additionalLinks = GroupPersonalizedLink::active()
                    ->forGroups($userGroups)
                    ->additions()
                    ->get();
                
                // Add additional links - simplified
                foreach ($additionalLinks as $link) {
                    $personalizedLinks[] = [
                        'id' => $link->id,
                        'name' => $link->link_name,
                        'url' => $link->link_url,
                        'sort_order' => $link->sort_order,
                        'is_personalized' => true,
                        'group_access' => $link->microsoft_group_name
                    ];
                }
            }

            // Process default links and apply replacements
            $finalLinks = [];
            foreach ($defaultLinks as $link) {
                $linkName = strtolower($link->name);
                
                // Check if this link should be replaced
                if (isset($replacements[$linkName])) {
                    Log::info('LinkController: Replacing link', [
                        'original' => $link->name,
                        'replacement' => $replacements[$linkName]['name'],
                        'group' => $replacements[$linkName]['group_access']
                    ]);
                    $finalLinks[] = $replacements[$linkName];
                } else {
                    // Keep default link
                    $finalLinks[] = [
                        'id' => $link->id,
                        'name' => $link->name,
                        'url' => $link->url,
                        'logo' => $link->logo,
                        'background_color' => $link->background_color,
                        'sort_order' => $link->sort_order,
                        'is_personalized' => false
                    ];
                }
            }

            // Add personalized additional links
            $finalLinks = array_merge($finalLinks, $personalizedLinks);

            // Sort by sort_order
            usort($finalLinks, function($a, $b) {
                return $a['sort_order'] - $b['sort_order'];
            });

            Log::info('LinkController: Returning personalized links', [
                'total_links' => count($finalLinks),
                'personalized_count' => count(array_filter($finalLinks, function($link) {
                    return $link['is_personalized'] ?? false;
                })),
                'user_groups' => $userGroups,
                'replacements_found' => array_keys($replacements)
            ]);

            return response()->json($finalLinks);

        } catch (\Exception $e) {
            Log::error('LinkController: Error processing personalized links', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            Log::warning('LinkController: Returning fallback Keka/Zoho links due to error');
            
            // Return fallback data in case of error
            return response()->json($this->getFallbackLinks());
        }
    }

    /**
     * Get RightPanel link (handles "Outlook" replacement for RightPanel.jsx)
     */
    public function getRightPanelLink()
    {
        try {
            // Default RightPanel link
            $defaultLink = [
                'name' => 'Outlook',
                'url' => 'https://outlook.office.com'
            ];

            // Get user's Microsoft groups from session
            $user = Session::get('user');
            $userGroups = [];
            
            if ($user && isset($user['authenticated']) && $user['authenticated']) {
                $userGraphGroups = $user['groups']['value'] ?? $user['groups'] ?? [];
                foreach ($userGraphGroups as $group) {
                    if (isset($group['displayName'])) {
                        $userGroups[] = $group['displayName'];
                    }
                }
            }

            Log::info('LinkController@getRightPanelLink: Processing for user groups', [
                'user_groups' => $userGroups,
                'total_groups' => count($userGroups)
            ]);

            // Check for personalized replacement for "outlook"
            if (!empty($userGroups)) {
                $personalizedLink = GroupPersonalizedLink::active()
                    ->forGroups($userGroups)
                    ->where('replaces_link', 'outlook')
                    ->first();

                if ($personalizedLink) {
                    Log::info('LinkController@getRightPanelLink: Found replacement', [
                        'group' => $personalizedLink->microsoft_group_name,
                        'replaces_outlook_with' => $personalizedLink->link_name,
                        'url' => $personalizedLink->link_url
                    ]);

                    return response()->json([
                        'name' => $personalizedLink->link_name,
                        'url' => $personalizedLink->link_url,
                        'is_personalized' => true,
                        'group_access' => $personalizedLink->microsoft_group_name
                    ]);
                }
            }

            Log::info('LinkController@getRightPanelLink: No replacement found, using default Outlook');

            return response()->json([
                'name' => $defaultLink['name'],
                'url' => $defaultLink['url'],
                'is_personalized' => false
            ]);

        } catch (\Exception $e) {
            Log::error('LinkController@getRightPanelLink: Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return default Outlook on error
            return response()->json([
                'name' => 'Outlook',
                'url' => 'https://outlook.office.com',
                'is_personalized' => false
            ]);
        }
    }

    /**
     * Get user's access control information for debugging
     */
    public function getAccessInfo()
    {
        try {
            $user = Session::get('user');
            $userGroups = [];
            
            if ($user && isset($user['authenticated']) && $user['authenticated']) {
                $userGraphGroups = $user['groups']['value'] ?? $user['groups'] ?? [];
                foreach ($userGraphGroups as $group) {
                    if (isset($group['displayName'])) {
                        $userGroups[] = $group['displayName'];
                    }
                }
            }

            // Get available personalized links for user's groups
            $availablePersonalizedLinks = [];
            if (!empty($userGroups)) {
                $availablePersonalizedLinks = GroupPersonalizedLink::active()
                    ->forGroups($userGroups)
                    ->get()
                    ->map(function($link) {
                        return [
                            'group' => $link->microsoft_group_name,
                            'link_name' => $link->link_name,
                            'replaces' => $link->replaces_link,
                            'type' => $link->replaces_link ? 'replacement' : 'additional'
                        ];
                    });
            }

            return response()->json([
                'user_groups' => $userGroups,
                'available_personalized_links' => $availablePersonalizedLinks,
                'total_groups' => count($userGroups),
                'total_personalized_links' => count($availablePersonalizedLinks)
            ]);

        } catch (\Exception $e) {
            Log::error('LinkController: Error getting access info', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to get access information',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fallback links in case of database issues
     */
    private function getFallbackLinks()
    {
        return [
            [
                'id' => 1,
                'name' => 'Keka',
                'url' => 'https://keka.com',
                'logo' => '/assets/keka.png',
                'background_color' => '#115948',
                'sort_order' => 1,
                'is_personalized' => false
            ],
            [
                'id' => 2,
                'name' => 'Zoho',
                'url' => 'https://zoho.com',
                'logo' => '/assets/zoho.png',
                'background_color' => '#115948',
                'sort_order' => 2,
                'is_personalized' => false
            ]
        ];
    }
} 