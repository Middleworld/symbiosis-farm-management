<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmNote;
use App\Models\FarmTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * Display notes list
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'dev');
        $filter = $request->get('filter', 'all');
        
        $query = FarmNote::with(['createdBy', 'task'])
            ->where('type', $type);
        
        // Apply filters
        switch ($filter) {
            case 'my-notes':
                $query->where('created_by', Auth::id());
                break;
            case 'public':
                $query->public();
                break;
            case 'pinned':
                $query->pinned();
                break;
        }
        
        $notes = $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.notes.index', compact('notes', 'type', 'filter'));
    }
    
    /**
     * Show create note form
     */
    public function create(Request $request)
    {
        $type = $request->get('type', 'dev');
        $taskId = $request->get('task_id');
        
        $task = $taskId ? FarmTask::find($taskId) : null;
        
        return view('admin.notes.create', compact('type', 'task'));
    }
    
    /**
     * Store new note
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:farm,dev,general',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
            'is_pinned' => 'boolean',
            'task_id' => 'nullable|exists:farm_tasks,id',
        ]);
        
        $validated['created_by'] = Auth::id();
        $validated['is_public'] = $request->has('is_public');
        $validated['is_pinned'] = $request->has('is_pinned');
        
        $note = FarmNote::create($validated);
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'note' => $note,
            ]);
        }
        
        return redirect()->route('admin.notes.show', $note)
            ->with('success', 'Note created successfully!');
    }
    
    /**
     * Show note details
     */
    public function show(FarmNote $note)
    {
        $note->load(['createdBy', 'task']);
        
        return view('admin.notes.show', compact('note'));
    }
    
    /**
     * Show edit note form
     */
    public function edit(FarmNote $note)
    {
        return view('admin.notes.edit', compact('note'));
    }
    
    /**
     * Update note
     */
    public function update(Request $request, FarmNote $note)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
            'is_pinned' => 'boolean',
        ]);
        
        $validated['is_public'] = $request->has('is_public');
        $validated['is_pinned'] = $request->has('is_pinned');
        
        $note->update($validated);
        
        return redirect()->route('admin.notes.show', $note)
            ->with('success', 'Note updated successfully!');
    }
    
    /**
     * Delete note
     */
    public function destroy(FarmNote $note)
    {
        $note->delete();
        
        return redirect()->route('admin.notes.index')
            ->with('success', 'Note deleted successfully!');
    }
    
    /**
     * Toggle pin status
     */
    public function togglePin(FarmNote $note)
    {
        $note->update(['is_pinned' => !$note->is_pinned]);
        
        return response()->json([
            'success' => true,
            'is_pinned' => $note->is_pinned,
        ]);
    }
}
