<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordPressUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard
     */
    public function index()
    {
        $dateRange = request('range', '30'); // Default to last 30 days
        $startDate = Carbon::now()->subDays($dateRange);
        
        // Get key performance indicators
        $kpis = $this->getKPIs($startDate);
        
        // Get AI usage stats
        $aiStats = $this->getAIStats($startDate);
        
        // Get growth trends
        $trends = $this->getTrends($startDate);
        
        // Get farm productivity metrics
        $productivity = $this->getProductivityMetrics($startDate);
        
        return view('admin.analytics.index', compact(
            'kpis',
            'aiStats',
            'trends',
            'productivity',
            'dateRange'
        ));
    }
    
    /**
     * Get key performance indicators
     */
    private function getKPIs($startDate)
    {
        // Total customers from WordPress/WooCommerce (all time)
        // Using subquery to get customer IDs first
        $totalCustomers = WordPressUser::whereHas('meta', function ($query) {
            $prefix = config('database.connections.wordpress.prefix');
            $query->where('meta_key', $prefix . 'capabilities')
                  ->where('meta_value', 'LIKE', '%customer%');
        })->count();
        
        // New customers in date range
        $activeCustomers = WordPressUser::whereHas('meta', function ($query) {
            $prefix = config('database.connections.wordpress.prefix');
            $query->where('meta_key', $prefix . 'capabilities')
                  ->where('meta_value', 'LIKE', '%customer%');
        })->where('user_registered', '>=', $startDate)
          ->count();
            
        // Completion rate
        $totalTasks = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->count();
            
        $completedTasks = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->count();
            
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
        
        // Harvest efficiency (total quantity / weeks)
        $totalHarvests = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->count();
            
        $totalQuantity = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->sum('quantity');
            
        $weeks = ceil($startDate->diffInDays(Carbon::now()) / 7);
        $harvestEfficiency = $weeks > 0 ? round($totalQuantity / $weeks, 2) : 0;
        
        // AI usage metrics
        $aiRequests = DB::table('ai_access_logs')
            ->where('created_at', '>=', $startDate)
            ->count();
            
        return [
            'customer_retention_rate' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0,
            'task_completion_rate' => $completionRate,
            'completed_tasks' => $completedTasks,
            'total_tasks' => $totalTasks,
            'harvest_efficiency' => $harvestEfficiency,
            'ai_requests' => $aiRequests,
        ];
    }
    
    /**
     * Get AI usage statistics
     */
    private function getAIStats($startDate)
    {
        $totalRequests = DB::table('ai_access_logs')
            ->where('created_at', '>=', $startDate)
            ->count();
            
        // Group by service (e.g., anthropic, openai, etc.)
        $requestsByType = DB::table('ai_access_logs')
            ->where('created_at', '>=', $startDate)
            ->select('service as request_type', DB::raw('COUNT(*) as count'))
            ->groupBy('service')
            ->get();
            
        $requestsByDay = DB::table('ai_access_logs')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $avgResponseTime = DB::table('ai_access_logs')
            ->where('created_at', '>=', $startDate)
            ->avg('duration_ms');
        
        // Convert milliseconds to seconds
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime / 1000, 3) : 0;
            
        // Top users by AI requests (handling null user_id)
        $topUsers = DB::table('ai_access_logs')
            ->where('ai_access_logs.created_at', '>=', $startDate)
            ->whereNotNull('ai_access_logs.user_id')
            ->leftJoin('users', 'ai_access_logs.user_id', '=', 'users.id')
            ->select(
                DB::raw('COALESCE(users.name, CONCAT("User #", ai_access_logs.user_id)) as name'),
                DB::raw('COUNT(*) as request_count')
            )
            ->groupBy('ai_access_logs.user_id', 'users.name')
            ->orderByDesc('request_count')
            ->limit(10)
            ->get();
        
        // Count anonymous/system requests
        $anonymousRequests = DB::table('ai_access_logs')
            ->where('created_at', '>=', $startDate)
            ->whereNull('user_id')
            ->count();
            
        return [
            'total_requests' => $totalRequests,
            'by_type' => $requestsByType,
            'by_day' => $requestsByDay,
            'avg_response_time' => round($avgResponseTime ?? 0, 2),
            'top_users' => $topUsers,
            'anonymous_requests' => $anonymousRequests,
        ];
    }
    
    /**
     * Get growth trends
     */
    private function getTrends($startDate)
    {
        // Customer growth from WordPress user registrations
        // This pulls ALL historical customer data from WooCommerce/WordPress
        $prefix = config('database.connections.wordpress.prefix');
        $customerGrowth = WordPressUser::selectRaw('DATE(user_registered) as date, COUNT(*) as count')
            ->whereHas('meta', function ($query) use ($prefix) {
                $query->where('meta_key', $prefix . 'capabilities')
                      ->where('meta_value', 'LIKE', '%customer%');
            })
            ->where('user_registered', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Task creation trend
        $taskTrends = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Harvest trend (using quantity instead of weight_kg)
        $harvestTrends = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->select(DB::raw('DATE(harvest_date) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Delivery completion trend
        $deliveryTrends = DB::table('delivery_completions')
            ->where('completed_at', '>=', $startDate)
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return [
            'customer_growth' => $customerGrowth,
            'task_trends' => $taskTrends,
            'harvest_trends' => $harvestTrends,
            'delivery_trends' => $deliveryTrends,
        ];
    }
    
    /**
     * Get farm productivity metrics
     */
    private function getProductivityMetrics($startDate)
    {
        // Crop diversity (number of different crop names)
        $cropDiversity = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->distinct('crop_name')
            ->count('crop_name');
            
        // Most productive crops
        $topVarieties = DB::table('harvest_logs')
            ->where('harvest_date', '>=', $startDate)
            ->select(
                'crop_name as variety_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('COUNT(*) as harvest_count')
            )
            ->groupBy('crop_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();
            
        // Task category distribution (using type and dev_category)
        $taskCategories = DB::table('farm_tasks')
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
            
        // Average task completion time
        $avgCompletionTime = DB::table('farm_tasks')
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(DAY, created_at, completed_at)) as avg_days'))
            ->first();
            
        return [
            'crop_diversity' => $cropDiversity,
            'top_varieties' => $topVarieties,
            'task_categories' => $taskCategories,
            'avg_completion_time' => round($avgCompletionTime->avg_days ?? 0, 1),
        ];
    }
    
    /**
     * Get real-time metrics
     */
    public function realtime()
    {
        $metrics = [
            'active_users' => DB::table('sessions')
                ->where('last_activity', '>=', Carbon::now()->subMinutes(5)->timestamp)
                ->count(),
            'tasks_today' => DB::table('farm_tasks')
                ->whereDate('created_at', Carbon::today())
                ->count(),
            'harvests_today' => DB::table('harvest_logs')
                ->whereDate('harvest_date', Carbon::today())
                ->count(),
            'deliveries_today' => DB::table('delivery_completions')
                ->whereDate('completed_at', Carbon::today())
                ->count(),
            'ai_requests_today' => DB::table('ai_access_logs')
                ->whereDate('created_at', Carbon::today())
                ->count(),
        ];
        
        return response()->json($metrics);
    }
}
