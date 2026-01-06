<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FarmOSQuickFormController extends Controller
{
    /**
     * Available farmOS quick forms
     */
    private $quickForms = [
        'seeding' => [
            'title' => 'Seeding Log',
            'path' => '/log/add/seeding',
            'icon' => 'seedling',
        ],
        'transplanting' => [
            'title' => 'Transplanting Log',
            'path' => '/log/add/transplanting',
            'icon' => 'exchange-alt',
        ],
        'harvest' => [
            'title' => 'Harvest Log',
            'path' => '/log/add/harvest',
            'icon' => 'box',
        ],
        'input' => [
            'title' => 'Input Log',
            'path' => '/log/add/input',
            'icon' => 'plus-circle',
        ],
        'observation' => [
            'title' => 'Observation Log',
            'path' => '/log/add/observation',
            'icon' => 'eye',
        ],
        'activity' => [
            'title' => 'Activity Log',
            'path' => '/log/add/activity',
            'icon' => 'tasks',
        ],
    ];
    
    /**
     * Show quick form selection page
     */
    public function index()
    {
        return view('admin.farmos.quickform-selector', [
            'forms' => $this->quickForms,
        ]);
    }
    
    /**
     * Embed a specific quick form
     */
    public function embed(Request $request, $formType)
    {
        if (!isset($this->quickForms[$formType])) {
            abort(404, 'Quick form not found');
        }
        
        $form = $this->quickForms[$formType];
        $farmosUrl = config('farmos.url') . $form['path'];
        
        // Add query parameters if provided (e.g., pre-fill asset, location)
        $queryParams = $request->except(['_token']);
        if (!empty($queryParams)) {
            $farmosUrl .= '?' . http_build_query($queryParams);
        }
        
        return view('admin.farmos.quickform-embed', [
            'formTitle' => $form['title'],
            'farmosUrl' => $farmosUrl,
            'formType' => $formType,
            'backUrl' => $request->get('back_url', route('admin.farmos.quickforms.index')),
            'backLabel' => 'Quick Forms',
            'redirectAfterSubmit' => $request->get('redirect_after_submit', false),
        ]);
    }
    
    /**
     * Embed with custom farmOS path
     */
    public function embedCustom(Request $request)
    {
        $path = $request->get('path');
        if (!$path) {
            abort(400, 'Path parameter required');
        }
        
        $farmosUrl = config('farmos.url') . '/' . ltrim($path, '/');
        
        return view('admin.farmos.quickform-embed', [
            'formTitle' => $request->get('title', 'farmOS Page'),
            'farmosUrl' => $farmosUrl,
            'backUrl' => $request->get('back_url', route('admin.dashboard')),
        ]);
    }
}
