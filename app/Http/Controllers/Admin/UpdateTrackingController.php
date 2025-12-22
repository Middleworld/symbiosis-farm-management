<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UpdateTracking;
use App\Services\UpdateTrackingService;
use Illuminate\Http\Request;

class UpdateTrackingController extends Controller
{
    /**
     * Display a listing of updates
     */
    public function index(Request $request)
    {
        $customerId = $request->get('customer_id');
        $environment = $request->get('environment', 'production');
        
        $updates = UpdateTrackingService::getUpdateHistory($customerId, $environment);
        
        // Get unique customers and environments for filters
        $customers = UpdateTracking::distinct('customer_id')->whereNotNull('customer_id')->pluck('customer_id');
        $environments = UpdateTracking::distinct('environment')->pluck('environment');
        
        return view('admin.updates.index', compact('updates', 'customers', 'environments', 'customerId', 'environment'));
    }

    /**
     * Show the form for creating a new update
     */
    public function create()
    {
        return view('admin.updates.create');
    }

    /**
     * Store a newly created update
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'files_changed' => 'nullable|string',
            'customer_id' => 'nullable|string',
            'environment' => 'required|string|in:production,demo,staging'
        ]);

        $filesChanged = [];
        if ($validated['files_changed']) {
            $filesChanged = array_map('trim', explode("\n", $validated['files_changed']));
        }

        UpdateTrackingService::logCodeChange(
            $validated['version'],
            $validated['title'],
            $validated['description'],
            $filesChanged,
            $validated['customer_id'],
            $validated['environment']
        );

        return redirect()->route('admin.updates.index')
            ->with('success', 'Update logged successfully');
    }

    /**
     * Display the specified update
     */
    public function show(UpdateTracking $update)
    {
        return view('admin.updates.show', compact('update'));
    }

    /**
     * Generate update script for a customer
     */
    public function generateScript(Request $request)
    {
        $customerId = $request->get('customer_id');
        $targetVersion = $request->get('target_version');
        
        if (!$customerId || !$targetVersion) {
            return back()->with('error', 'Customer ID and target version are required');
        }
        
        $script = UpdateTrackingService::generateUpdateScript($customerId, $targetVersion);
        
        return response($script, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="update-script-' . $customerId . '-v' . $targetVersion . '.sh"'
        ]);
    }
}
