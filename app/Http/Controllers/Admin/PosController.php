<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSession;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    /**
     * Display the POS interface
     */
    public function index(): View
    {
        $currentSession = PosSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        $categories = Product::active()
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->sort();

        return view('admin.pos.index', compact('currentSession', 'categories'));
    }

    /**
     * Get products for POS interface
     */
    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::active()->with('supplier');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        // Stock filter
        if ($request->has('in_stock_only') && $request->boolean('in_stock_only')) {
            $query->where('stock_quantity', '>', 0);
        }

        $products = $query->paginate(50);

        return response()->json([
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total()
            ]
        ]);
    }

    /**
     * Create a new order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'order_type' => 'required|in:pos,phone'
        ]);

        try {
            DB::beginTransaction();

            $order = Order::create([
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'order_type' => $request->order_type,
                'staff_id' => Auth::id(),
                'order_status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'order' => $order->load('orderItems.product')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to order
     */
    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            $product = Product::findOrFail($request->product_id);

            // Check stock availability
            if ($product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock_quantity
                ], 400);
            }

            $orderItem = $order->addItem($product, $request->quantity);

            return response()->json([
                'success' => true,
                'order_item' => $orderItem->load('product'),
                'order' => $order->fresh(['orderItems.product'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order item quantity
     */
    public function updateItem(Request $request): JsonResponse
    {
        $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'quantity' => 'required|integer|min:0'
        ]);

        try {
            $orderItem = OrderItem::findOrFail($request->order_item_id);
            $order = $orderItem->order;

            if ($request->quantity == 0) {
                // Remove item
                $orderItem->delete();
                $order->recalculateTotals();
            } else {
                // Check stock availability
                $availableStock = $orderItem->product->stock_quantity + $orderItem->quantity; // Add back current quantity
                if ($availableStock < $request->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock. Available: ' . $availableStock
                    ], 400);
                }

                $orderItem->updateQuantity($request->quantity);
            }

            return response()->json([
                'success' => true,
                'order' => $order->fresh(['orderItems.product'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply discount to order
     */
    public function applyDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percentage'
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            $order->applyDiscount($request->discount_amount, $request->discount_type);

            return response()->json([
                'success' => true,
                'order' => $order->fresh(['orderItems.product'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment and complete order
     */
    public function processPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:cash,card,bank_transfer,other',
            'amount_paid' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($request->order_id);

            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already paid'
                ], 400);
            }

            // Check if payment amount matches order total
            if (abs($request->amount_paid - $order->total_amount) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount does not match order total'
                ], 400);
            }

            // Process payment
            $order->processPayment($request->payment_method, $request->reference);

            // Complete the order
            $order->complete();

            DB::commit();

            return response()->json([
                'success' => true,
                'order' => $order->fresh(['orderItems.product', 'payments']),
                'change_amount' => $request->amount_paid - $order->total_amount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details
     */
    public function getOrder($orderId): JsonResponse
    {
        $order = Order::with(['orderItems.product', 'payments', 'staff'])
            ->findOrFail($orderId);

        return response()->json(['order' => $order]);
    }

    /**
     * Cancel order
     */
    public function cancelOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            $order->cancel();

            return response()->json([
                'success' => true,
                'order' => $order->fresh(['orderItems.product'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start POS session
     */
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0'
        ]);

        try {
            $session = PosSession::startSession(Auth::id(), $request->opening_balance);

            return response()->json([
                'success' => true,
                'session' => $session
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close POS session
     */
    public function closeSession(Request $request): JsonResponse
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $session = PosSession::where('user_id', Auth::id())
                ->where('status', 'open')
                ->firstOrFail();

            $session->closeSession($request->closing_balance, $request->notes);

            return response()->json([
                'success' => true,
                'session' => $session
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current session
     */
    public function getCurrentSession(): JsonResponse
    {
        $session = PosSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->with(['orders' => function($query) {
                $query->completed()->with('orderItems.product');
            }])
            ->first();

        return response()->json(['session' => $session]);
    }

    /**
     * Get sales report for current session
     */
    public function getSessionReport(): JsonResponse
    {
        $session = PosSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active session found'
            ], 404);
        }

        $report = [
            'session' => $session,
            'total_sales' => $session->total_sales,
            'total_payments' => $session->total_payments,
            'order_count' => $session->orders()->completed()->count(),
            'expected_balance' => $session->opening_balance + $session->total_sales
        ];

        return response()->json($report);
    }
}
