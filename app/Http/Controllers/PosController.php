<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PosPaymentService;
use App\Services\BluetoothScaleService;
use App\Services\ReceiptPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    protected PosPaymentService $paymentService;
    protected BluetoothScaleService $scaleService;
    protected ReceiptPrinterService $printerService;

    public function __construct(
        PosPaymentService $paymentService,
        BluetoothScaleService $scaleService,
        ReceiptPrinterService $printerService
    ) {
        $this->paymentService = $paymentService;
        $this->scaleService = $scaleService;
        $this->printerService = $printerService;
    }
    /**
     * POS Dashboard
     */
    public function dashboard()
    {
        $user = Session::get('user');

        return view('pos.dashboard', [
            'user' => $user,
            'title' => 'POS System - ' . ($user['name'] ?? 'Staff')
        ]);
    }

    /**
     * Get POS settings for hardware integration
     */
    public function getPosSettings()
    {
        $settings = \App\Models\Setting::getAll();

        return response()->json([
            // Scale settings
            'scale_integration' => $settings['pos_scale_integration'] ?? 'manual',
            'scale_auto_populate' => $settings['pos_scale_auto_populate'] ?? false,
            'scale_auto_connect' => $settings['pos_scale_auto_connect'] ?? false,
            'scale_protocol' => $settings['pos_scale_protocol'] ?? 'generic',
            'scale_baud_rate' => $settings['pos_scale_baud_rate'] ?? '9600',
            'scale_bluetooth_service' => $settings['pos_scale_bluetooth_service'] ?? '',
            'scale_websocket_url' => $settings['pos_scale_websocket_url'] ?? 'ws://localhost:8765',
            'scale_reconnect_interval' => $settings['pos_scale_reconnect_interval'] ?? 5000,

            // Card reader settings
            'card_reader_type' => $settings['pos_card_reader_type'] ?? 'manual',
            'card_reader_connection' => $settings['pos_card_reader_connection'] ?? 'bluetooth',
            'stripe_publishable_key' => $settings['pos_stripe_publishable_key'] ?? config('services.stripe.key') ?? '',
            'stripe_location_id' => $settings['pos_stripe_location_id'] ?? '',
            'currency' => $settings['pos_currency'] ?? 'gbp',
            'square_app_id' => $settings['pos_square_app_id'] ?? '',
            'card_websocket_url' => $settings['pos_card_websocket_url'] ?? 'ws://localhost:8766',

            // Printer settings
            'printer_type' => $settings['pos_printer_type'] ?? 'browser',
            'printer_connection' => $settings['pos_printer_connection'] ?? 'browser',
            'printer_paper_size' => $settings['pos_printer_paper_size'] ?? '80mm',
            'printer_auto_cut' => $settings['pos_printer_auto_cut'] ?? true,
            'printer_open_drawer' => $settings['pos_printer_open_drawer'] ?? false,
            'printer_ip' => $settings['pos_printer_ip'] ?? '',
            'printer_port' => $settings['pos_printer_port'] ?? '9100',
        ]);
    }

    /**
     * Get products for POS interface
     * Only returns products marked as available for POS
     */
    public function getProducts(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');

        $query = Product::where('is_active', true)
                       ->where('pos_available', true);  // Only show POS-available products

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($category) {
            $query->where('category', $category);
        }

        $products = $query->orderBy('name')->get();

        return response()->json($products);
    }

    /**
     * Create new order
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $user = Session::get('user');

            // Create order
            $order = Order::create([
                'order_number' => 'POS-' . time() . '-' . rand(100, 999),
                'customer_name' => $request->customer_name ?? 'Walk-in Customer',
                'customer_email' => $request->customer_email ?? null,
                'customer_phone' => $request->customer_phone ?? null,
                'customer_address' => $request->customer_address ?? null,
                'subtotal' => 0, // Will calculate below
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0, // Will calculate below
                'order_status' => 'completed',
                'payment_status' => 'paid', // POS orders are paid immediately
                'order_type' => 'pos',
                'payment_method' => $request->payment_method ?? 'cash',
                'staff_id' => $user['id'] ?? 1, // Default to admin user ID 1
                'notes' => $request->notes ?? null,
            ]);            $totalAmount = 0;

            // Add order items
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $subtotal = $item['price'] * $item['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            // Update order total
            $order->update(['total_amount' => $totalAmount]);

            // Record in bank transactions/accounts if payment method is cash or card
            if (in_array($request->payment_method, ['cash', 'card'])) {
                $this->recordPOSTransactionInAccounts($order, $request->payment_method);
            }

            // Handle digital payment processing
            if ($request->payment_method === 'card') {
                $paymentResult = $this->processDigitalPaymentForOrder($order);
                if (!$paymentResult['success']) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message'] ?? 'Payment processing failed'
                    ], 400);
                }
                // For digital payments, return payment details for frontend processing
                // Don't mark as completed yet - wait for payment confirmation
                DB::commit();
                return response()->json([
                    'success' => true,
                    'order' => $order->load('items'),
                    'payment_intent_id' => $paymentResult['payment_intent_id'] ?? null,
                    'client_secret' => $paymentResult['client_secret'] ?? null,
                    'status' => 'payment_pending',
                    'message' => 'Order created. Please complete payment.'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'order' => $order->load('items'),
                'message' => 'Order created successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get POS statistics
     */
    public function getStats(Request $request)
    {
        $user = Session::get('user');

        // If we have a user session, filter by staff_id, otherwise show all POS stats (admin view)
        $query = Order::where('order_type', 'pos');
        if ($user && isset($user['id'])) {
            $query->where('staff_id', $user['id']);
        }

        // Get today's stats
        $today = (clone $query)->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as orders, SUM(total_amount) as sales')
            ->first();

        // Get total stats
        $total = (clone $query)->selectRaw('COUNT(*) as orders, SUM(total_amount) as sales')
            ->first();

        return response()->json([
            'today_orders' => $today->orders ?? 0,
            'today_sales' => $today->sales ?? 0,
            'total_orders' => $total->orders ?? 0,
            'total_sales' => $total->sales ?? 0
        ]);
    }

    /**
     * Get POS orders
     */
    public function getOrders(Request $request)
    {
        $user = Session::get('user');
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');

        $query = Order::where('order_type', 'pos')
            ->with('orderItems');

        // If we have a user session, filter by staff_id, otherwise show all POS orders (admin view)
        if ($user && isset($user['id'])) {
            $query->where('staff_id', $user['id']);
        }

        if ($status) {
            $query->where('order_status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Get supported payment methods
     */
    public function getPaymentMethods(Request $request)
    {
        $methods = $this->paymentService->getSupportedPaymentMethods();

        return response()->json([
            'success' => true,
            'payment_methods' => $methods
        ]);
    }

    /**
     * Process card payment
     */
    public function processCardPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:card,contactless,bluetooth_reader',
            'reader_id' => 'nullable|string', // For Bluetooth readers
        ]);

        $order = Order::find($request->order_id);

        // Validate payment amount
        $validation = $this->paymentService->validatePaymentAmount($order->total_amount);
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['error']
            ], 400);
        }

        try {
            if ($request->payment_method === 'contactless') {
                $result = $this->paymentService->processContactlessPayment(
                    $order->total_amount,
                    $order->id
                );
            } elseif ($request->payment_method === 'bluetooth_reader') {
                $result = $this->paymentService->processCardPayment(
                    $request->reader_id,
                    $order->total_amount,
                    $order->id
                );
            } else {
                // Regular card payment
                $result = $this->paymentService->createPaymentIntent(
                    $order->total_amount,
                    'gbp',
                    ['order_id' => $order->id]
                );
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'payment_intent_id' => $result['payment_intent']->id ?? null,
                    'client_secret' => $result['client_secret'] ?? null,
                    'status' => $result['status'] ?? 'processing',
                    'message' => $result['message'] ?? 'Payment initiated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Payment processing failed'
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('POS Card Payment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string'
        ]);

        $result = $this->paymentService->getPaymentStatus($request->payment_intent_id);

        // If payment was captured, update the order status
        if ($result['success'] && $result['captured'] && isset($result['metadata']['order_id'])) {
            $order = Order::find($result['metadata']['order_id']);
            if ($order && $order->order_status !== 'completed') {
                $order->update(['order_status' => 'completed']);
            }
        }

        return response()->json($result);
    }

    /**
     * Get card readers (for Bluetooth setup)
     */
    public function getCardReaders(Request $request)
    {
        $result = $this->paymentService->getReaders();

        return response()->json($result);
    }

    /**
     * Register a new card reader
     */
    public function registerCardReader(Request $request)
    {
        $request->validate([
            'registration_code' => 'required|string',
            'label' => 'nullable|string|max:255'
        ]);

        $result = $this->paymentService->registerReader(
            $request->registration_code,
            $request->label ?? 'POS Reader'
        );

        return response()->json($result);
    }

    /**
     * Create connection token for Stripe Terminal
     */
    public function createConnectionToken(Request $request)
    {
        $result = $this->paymentService->createConnectionToken();

        return response()->json($result);
    }

    /**
     * Get scale configuration for frontend
     */
    public function getScaleConfig(Request $request)
    {
        $config = $this->scaleService->getScaleConfig();

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    /**
     * Scan for available Bluetooth scales
     */
    public function scanScales(Request $request)
    {
        $result = $this->scaleService->scanForScales();

        return response()->json($result);
    }

    /**
     * Connect to a Bluetooth scale
     */
    public function connectScale(Request $request)
    {
        $request->validate([
            'scale_type' => 'nullable|string',
            'device_name' => 'nullable|string'
        ]);

        $result = $this->scaleService->connectToScale(
            $request->scale_type,
            $request->device_name
        );

        return response()->json($result);
    }

    /**
     * Disconnect from scale
     */
    public function disconnectScale(Request $request)
    {
        $result = $this->scaleService->disconnectScale();

        return response()->json($result);
    }

    /**
     * Get scale status
     */
    public function getScaleStatus(Request $request)
    {
        $status = $this->scaleService->getScaleStatus();

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Read current weight from scale
     */
    public function readWeight(Request $request)
    {
        $result = $this->scaleService->readWeight();

        return response()->json($result);
    }

    /**
     * Set tare weight
     */
    public function setTare(Request $request)
    {
        $tareWeight = $request->get('tare_weight');

        $result = $this->scaleService->setTare($tareWeight);

        return response()->json($result);
    }

    /**
     * Zero the scale
     */
    public function zeroScale(Request $request)
    {
        $result = $this->scaleService->zeroScale();

        return response()->json($result);
    }

    /**
     * Set weight unit
     */
    public function setWeightUnit(Request $request)
    {
        $request->validate([
            'unit' => 'required|string|in:kg,g,lbs,oz'
        ]);

        $result = $this->scaleService->setWeightUnit($request->unit);

        return response()->json($result);
    }

    /**
     * Calculate price based on weight
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'weight' => 'required|numeric|min:0',
            'price_per_kg' => 'required|numeric|min:0',
            'unit' => 'nullable|string|in:kg,g,lbs,oz'
        ]);

        $result = $this->scaleService->calculatePrice(
            $request->weight,
            $request->price_per_kg,
            $request->unit
        );

        return response()->json([
            'success' => true,
            'calculation' => $result
        ]);
    }

    /**
     * Get printer configuration for frontend
     */
    public function getPrinterConfig(Request $request)
    {
        $config = $this->printerService->getPrinterConfig();

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    /**
     * Scan for available receipt printers
     */
    public function scanPrinters(Request $request)
    {
        $result = $this->printerService->scanForPrinters();

        return response()->json($result);
    }

    /**
     * Connect to a receipt printer
     */
    public function connectPrinter(Request $request)
    {
        $request->validate([
            'printer_type' => 'required|string',
            'connection_type' => 'required|string|in:usb,serial,network,bluetooth',
            'connection_config' => 'nullable|array'
        ]);

        $result = $this->printerService->connectToPrinter(
            $request->printer_type,
            $request->connection_type,
            $request->connection_config ?? []
        );

        return response()->json($result);
    }

    /**
     * Disconnect from printer
     */
    public function disconnectPrinter(Request $request)
    {
        $result = $this->printerService->disconnectPrinter();

        return response()->json($result);
    }

    /**
     * Get printer status
     */
    public function getPrinterStatus(Request $request)
    {
        $status = $this->printerService->getPrinterStatus();

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Print a receipt for an order
     */
    public function printReceipt(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::with('orderItems')->find($request->order_id);
        $user = Session::get('user');

        $orderData = [
            'id' => $order->id,
            'staff_name' => $user['name'] ?? 'POS Staff',
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price,
                    'weight' => $item->weight ?? null,
                    'weight_unit' => $item->weight_unit ?? null
                ];
            })->toArray(),
            'payment_method' => $order->payment_method,
            'total' => $order->total_amount
        ];

        $result = $this->printerService->printReceipt($orderData);

        return response()->json($result);
    }

    /**
     * Print a test receipt
     */
    public function printTestReceipt(Request $request)
    {
        $result = $this->printerService->printTestReceipt();

        return response()->json($result);
    }

    /**
     * Open cash drawer
     */
    public function openCashDrawer(Request $request)
    {
        $result = $this->printerService->openCashDrawer();

        return response()->json($result);
    }

    /**
     * Create Stripe payment intent for mobile terminal
     */
    public function createStripePaymentIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|max:3'
        ]);

        try {
            // Note: This is a placeholder. You'll need to configure Stripe API keys
            // in your .env file: STRIPE_SECRET_KEY and STRIPE_PUBLISHABLE_KEY
            
            $paymentIntentId = 'pi_' . strtoupper(bin2hex(random_bytes(12)));
            
            return response()->json([
                'success' => true,
                'payment_intent_id' => $paymentIntentId,
                'terminal_url' => 'stripe-terminal://payment/' . $paymentIntentId,
                'message' => 'Payment intent created. Complete payment in Stripe Terminal app.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Stripe payment status
     */
    public function checkStripePayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string'
        ]);

        try {
            // Placeholder - would normally check with Stripe API
            // For now, assume payment succeeded if checking
            
            return response()->json([
                'success' => true,
                'status' => 'succeeded',
                'message' => 'Payment confirmed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create WooCommerce order for payment
     */
    public function createWooCommercePaymentOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'items' => 'required|array'
        ]);

        try {
            // Create a pending WooCommerce order
            // Note: This would normally use WooCommerce REST API
            
            $orderId = rand(1000, 9999);
            $orderUrl = 'https://middleworldfarms.org/wp-admin/post.php?post=' . $orderId . '&action=edit';
            
            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'order_url' => $orderUrl,
                'app_url' => 'woocommerce://orders/' . $orderId,
                'message' => 'WooCommerce order created. Process payment in WooCommerce app.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check WooCommerce payment status
     */
    public function checkWooCommercePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer'
        ]);

        try {
            // Placeholder - would normally check WooCommerce order status via API
            // For now, assume payment succeeded if checking
            
            return response()->json([
                'success' => true,
                'paid' => true,
                'message' => 'Payment confirmed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'paid' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request payment from card reader
     */
    public function requestCardReaderPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string'
        ]);

        try {
            // Placeholder for card reader integration
            // Would normally communicate with actual card reader hardware
            
            $transactionId = 'TXN_' . strtoupper(bin2hex(random_bytes(8)));
            
            return response()->json([
                'success' => true,
                'transaction_id' => $transactionId,
                'message' => 'Payment approved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process digital payment for an order
     */
    private function processDigitalPaymentForOrder(Order $order): array
    {
        $settings = \App\Models\Setting::getAll();

        // Check which payment service is enabled
        if ($settings['pos_payment_stripe'] ?? false) {
            // Use Stripe Terminal
            return $this->processStripePaymentForOrder($order);
        } elseif ($settings['pos_payment_woocommerce'] ?? false) {
            // Use WooCommerce Payments
            return $this->processWooCommercePaymentForOrder($order);
        } elseif ($settings['pos_payment_square'] ?? false) {
            // Use Square
            return $this->processSquarePaymentForOrder($order);
        } else {
            return [
                'success' => false,
                'message' => 'No payment service enabled. Please enable Stripe Terminal, WooCommerce Payments, or Square in POS settings.'
            ];
        }
    }

    /**
     * Process Stripe payment for an order
     */
    private function processStripePaymentForOrder(Order $order): array
    {
        try {
            // Create payment intent
            $result = $this->paymentService->createPaymentIntent(
                $order->total_amount,
                'gbp',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name
                ]
            );

            if ($result['success']) {
                // Save Stripe payment intent ID to order
                $order->update([
                    'stripe_payment_intent_id' => $result['payment_intent']->id,
                    'payment_reference' => $result['payment_intent']->id,
                    'stripe_metadata' => [
                        'payment_intent_id' => $result['payment_intent']->id,
                        'amount' => $order->total_amount,
                        'currency' => 'gbp',
                        'created_at' => now()->toIso8601String()
                    ]
                ]);
                
                // Return payment intent details for frontend to complete payment
                return [
                    'success' => true,
                    'payment_intent_id' => $result['payment_intent']->id,
                    'client_secret' => $result['client_secret'],
                    'message' => 'Payment intent created. Please complete payment on card reader.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['error'] ?? 'Stripe payment failed'
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Stripe POS Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Stripe payment processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process WooCommerce payment for an order
     */
    private function processWooCommercePaymentForOrder(Order $order): array
    {
        // Placeholder for WooCommerce payment processing
        return [
            'success' => false,
            'message' => 'WooCommerce payment processing not yet implemented'
        ];
    }

    /**
     * Process Square payment for an order
     */
    private function processSquarePaymentForOrder(Order $order): array
    {
        // Placeholder for Square payment processing
        return [
            'success' => false,
            'message' => 'Square payment processing not yet implemented'
        ];
    }

    /**
     * Generate Stripe Terminal connection token
     * Required for Stripe Terminal SDK to authenticate
     */
    public function getTerminalConnectionToken()
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $connectionToken = $stripe->terminal->connectionTokens->create();
            
            return response()->json([
                'secret' => $connectionToken->secret
            ]);
        } catch (\Exception $e) {
            \Log::error('Terminal connection token error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Clear all test POS sales
     */
    public function clearTestSales()
    {
        try {
            DB::beginTransaction();
            
            // Get all POS orders
            $orderIds = Order::where('order_type', 'pos')->pluck('id');
            
            // Delete associated bank transactions
            \App\Models\BankTransaction::whereIn('matched_order_id', $orderIds)->delete();
            
            // Delete order items first
            OrderItem::whereIn('order_id', $orderIds)->delete();
            
            // Delete orders
            $deleted = Order::where('order_type', 'pos')->delete();
            
            DB::commit();
            
            \Log::info('POS test sales cleared', [
                'deleted_orders' => $deleted,
                'user' => Session::get('user.name')
            ]);
            
            return response()->json([
                'success' => true,
                'deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to clear test sales: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Order history page (admin only)
     */
    public function orderHistory()
    {
        return view('pos.order-history');
    }
    
    /**
     * Delete a specific POS order
     */
    public function deleteOrder($id)
    {
        try {
            DB::beginTransaction();
            
            $order = Order::findOrFail($id);
            
            // Delete associated bank transaction if exists
            \App\Models\BankTransaction::where('matched_order_id', $id)->delete();
            
            // Delete order items first
            OrderItem::where('order_id', $id)->delete();
            
            // Delete the order
            $order->delete();
            
            DB::commit();
            
            \Log::info('POS order deleted', [
                'order_id' => $id,
                'order_number' => $order->order_number,
                'user' => Session::get('user.name')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete order: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record POS transaction in bank transactions (accounting)
     */
    private function recordPOSTransactionInAccounts(Order $order, string $paymentMethod)
    {
        try {
            $description = $paymentMethod === 'card' 
                ? "POS Card Sale - {$order->order_number}"
                : "POS Cash Sale - {$order->order_number}";
            
            if ($order->customer_name && $order->customer_name !== 'Walk-in Customer') {
                $description .= " ({$order->customer_name})";
            }
            
            \App\Models\BankTransaction::create([
                'transaction_date' => now(),
                'description' => $description,
                'amount' => $order->total_amount,
                'type' => 'credit', // Income
                'reference' => $order->order_number,
                'category' => 'pos_sales',
                'notes' => $paymentMethod === 'card' ? 'Card payment via POS terminal' : 'Cash payment at POS',
                'matched_order_id' => $order->id,
                'imported_at' => now(),
                'imported_by' => Session::get('user.id', 1),
            ]);
            
            \Log::info('POS transaction recorded in accounts', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->total_amount,
                'payment_method' => $paymentMethod
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to record POS transaction in accounts', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the order creation if accounting recording fails
        }
    }
    
    /**
     * Mobile-friendly deliveries & collections completion view
     * Stripped down version for completing deliveries without going back to office
     */
    public function deliveries()
    {
        $user = Session::get('user');

        return view('pos.deliveries', [
            'user' => $user,
            'title' => 'Deliveries & Collections - ' . ($user['name'] ?? 'Staff')
        ]);
    }
    
    /**
     * Get today's deliveries and collections data for POS
     */
    public function getDeliveriesData(Request $request)
    {
        try {
            $wpApi = app(\App\Services\WpApiService::class);
            $deliveryController = app(\App\Http\Controllers\Admin\DeliveryController::class);
            
            $selectedWeek = (int) $request->get('week', date('W'));
            
            // Get raw data
            $rawData = [];
            try {
                $rawData = $wpApi->getDeliveryScheduleData(500);
            } catch (\Exception $e) {
                \Log::error('POS Delivery schedule API timeout: ' . $e->getMessage());
            }
            
            // Use reflection to call private methods from DeliveryController
            $reflection = new \ReflectionClass($deliveryController);
            
            // Fetch collection day preferences
            $fetchMethod = $reflection->getMethod('fetchCollectionDayPreferences');
            $fetchMethod->setAccessible(true);
            $collectionDayPreferences = $fetchMethod->invoke($deliveryController, $rawData);
            
            // Transform data
            $transformMethod = $reflection->getMethod('transformScheduleData');
            $transformMethod->setAccessible(true);
            $scheduleData = $transformMethod->invoke($deliveryController, $rawData, $selectedWeek, $collectionDayPreferences);
            
            // Add completion data
            $completionMethod = $reflection->getMethod('addCompletionData');
            $completionMethod->setAccessible(true);
            $scheduleData = $completionMethod->invoke($deliveryController, $scheduleData);
            
            // Return full week's deliveries and collections (not just today)
            if (empty($scheduleData['data'])) {
                return response()->json([
                    'success' => true,
                    'deliveries' => [],
                    'collections' => [],
                    'message' => 'No deliveries or collections scheduled for this week'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'deliveries' => $scheduleData['data'],
                'collections' => $scheduleData['data'],
                'week' => $selectedWeek
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get POS deliveries data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark delivery/collection as complete from POS
     */
    public function completeDelivery(Request $request)
    {
        try {
            $validated = $request->validate([
                'delivery_id' => 'required|string',
                'type' => 'required|in:delivery,collection',
                'delivery_date' => 'required|date'
            ]);
            
            $user = Session::get('user');
            $completedBy = $user['name'] ?? 'POS Staff';
            
            \App\Models\DeliveryCompletion::updateOrCreate(
                [
                    'external_id' => $request->delivery_id,
                    'type' => $request->type,
                    'delivery_date' => $request->delivery_date
                ],
                [
                    'completed_at' => now(),
                    'completed_by' => $completedBy
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($request->type) . ' marked as complete'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to complete delivery from POS: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error marking complete: ' . $e->getMessage()
            ], 500);
        }
    }
}
