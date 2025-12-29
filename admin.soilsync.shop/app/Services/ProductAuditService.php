<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\MistralService;

class ProductAuditService
{
    protected $mistral;

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    /**
     * Perform complete product audit
     */
    public function performAudit(): array
    {
        Log::info('Starting product audit');

        try {
            // Gather product data
            $productData = $this->gatherProductData();
            
            // Analyze with AI
            $analysis = $this->mistral->analyzeProducts($productData);
            
            // Generate report
            $report = [
                'timestamp' => now(),
                'data' => $productData,
                'analysis' => $analysis,
                'recommendations' => $this->generateActionPlan($productData, $analysis)
            ];

            Log::info('Product audit completed', [
                'total_products' => $productData['total_products'] ?? 0,
                'subscription_products' => $productData['subscription_products'] ?? 0,
                'issues_found' => $productData['issues_count'] ?? 0
            ]);

            return $report;

        } catch (\Exception $e) {
            Log::error('Product audit failed', ['error' => $e->getMessage()]);
            return [
                'error' => 'Audit failed: ' . $e->getMessage(),
                'timestamp' => now()
            ];
        }
    }

    /**
     * Gather comprehensive product data
     */
    private function gatherProductData(): array
    {
        // Get all products
        $products = DB::connection('wordpress')
            ->select('SELECT ID, post_title, post_date, post_status FROM D6sPMX_posts WHERE post_type = "product"');

        $productDetails = [];
        $subscriptionProducts = 0;
        $issues = [];

        foreach ($products as $product) {
            $details = $this->getProductDetails($product->ID);
            $productDetails[] = $details;

            if ($details['is_subscription']) {
                $subscriptionProducts++;
            }

            // Check for issues
            $productIssues = $this->checkProductIssues($details);
            if (!empty($productIssues)) {
                $issues = array_merge($issues, $productIssues);
            }
        }

        return [
            'total_products' => count($products),
            'subscription_products' => $subscriptionProducts,
            'issues_count' => count($issues),
            'issues' => $issues,
            'products' => $productDetails,
            'audit_date' => now()->toDateTimeString()
        ];
    }

    /**
     * Get detailed product information
     */
    private function getProductDetails(int $productId): array
    {
        try {
            // Get product post data
            $product = DB::connection('wordpress')
                ->select('SELECT post_title, post_date, post_status FROM D6sPMX_posts WHERE ID = ?', [$productId]);

            if (empty($product)) {
                return ['id' => $productId, 'error' => 'Product not found'];
            }

            // Get product meta data
            $meta = DB::connection('wordpress')
                ->select('SELECT meta_key, meta_value FROM D6sPMX_postmeta WHERE post_id = ?', [$productId]);

            $details = [
                'id' => $productId,
                'name' => $product[0]->post_title,
                'created' => $product[0]->post_date,
                'status' => $product[0]->post_status,
                'is_subscription' => false,
                'price' => 0,
                'billing_period' => null,
                'billing_interval' => null,
                'stock_status' => 'instock',
                'categories' => []
            ];

            foreach ($meta as $item) {
                switch ($item->meta_key) {
                    case '_product_type':
                        $details['product_type'] = $item->meta_value;
                        break;
                    case '_price':
                        $details['price'] = (float) $item->meta_value;
                        break;
                    case '_regular_price':
                        $details['regular_price'] = (float) $item->meta_value;
                        break;
                    case '_sale_price':
                        $details['sale_price'] = (float) $item->meta_value;
                        break;
                    case '_stock_status':
                        $details['stock_status'] = $item->meta_value;
                        break;
                    case '_manage_stock':
                        $details['manage_stock'] = $item->meta_value === 'yes';
                        break;
                    case '_stock':
                        $details['stock_quantity'] = (int) $item->meta_value;
                        break;
                    case '_subscription_period':
                        $details['billing_period'] = $item->meta_value;
                        if ($item->meta_value) {
                            $details['is_subscription'] = true;
                        }
                        break;
                    case '_subscription_period_interval':
                        $details['billing_interval'] = (int) $item->meta_value;
                        break;
                }
            }

            // Get categories
            $categories = DB::connection('wordpress')
                ->select('
                    SELECT t.name FROM D6sPMX_term_relationships tr
                    JOIN D6sPMX_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    JOIN D6sPMX_terms t ON tt.term_id = t.term_id
                    WHERE tr.object_id = ? AND tt.taxonomy = "product_cat"
                ', [$productId]);

            $details['categories'] = array_column($categories, 'name');

            return $details;

        } catch (\Exception $e) {
            Log::error('Failed to get product details', ['id' => $productId, 'error' => $e->getMessage()]);
            return ['id' => $productId, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check for product issues
     */
    private function checkProductIssues(array $product): array
    {
        $issues = [];

        // Check if subscription product has proper configuration
        if ($product['is_subscription']) {
            if (empty($product['billing_period'])) {
                $issues[] = [
                    'type' => 'subscription_config',
                    'severity' => 'HIGH',
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'description' => 'Subscription product missing billing period'
                ];
            }

            if (empty($product['billing_interval']) || $product['billing_interval'] < 1) {
                $issues[] = [
                    'type' => 'subscription_config',
                    'severity' => 'HIGH',
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'description' => 'Subscription product has invalid billing interval'
                ];
            }
        }

        // Check stock issues
        if ($product['stock_status'] === 'outofstock') {
            $issues[] = [
                'type' => 'stock',
                'severity' => 'MEDIUM',
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'description' => 'Product is out of stock'
            ];
        }

        // Check pricing issues
        if ($product['price'] <= 0) {
            $issues[] = [
                'type' => 'pricing',
                'severity' => 'CRITICAL',
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'description' => 'Product has no price set'
            ];
        }

        // Check if product is published but has issues
        if ($product['status'] === 'publish') {
            if ($product['is_subscription'] && empty($product['billing_period'])) {
                $issues[] = [
                    'type' => 'published_broken',
                    'severity' => 'CRITICAL',
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'description' => 'Published subscription product is misconfigured'
                ];
            }
        }

        return $issues;
    }

    /**
     * Generate action plan based on analysis
     */
    private function generateActionPlan(array $data, array $analysis): array
    {
        $actions = [];

        $issues = $data['issues'] ?? [];
        $criticalIssues = array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'CRITICAL');
        $highIssues = array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'HIGH');

        if (!empty($criticalIssues)) {
            $actions[] = [
                'priority' => 'CRITICAL',
                'action' => 'Fix critical product issues',
                'description' => "Address " . count($criticalIssues) . " critical product configuration issues",
                'impact' => 'Prevent billing failures and customer issues'
            ];
        }

        if (!empty($highIssues)) {
            $actions[] = [
                'priority' => 'HIGH',
                'action' => 'Review subscription configurations',
                'description' => "Check " . count($highIssues) . " subscription products for proper setup",
                'impact' => 'Ensure subscription billing works correctly'
            ];
        }

        $actions[] = [
            'priority' => 'MEDIUM',
            'action' => 'Monitor product health',
            'description' => 'Set up daily product audits to catch issues early',
            'impact' => 'Prevent revenue loss from product problems'
        ];

        $actions[] = [
            'priority' => 'LOW',
            'action' => 'Review product catalog',
            'description' => 'Regular review of product configurations and pricing',
            'impact' => 'Maintain product data quality'
        ];

        return $actions;
    }

    /**
     * Send alert if critical issues found
     */
    public function sendAlertIfNeeded(array $auditReport): void
    {
        $criticalIssues = 0;
        $issues = $auditReport['data']['issues'] ?? [];
        
        foreach ($issues as $issue) {
            if (($issue['severity'] ?? '') === 'CRITICAL') {
                $criticalIssues++;
            }
        }
        
        if ($criticalIssues > 0) {
            Log::critical('CRITICAL: Product audit found issues', [
                'critical_issues' => $criticalIssues,
                'total_issues' => count($issues),
                'affected_products' => count(array_unique(array_column($issues, 'product_id')))
            ]);

            // Here you could add email/Slack notifications
            // For now, just log it
        }
    }
}
