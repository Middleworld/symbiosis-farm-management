<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VegboxPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VegboxPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plans = VegboxPlan::orderBy('sort_order')->orderBy('name')->get();
        
        return view('admin.vegbox-plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.vegbox-plans.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'box_size' => 'required|in:small,medium,large',
            'delivery_frequency' => 'required|in:weekly,fortnightly',
            'default_tokens' => 'required|integer|min:1|max:50',
            'price' => 'required|numeric|min:0',
            'invoice_period' => 'required|integer|min:1',
            'invoice_interval' => 'required|in:day,week,month,year',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $plan = VegboxPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'box_size' => $validated['box_size'],
            'delivery_frequency' => $validated['delivery_frequency'],
            'default_tokens' => $validated['default_tokens'],
            'price' => $validated['price'],
            'currency' => 'GBP',
            'invoice_period' => $validated['invoice_period'],
            'invoice_interval' => $validated['invoice_interval'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.vegbox-plans.index')
            ->with('success', "Plan '{$validated['name']}' created successfully!");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VegboxPlan $vegboxPlan)
    {
        return view('admin.vegbox-plans.edit', compact('vegboxPlan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VegboxPlan $vegboxPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'box_size' => 'required|in:small,medium,large',
            'delivery_frequency' => 'required|in:weekly,fortnightly',
            'default_tokens' => 'required|integer|min:1|max:50',
            'price' => 'required|numeric|min:0',
            'invoice_period' => 'required|integer|min:1',
            'invoice_interval' => 'required|in:day,week,month,year',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $vegboxPlan->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'box_size' => $validated['box_size'],
            'delivery_frequency' => $validated['delivery_frequency'],
            'default_tokens' => $validated['default_tokens'],
            'price' => $validated['price'],
            'invoice_period' => $validated['invoice_period'],
            'invoice_interval' => $validated['invoice_interval'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? $vegboxPlan->sort_order,
        ]);

        return redirect()
            ->route('admin.vegbox-plans.index')
            ->with('success', "Plan '{$validated['name']}' updated successfully!");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VegboxPlan $vegboxPlan)
    {
        $name = $vegboxPlan->name;
        
        // Check if plan has active subscriptions
        // CSA subscriptions don't link to plans directly yet
        $activeSubscriptions = 0;
        
        if ($activeSubscriptions > 0) {
            return redirect()
                ->route('admin.vegbox-plans.index')
                ->with('error', "Cannot delete plan '{$name}' - it has {$activeSubscriptions} active subscription(s). Deactivate it instead.");
        }
        
        $vegboxPlan->delete();
        
        return redirect()
            ->route('admin.vegbox-plans.index')
            ->with('success', "Plan '{$name}' deleted successfully!");
    }
}
