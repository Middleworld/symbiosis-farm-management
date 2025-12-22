<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Display the reports dashboard
     */
    public function index()
    {
        $dateRange = request('range', '30'); // Default to last 30 days
        $startDate = Carbon::now()->subDays($dateRange);
        
        // Get delivery completion stats
        $deliveryStats = $this->getDeliveryStats($startDate);
        
        // Get task completion stats
        $taskStats = $this->getTaskStats($startDate);
        
        // Get harvest stats
        $harvestStats = $this->getHarvestStats($startDate);
        
        // Get customer engagement stats
        $customerStats = $this->getCustomerStats($startDate);
        
        return view('admin.reports.index', compact(
            'deliveryStats',
            'taskStats',
            'harvestStats',
            'customerStats',
            'dateRange'
        ));
    }
    
    /**
     * Get delivery completion statistics
     */
    private function getDeliveryStats($startDate)
    {
        $totalDeliveries = DB::table('delivery_completions')
            ->where('completed_at', '>=', $startDate)
            ->count();
            
        $averageDeliveryTime = DB::table('delivery_completions')
            ->where('completed_at', '>=', $startDate)
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_time'))
            ->first();
            
        // Group by week (extracted from delivery_date)
        $deliveriesByWeek = DB::table('delivery_completions')
            ->where('completed_at', '>=', $startDate)
            ->select(
                DB::raw('WEEK(delivery_date) as week_number'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('week_number')
            ->orderBy('week_number')
            ->get();
            
        // Group by customer email
        $deliveriesByCustomer = DB::table('delivery_completions')
            ->where('completed_at', '>=', $startDate)
            ->whereNotNull('customer_email')
            ->select(
                DB::raw('COALESCE(customer_name, customer_email) as name'),
                DB::raw('COUNT(*) as deliveries')
            )
            ->groupBy('customer_email', 'customer_name')
            ->orderByDesc('deliveries')
            ->limit(10)
            ->get();
            
        return [
            'total' => $totalDeliveries,
            'average_time' => round($averageDeliveryTime->avg_time ?? 0, 1),
            'by_week' => $deliveriesByWeek,
            'top_customers' => $deliveriesByCustomer,
        ];
    }
    
    /**
     * Get task completion statistics
     */
    private function getTaskStats($startDate)
    {
        $totalTasks = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->count();
            
        $completedTasks = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->count();
            
        $overdueTasks = DB::table('farm_tasks')
            ->where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'completed')
            ->count();
            
        $tasksByPriority = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();
            
        // Group by type (farm/dev) and for dev tasks, show dev_category
        $tasksByCategory = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('CASE 
                    WHEN type = "dev" AND dev_category IS NOT NULL THEN CONCAT("dev: ", dev_category)
                    WHEN type = "farm" THEN "farm"
                    ELSE type
                END as category'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category')
            ->get();
            
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
        
        return [
            'total' => $totalTasks,
            'completed' => $completedTasks,
            'overdue' => $overdueTasks,
            'completion_rate' => $completionRate,
            'by_priority' => $tasksByPriority,
            'by_category' => $tasksByCategory,
        ];
    }
    
    /**
     * Get harvest statistics
     */
    private function getHarvestStats($startDate)
    {
        $totalHarvests = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->count();
            
        // Sum quantity (which is the weight/count in various units)
        $totalQuantity = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->sum('quantity');
            
        // Group by crop_name (since there's no plant_variety_id)
        $harvestsByCrop = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->select(
                'crop_name',
                DB::raw('COALESCE(crop_type, "Unknown") as crop_type'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('COUNT(*) as count'),
                DB::raw('GROUP_CONCAT(DISTINCT units) as units')
            )
            ->groupBy('crop_name', 'crop_type')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();
            
        $harvestsByMonth = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(harvest_date, "%Y-%m") as month'),
                DB::raw('SUM(quantity) as total_quantity')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        return [
            'total' => $totalHarvests,
            'total_weight' => round($totalQuantity, 2),
            'by_variety' => $harvestsByCrop,
            'by_month' => $harvestsByMonth,
        ];
    }
    
    /**
     * Get customer engagement statistics
     */
    private function getCustomerStats($startDate)
    {
        // Count unique customer emails from week assignments
        $activeCustomers = DB::table('customer_week_assignments')
            ->where('created_at', '>=', $startDate)
            ->distinct('customer_email')
            ->count('customer_email');
            
        $totalOrders = DB::table('customer_week_assignments')
            ->where('created_at', '>=', $startDate)
            ->count();
            
        $customerNotes = DB::table('customer_notes')
            ->where('created_at', '>=', $startDate)
            ->count();
            
        // Get top customers by email
        $topCustomers = DB::table('customer_week_assignments')
            ->where('created_at', '>=', $startDate)
            ->select(
                'customer_email as name',
                DB::raw('COUNT(*) as weeks')
            )
            ->groupBy('customer_email')
            ->orderByDesc('weeks')
            ->limit(10)
            ->get();
            
        return [
            'active_customers' => $activeCustomers,
            'total_orders' => $totalOrders,
            'notes_added' => $customerNotes,
            'top_customers' => $topCustomers,
        ];
    }
    
    /**
     * Export reports data
     */
    public function export(Request $request)
    {
        $type = $request->input('type', 'deliveries');
        $dateRange = $request->input('range', '30');
        $startDate = Carbon::now()->subDays($dateRange);
        
        switch ($type) {
            case 'deliveries':
                return $this->exportDeliveries($startDate);
            case 'tasks':
                return $this->exportTasks($startDate);
            case 'harvests':
                return $this->exportHarvests($startDate);
            default:
                return redirect()->back()->with('error', 'Invalid export type');
        }
    }
    
    /**
     * Export delivery data to CSV
     */
    private function exportDeliveries($startDate)
    {
        $deliveries = DB::table('delivery_completions')
            ->where('completed_at', '>=', $startDate)
            ->select(
                DB::raw('COALESCE(customer_name, customer_email) as customer_name'),
                'delivery_date',
                'type',
                'completed_at',
                'completed_by',
                'notes'
            )
            ->orderBy('completed_at', 'desc')
            ->get();
            
        $filename = 'deliveries_export_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($deliveries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Customer Name', 'Delivery Date', 'Type', 'Completed At', 'Completed By', 'Notes']);
            
            foreach ($deliveries as $delivery) {
                fputcsv($file, [
                    $delivery->customer_name,
                    $delivery->delivery_date,
                    $delivery->type,
                    $delivery->completed_at,
                    $delivery->completed_by,
                    $delivery->notes
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export task data to CSV
     */
    private function exportTasks($startDate)
    {
        $tasks = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->select(
                'title',
                'description',
                'type',
                'dev_category',
                'priority',
                'status',
                'due_date',
                'assigned_to',
                'created_by',
                'created_at'
            )
            ->orderBy('created_at', 'desc')
            ->get();
            
        $filename = 'tasks_export_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Title', 'Description', 'Type', 'Dev Category', 'Priority', 'Status', 'Due Date', 'Assigned To', 'Created By', 'Created At']);
            
            foreach ($tasks as $task) {
                fputcsv($file, [
                    $task->title,
                    $task->description,
                    $task->type,
                    $task->dev_category,
                    $task->priority,
                    $task->status,
                    $task->due_date,
                    $task->assigned_to,
                    $task->created_by,
                    $task->created_at
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export harvest data to CSV
     */
    private function exportHarvests($startDate)
    {
        $harvests = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->select(
                'crop_name',
                'crop_type',
                'harvest_date',
                'quantity',
                'units',
                'measure',
                'location',
                'notes',
                'status'
            )
            ->orderBy('harvest_date', 'desc')
            ->get();
            
        $filename = 'harvests_export_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($harvests) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Crop Name', 'Crop Type', 'Harvest Date', 'Quantity', 'Units', 'Measure', 'Location', 'Notes', 'Status']);
            
            foreach ($harvests as $harvest) {
                fputcsv($file, [
                    $harvest->crop_name,
                    $harvest->crop_type,
                    $harvest->harvest_date,
                    $harvest->quantity,
                    $harvest->units,
                    $harvest->measure,
                    $harvest->location,
                    $harvest->notes,
                    $harvest->status
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
