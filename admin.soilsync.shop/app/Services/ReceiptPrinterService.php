<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ReceiptPrinterService
{
    /**
     * Supported receipt printer manufacturers and models
     */
    const SUPPORTED_PRINTERS = [
        'epson' => [
            'name' => 'Epson',
            'models' => ['TM-T88', 'TM-T70', 'TM-T20', 'TM-m30', 'TM-m50'],
            'protocol' => 'esc_pos',
            'connections' => ['usb', 'serial', 'network', 'bluetooth'],
            'default_port' => 9100,
            'commands' => [
                'init' => "\x1B\x40",
                'cut' => "\x1D\x56\x42\x00",
                'feed' => "\x1B\x64",
                'bold_on' => "\x1B\x45\x01",
                'bold_off' => "\x1B\x45\x00",
                'align_center' => "\x1B\x61\x01",
                'align_left' => "\x1B\x61\x00",
                'align_right' => "\x1B\x61\x02",
                'font_a' => "\x1B\x4D\x00",
                'font_b' => "\x1B\x4D\x01",
                'double_height_on' => "\x1B\x21\x10",
                'double_height_off' => "\x1B\x21\x00",
                'double_width_on' => "\x1B\x21\x20",
                'double_width_off' => "\x1B\x21\x00",
                'underline_on' => "\x1B\x2D\x01",
                'underline_off' => "\x1B\x2D\x00",
            ]
        ],
        'star' => [
            'name' => 'Star Micronics',
            'models' => ['TSP100', 'TSP143', 'mPOP', 'SM-L200', 'SM-S210i'],
            'protocol' => 'esc_pos',
            'connections' => ['usb', 'serial', 'network', 'bluetooth'],
            'default_port' => 9100,
            'commands' => [
                'init' => "\x1B\x40",
                'cut' => "\x1B\x64\x02",
                'feed' => "\x1B\x64",
                'bold_on' => "\x1B\x45\x01",
                'bold_off' => "\x1B\x45\x00",
                'align_center' => "\x1B\x61\x01",
                'align_left' => "\x1B\x61\x00",
                'align_right' => "\x1B\x61\x02",
                'double_height_on' => "\x1B\x21\x10",
                'double_height_off' => "\x1B\x21\x00",
                'double_width_on' => "\x1B\x21\x20",
                'double_width_off' => "\x1B\x21\x00",
                'underline_on' => "\x1B\x2D\x01",
                'underline_off' => "\x1B\x2D\x00",
            ]
        ],
        'citizen' => [
            'name' => 'Citizen',
            'models' => ['CT-S2000', 'CT-S4000', 'CT-S601', 'CT-S651'],
            'protocol' => 'esc_pos',
            'connections' => ['usb', 'serial', 'network'],
            'default_port' => 9100,
            'commands' => [
                'init' => "\x1B\x40",
                'cut' => "\x1D\x56\x42\x00",
                'feed' => "\x1B\x64",
                'bold_on' => "\x1B\x45\x01",
                'bold_off' => "\x1B\x45\x00",
                'align_center' => "\x1B\x61\x01",
                'align_left' => "\x1B\x61\x00",
                'align_right' => "\x1B\x61\x02",
                'double_height_on' => "\x1B\x21\x10",
                'double_height_off' => "\x1B\x21\x00",
                'double_width_on' => "\x1B\x21\x20",
                'double_width_off' => "\x1B\x21\x00",
            ]
        ],
        'brother' => [
            'name' => 'Brother',
            'models' => ['RJ-4030', 'RJ-4040', 'RJ-3050', 'QL-800'],
            'protocol' => 'esc_pos',
            'connections' => ['usb', 'network', 'bluetooth'],
            'default_port' => 9100,
            'commands' => [
                'init' => "\x1B\x40",
                'cut' => "\x1D\x56\x42\x00",
                'feed' => "\x1B\x64",
                'bold_on' => "\x1B\x45\x01",
                'bold_off' => "\x1B\x45\x00",
                'align_center' => "\x1B\x61\x01",
                'align_left' => "\x1B\x61\x00",
                'align_right' => "\x1B\x61\x02",
            ]
        ],
        'generic' => [
            'name' => 'Generic ESC/POS Printer',
            'models' => ['Generic'],
            'protocol' => 'esc_pos',
            'connections' => ['usb', 'serial', 'network', 'bluetooth'],
            'default_port' => 9100,
            'commands' => [
                'init' => "\x1B\x40",
                'cut' => "\x1D\x56\x42\x00",
                'feed' => "\x1B\x64",
                'bold_on' => "\x1B\x45\x01",
                'bold_off' => "\x1B\x45\x00",
                'align_center' => "\x1B\x61\x01",
                'align_left' => "\x1B\x61\x00",
                'align_right' => "\x1B\x61\x02",
                'double_height_on' => "\x1B\x21\x10",
                'double_height_off' => "\x1B\x21\x00",
                'double_width_on' => "\x1B\x21\x20",
                'double_width_off' => "\x1B\x21\x00",
                'underline_on' => "\x1B\x2D\x01",
                'underline_off' => "\x1B\x2D\x00",
            ]
        ]
    ];

    /**
     * Connection types and their configurations
     */
    const CONNECTION_TYPES = [
        'usb' => [
            'name' => 'USB',
            'requires_device_path' => true,
            'default_path' => '/dev/usb/lp0'
        ],
        'serial' => [
            'name' => 'Serial',
            'requires_device_path' => true,
            'default_path' => '/dev/ttyUSB0',
            'baud_rate' => 9600
        ],
        'network' => [
            'name' => 'Network',
            'requires_ip' => true,
            'default_port' => 9100
        ],
        'bluetooth' => [
            'name' => 'Bluetooth',
            'requires_mac_address' => true,
            'service_uuid' => '00001101-0000-1000-8000-00805f9b34fb'
        ]
    ];

    protected $connectedPrinter = null;
    protected $currentPrinter = null;
    protected $connectionType = null;
    protected $connectionHandle = null;

    /**
     * Scan for available receipt printers
     */
    public function scanForPrinters()
    {
        try {
            // In a real implementation, this would scan for USB/serial/network devices
            $printers = [];
            foreach (self::SUPPORTED_PRINTERS as $key => $printer) {
                foreach ($printer['connections'] as $connection) {
                    $printers[] = [
                        'id' => $key . '_' . $connection,
                        'manufacturer' => $printer['name'],
                        'connection_type' => $connection,
                        'supported' => true,
                        'models' => $printer['models']
                    ];
                }
            }

            return [
                'success' => true,
                'printers' => $printers,
                'message' => 'Printer types available for connection'
            ];
        } catch (Exception $e) {
            Log::error('Printer scanning error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Connect to a receipt printer
     */
    public function connectToPrinter($printerType, $connectionType, $connectionConfig = [])
    {
        try {
            if (!isset(self::SUPPORTED_PRINTERS[$printerType])) {
                $printerType = 'generic';
            }

            if (!isset(self::CONNECTION_TYPES[$connectionType])) {
                throw new Exception('Unsupported connection type: ' . $connectionType);
            }

            $this->currentPrinter = self::SUPPORTED_PRINTERS[$printerType];
            $this->connectionType = self::CONNECTION_TYPES[$connectionType];

            // In a real implementation, this would establish the actual connection
            // For now, simulate connection
            $this->connectedPrinter = [
                'manufacturer' => $this->currentPrinter['name'],
                'type' => $printerType,
                'connection_type' => $connectionType,
                'connection_config' => $connectionConfig,
                'connected_at' => now(),
                'status' => 'connected'
            ];

            // Store connection in cache for session persistence
            Cache::put('receipt_printer_connected', $this->connectedPrinter, now()->addHours(8));

            Log::info('Receipt printer connected', [
                'printer_type' => $printerType,
                'connection_type' => $connectionType
            ]);

            return [
                'success' => true,
                'printer' => $this->connectedPrinter,
                'printer_info' => $this->currentPrinter,
                'message' => 'Printer connected successfully'
            ];

        } catch (Exception $e) {
            Log::error('Printer connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Disconnect from printer
     */
    public function disconnectPrinter()
    {
        try {
            if ($this->connectedPrinter) {
                Log::info('Receipt printer disconnected', [
                    'printer' => $this->connectedPrinter['manufacturer']
                ]);

                $this->connectedPrinter = null;
                $this->currentPrinter = null;
                $this->connectionType = null;
                $this->connectionHandle = null;

                Cache::forget('receipt_printer_connected');
            }

            return [
                'success' => true,
                'message' => 'Printer disconnected'
            ];

        } catch (Exception $e) {
            Log::error('Printer disconnection error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get printer connection status
     */
    public function getPrinterStatus()
    {
        $cached = Cache::get('receipt_printer_connected');

        if ($cached && !$this->connectedPrinter) {
            $this->connectedPrinter = $cached;
            $this->currentPrinter = self::SUPPORTED_PRINTERS[$cached['type']] ?? self::SUPPORTED_PRINTERS['generic'];
            $this->connectionType = self::CONNECTION_TYPES[$cached['connection_type']];
        }

        return [
            'connected' => $this->connectedPrinter !== null,
            'printer' => $this->connectedPrinter,
            'printer_info' => $this->currentPrinter,
            'connection_type' => $this->connectionType
        ];
    }

    /**
     * Print a receipt
     */
    public function printReceipt($orderData, $options = [])
    {
        try {
            if (!$this->connectedPrinter) {
                return [
                    'success' => false,
                    'error' => 'No printer connected'
                ];
            }

            $receiptData = $this->formatReceipt($orderData, $options);

            // In a real implementation, this would send the formatted data to the printer
            $result = $this->sendToPrinter($receiptData);

            if ($result) {
                Log::info('Receipt printed successfully', [
                    'order_id' => $orderData['id'] ?? null,
                    'printer' => $this->connectedPrinter['manufacturer']
                ]);

                return [
                    'success' => true,
                    'message' => 'Receipt printed successfully',
                    'receipt_data' => $receiptData
                ];
            } else {
                throw new Exception('Failed to send data to printer');
            }

        } catch (Exception $e) {
            Log::error('Receipt printing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Print a test receipt
     */
    public function printTestReceipt()
    {
        try {
            if (!$this->connectedPrinter) {
                return [
                    'success' => false,
                    'error' => 'No printer connected'
                ];
            }

            $testData = $this->formatTestReceipt();

            // In a real implementation, this would send the test data to the printer
            $result = $this->sendToPrinter($testData);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Test receipt printed successfully'
                ];
            } else {
                throw new Exception('Failed to send test data to printer');
            }

        } catch (Exception $e) {
            Log::error('Test receipt printing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Open cash drawer (if supported)
     */
    public function openCashDrawer()
    {
        try {
            if (!$this->connectedPrinter) {
                return [
                    'success' => false,
                    'error' => 'No printer connected'
                ];
            }

            // ESC/POS command to open cash drawer
            $command = "\x1B\x70\x00\x19\xFA"; // Open drawer 1

            $result = $this->sendToPrinter($command);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Cash drawer opened'
                ];
            } else {
                throw new Exception('Failed to open cash drawer');
            }

        } catch (Exception $e) {
            Log::error('Cash drawer error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format receipt data
     */
    private function formatReceipt($orderData, $options = [])
    {
        $commands = $this->currentPrinter['commands'];

        $receipt = '';

        // Initialize printer
        $receipt .= $commands['init'];

        // Header
        $receipt .= $commands['align_center'];
        $receipt .= $commands['double_height_on'];
        $receipt .= "MIDDLEWORLD FARMS\n";
        $receipt .= $commands['double_height_off'];
        $receipt .= "Fresh Organic Produce\n";
        $receipt .= "www.middleworldfarms.org\n";
        $receipt .= "\n";

        // Order details
        $receipt .= $commands['align_left'];
        $receipt .= "Order #" . ($orderData['id'] ?? 'N/A') . "\n";
        $receipt .= "Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $receipt .= "Staff: " . ($orderData['staff_name'] ?? 'POS Staff') . "\n";
        $receipt .= "\n";

        // Items header
        $receipt .= $commands['bold_on'];
        $receipt .= str_pad("Item", 20) . str_pad("Qty", 6) . str_pad("Price", 8) . "\n";
        $receipt .= $commands['bold_off'];
        $receipt .= str_repeat("-", 34) . "\n";

        // Items
        $total = 0;
        foreach ($orderData['items'] ?? [] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;

            $itemName = substr($item['product_name'], 0, 18);
            $receipt .= str_pad($itemName, 20);
            $receipt .= str_pad($item['quantity'], 6);
            $receipt .= str_pad('£' . number_format($subtotal, 2), 8) . "\n";

            // Show weight info for weighted items
            if (isset($item['weight'])) {
                $receipt .= "  (" . number_format($item['weight'], 3) . " " . ($item['weight_unit'] ?? 'kg') . ")\n";
            }
        }

        $receipt .= str_repeat("-", 34) . "\n";

        // Total
        $receipt .= $commands['bold_on'];
        $receipt .= str_pad("TOTAL", 26) . str_pad('£' . number_format($total, 2), 8) . "\n";
        $receipt .= $commands['bold_off'];

        // Payment method
        if (isset($orderData['payment_method'])) {
            $receipt .= "\nPayment: " . ucfirst($orderData['payment_method']) . "\n";
        }

        // Footer
        $receipt .= "\n";
        $receipt .= $commands['align_center'];
        $receipt .= "Thank you for your business!\n";
        $receipt .= "Visit us again soon.\n";

        // Feed and cut
        $receipt .= $commands['feed'] . "\x03"; // Feed 3 lines
        $receipt .= $commands['cut'];

        return $receipt;
    }

    /**
     * Format test receipt
     */
    private function formatTestReceipt()
    {
        $commands = $this->currentPrinter['commands'];

        $receipt = '';

        // Initialize printer
        $receipt .= $commands['init'];

        // Test header
        $receipt .= $commands['align_center'];
        $receipt .= $commands['double_height_on'];
        $receipt .= "PRINTER TEST\n";
        $receipt .= $commands['double_height_off'];
        $receipt .= "MiddleWorld Farms POS\n";
        $receipt .= "Test Receipt\n";
        $receipt .= "Printer: " . $this->connectedPrinter['manufacturer'] . "\n";
        $receipt .= "Connection: " . ucfirst($this->connectedPrinter['connection_type']) . "\n";
        $receipt .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
        $receipt .= "\n";

        // Test patterns
        $receipt .= $commands['align_left'];
        $receipt .= "Normal text\n";
        $receipt .= $commands['bold_on'] . "Bold text\n" . $commands['bold_off'];
        $receipt .= $commands['underline_on'] . "Underlined text\n" . $commands['underline_off'];
        $receipt .= $commands['double_height_on'] . "Double height\n" . $commands['double_height_off'];
        $receipt .= $commands['double_width_on'] . "Double width\n" . $commands['double_width_off'];

        // Test characters
        $receipt .= "\nCharacters: !\"#$%&'()*+,-./0123456789:;<=>?@\n";
        $receipt .= "ABCDEFGHIJ KLMNOPQRST UVWXYZ[\\]^_`abc\n";
        $receipt .= "defghijklmn opqrstuvw xyz{|}~\n";

        // Feed and cut
        $receipt .= $commands['feed'] . "\x03";
        $receipt .= $commands['cut'];

        return $receipt;
    }

    /**
     * Send data to printer (simulated)
     */
    private function sendToPrinter($data)
    {
        // In a real implementation, this would send the data via the appropriate connection
        // For USB: write to device file
        // For Serial: write to serial port
        // For Network: send via TCP socket
        // For Bluetooth: send via Bluetooth socket

        Log::info('Printer data sent', [
            'data_length' => strlen($data),
            'printer' => $this->connectedPrinter['manufacturer'] ?? 'Unknown'
        ]);

        // Simulate success
        return true;
    }

    /**
     * Get printer configuration for frontend
     */
    public function getPrinterConfig()
    {
        return [
            'supported_printers' => collect(self::SUPPORTED_PRINTERS)->map(function ($printer, $key) {
                return [
                    'id' => $key,
                    'name' => $printer['name'],
                    'models' => $printer['models'],
                    'connections' => $printer['connections']
                ];
            })->values(),
            'connection_types' => self::CONNECTION_TYPES
        ];
    }
}