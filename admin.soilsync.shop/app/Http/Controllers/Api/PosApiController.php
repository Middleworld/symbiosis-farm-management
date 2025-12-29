<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\PosSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PosApiController extends Controller
{
    /**
     * Get products with search and filtering
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::active();

        // Search by name, SKU, or barcode
        if ($request->has('q') && !empty($request->q)) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by stock status
        if ($request->has('in_stock') && $request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        $products = $query->paginate(20);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total()
            ]
        ]);
    }

    /**
     * Get product categories
     */
    public function categories(): JsonResponse
    {
        $categories = Product::active()
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json(['categories' => $categories]);
    }

    /**
     * Get single product details
     */
    public function product($id): JsonResponse
    {
        $product = Product::active()->findOrFail($id);

        return response()->json(['product' => $product]);
    }

    /**
     * Create new order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'order_type' => 'required|in:pos,phone,online'
        ]);

        $order = Order::create([
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'order_type' => $request->order_type,
            'staff_id' => auth()->id(),
            'order_status' => 'pending'
        ]);

        return response()->json([
            'order' => $order->load('orderItems.product')
        ], 201);
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
     * Add item to order
     */
    public function addToOrder(Request $request, $orderId): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $order = Order::findOrFail($orderId);
        $product = Product::findOrFail($request->product_id);

        // Check stock
        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'error' => 'Insufficient stock',
                'available' => $product->stock_quantity
            ], 400);
        }

        $orderItem = $order->addItem($product, $request->quantity);

        return response()->json([
            'order_item' => $orderItem->load('product'),
            'order' => $order->fresh(['orderItems.product'])
        ]);
    }

    /**
     * Update order item
     */
    public function updateOrderItem(Request $request, $orderId, $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $order = Order::findOrFail($orderId);
        $orderItem = $order->orderItems()->findOrFail($itemId);

        if ($request->quantity == 0) {
            $orderItem->delete();
        } else {
            // Check stock
            $availableStock = $orderItem->product->stock_quantity + $orderItem->quantity;
            if ($availableStock < $request->quantity) {
                return response()->json([
                    'error' => 'Insufficient stock',
                    'available' => $availableStock
                ], 400);
            }

            $orderItem->updateQuantity($request->quantity);
        }

        $order->recalculateTotals();

        return response()->json([
            'order' => $order->fresh(['orderItems.product'])
        ]);
    }

    /**
     * Apply discount to order
     */
    public function applyDiscount(Request $request, $orderId): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:fixed,percentage'
        ]);

        $order = Order::findOrFail($orderId);
        $order->applyDiscount($request->amount, $request->type);

        return response()->json([
            'order' => $order->fresh(['orderItems.product'])
        ]);
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, $orderId): JsonResponse
    {
        $request->validate([
            'method' => 'required|in:cash,card,bank_transfer,other',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:255'
        ]);

        $order = Order::findOrFail($orderId);

        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Order already paid'], 400);
        }

        $order->processPayment($request->method, $request->reference);
        $order->complete();

        return response()->json([
            'order' => $order->fresh(['orderItems.product', 'payments']),
            'change' => $request->amount - $order->total_amount
        ]);
    }

    /**
     * Cancel order
     */
    public function cancelOrder($orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);
        $order->cancel();

        return response()->json([
            'order' => $order->fresh(['orderItems.product'])
        ]);
    }

    /**
     * Get POS session info
     */
    public function session(): JsonResponse
    {
        $session = PosSession::where('user_id', auth()->id())
            ->where('status', 'open')
            ->with(['orders' => function($query) {
                $query->completed()->with('orderItems.product');
            }])
            ->first();

        if (!$session) {
            return response()->json(['error' => 'No active session'], 404);
        }

        return response()->json(['session' => $session]);
    }

    /**
     * Start new POS session
     */
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0'
        ]);

        $session = PosSession::startSession(auth()->id(), $request->opening_balance);

        return response()->json(['session' => $session], 201);
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

        $session = PosSession::where('user_id', auth()->id())
            ->where('status', 'open')
            ->firstOrFail();

        $session->closeSession($request->closing_balance, $request->notes);

        return response()->json(['session' => $session]);
    }

    /**
     * Get sales summary
     */
    public function salesSummary(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        $summary = [
            'date' => $date,
            'total_sales' => Order::completed()
                ->whereDate('created_at', $date)
                ->sum('total_amount'),
            'order_count' => Order::completed()
                ->whereDate('created_at', $date)
                ->count(),
            'average_order' => Order::completed()
                ->whereDate('created_at', $date)
                ->avg('total_amount') ?? 0,
            'payment_methods' => Order::completed()
                ->whereDate('created_at', $date)
                ->selectRaw('payment_method, COUNT(*) as count, SUM(total_amount) as total')
                ->groupBy('payment_method')
                ->get()
        ];

        return response()->json($summary);
    }
}
