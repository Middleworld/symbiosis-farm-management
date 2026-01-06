<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class UnifiedAdminController extends Controller
{
    /**
     * Define which pages are Laravel vs farmOS
     */
    private $routes = [
        // Laravel-native pages
        'dashboard' => ['type' => 'laravel', 'route' => 'admin.dashboard'],
        'succession-planning' => ['type' => 'laravel', 'route' => 'admin.farmos.succession-planning'],
        'deliveries' => ['type' => 'laravel', 'route' => 'admin.deliveries'],
        'products' => ['type' => 'laravel', 'route' => 'admin.products.index'],
        'customers' => ['type' => 'laravel', 'route' => 'admin.customers'],
        
        // farmOS-native pages (embedded or redirected)
        'crop-plans' => ['type' => 'farmos', 'path' => '/admin/content/plan', 'embed' => true],
        'timeline' => ['type' => 'farmos', 'path' => '/farm/timeline', 'embed' => true],
        'map' => ['type' => 'farmos', 'path' => '/dashboard/map', 'embed' => true],
        'assets' => ['type' => 'farmos', 'path' => '/asset', 'embed' => true],
        'logs' => ['type' => 'farmos', 'path' => '/log', 'embed' => true],
        'taxonomy' => ['type' => 'farmos', 'path' => '/admin/structure/taxonomy', 'embed' => false], // Too complex, redirect
    ];
    
    /**
     * Unified admin router - decides Laravel vs farmOS
     */
    public function route(Request $request, $page): RedirectResponse|View
    {
        if (!isset($this->routes[$page])) {
            abort(404, 'Admin page not found');
        }
        
        $config = $this->routes[$page];
        
        if ($config['type'] === 'laravel') {
            // Native Laravel page - standard routing
            return redirect()->route($config['route']);
        }
        
        // farmOS page
        $farmosUrl = config('farmos.url') . $config['path'];
        
        if ($config['embed'] ?? false) {
            // Embed in Laravel layout
            return view('admin.farmos.iframe-wrapper', [
                'farmosUrl' => $farmosUrl,
                'pageTitle' => ucwords(str_replace('-', ' ', $page)),
            ]);
        } else {
            // Direct redirect to farmOS (opens in new context)
            return redirect($farmosUrl);
        }
    }
    
    /**
     * Get navigation menu with mixed Laravel/farmOS pages
     */
    public function getNavigationMenu(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'icon' => 'fa-home',
                'page' => 'dashboard',
                'type' => 'laravel'
            ],
            [
                'label' => 'Planning',
                'icon' => 'fa-calendar',
                'children' => [
                    ['label' => 'Succession Planner', 'page' => 'succession-planning', 'type' => 'laravel'],
                    ['label' => 'Crop Plans (farmOS)', 'page' => 'crop-plans', 'type' => 'farmos'],
                    ['label' => 'Timeline View', 'page' => 'timeline', 'type' => 'farmos'],
                ]
            ],
            [
                'label' => 'Farm Data',
                'icon' => 'fa-seedling',
                'children' => [
                    ['label' => 'Map View', 'page' => 'map', 'type' => 'farmos'],
                    ['label' => 'Assets', 'page' => 'assets', 'type' => 'farmos'],
                    ['label' => 'Logs', 'page' => 'logs', 'type' => 'farmos'],
                ]
            ],
            [
                'label' => 'Deliveries',
                'icon' => 'fa-truck',
                'page' => 'deliveries',
                'type' => 'laravel'
            ],
            [
                'label' => 'Products',
                'icon' => 'fa-shopping-cart',
                'page' => 'products',
                'type' => 'laravel'
            ],
        ];
    }
}
