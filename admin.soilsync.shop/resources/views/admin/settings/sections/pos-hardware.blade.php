<h4 class="mb-4"><i class="fas fa-cash-register"></i> POS & Hardware Settings</h4>

<div class="row">
    <!-- POS Terminal Configuration -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-cash-register text-primary"></i> POS Terminal Configuration
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pos_terminal_name" class="form-label">Terminal Name / Location</label>
                        <input type="text" class="form-control" id="pos_terminal_name" name="pos_terminal_name" 
                               value="{{ $settings['pos_terminal_name'] ?? 'Main Counter' }}" placeholder="Main Counter">
                        <small class="text-muted">Identifier for this POS terminal</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="pos_mode" class="form-label">POS Mode</label>
                        <select class="form-select" id="pos_mode" name="pos_mode">
                            <option value="standard" {{ ($settings['pos_mode'] ?? 'standard') === 'standard' ? 'selected' : '' }}>Standard (Keyboard & Mouse)</option>
                            <option value="touch" {{ ($settings['pos_mode'] ?? '') === 'touch' ? 'selected' : '' }}>Touch Screen</option>
                            <option value="hybrid" {{ ($settings['pos_mode'] ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid (Both)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="pos_offline_mode" 
                               name="pos_offline_mode" {{ ($settings['pos_offline_mode'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="pos_offline_mode">
                            <strong>Enable Offline Mode</strong>
                        </label>
                    </div>
                    <small class="text-muted">Allow transactions when internet connection is unavailable (syncs when online)</small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="pos_customer_display" 
                               name="pos_customer_display" {{ ($settings['pos_customer_display'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="pos_customer_display">
                            <strong>Enable Customer-Facing Display</strong>
                        </label>
                    </div>
                    <small class="text-muted">Show transaction details on second screen for customers</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Barcode Scanner -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-barcode text-success"></i> Barcode Scanner Configuration
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="barcode_scanner_type" class="form-label">Scanner Type</label>
                        <select class="form-select" id="barcode_scanner_type" name="barcode_scanner_type">
                            <option value="usb_hid" {{ ($settings['barcode_scanner_type'] ?? 'usb_hid') === 'usb_hid' ? 'selected' : '' }}>USB HID (Keyboard Emulation)</option>
                            <option value="serial" {{ ($settings['barcode_scanner_type'] ?? '') === 'serial' ? 'selected' : '' }}>Serial/RS-232</option>
                            <option value="bluetooth" {{ ($settings['barcode_scanner_type'] ?? '') === 'bluetooth' ? 'selected' : '' }}>Bluetooth</option>
                            <option value="camera" {{ ($settings['barcode_scanner_type'] ?? '') === 'camera' ? 'selected' : '' }}>Camera/Webcam</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="barcode_format" class="form-label">Barcode Format</label>
                        <select class="form-select" id="barcode_format" name="barcode_format">
                            <option value="ean13" {{ ($settings['barcode_format'] ?? 'ean13') === 'ean13' ? 'selected' : '' }}>EAN-13 (International)</option>
                            <option value="upc" {{ ($settings['barcode_format'] ?? '') === 'upc' ? 'selected' : '' }}>UPC-A (North America)</option>
                            <option value="code39" {{ ($settings['barcode_format'] ?? '') === 'code39' ? 'selected' : '' }}>Code 39</option>
                            <option value="code128" {{ ($settings['barcode_format'] ?? '') === 'code128' ? 'selected' : '' }}>Code 128</option>
                            <option value="qr_code" {{ ($settings['barcode_format'] ?? '') === 'qr_code' ? 'selected' : '' }}>QR Code</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="barcode_prefix" class="form-label">Barcode Prefix (Optional)</label>
                    <input type="text" class="form-control" id="barcode_prefix" name="barcode_prefix" 
                           value="{{ $settings['barcode_prefix'] ?? '' }}" placeholder="MWF">
                    <small class="text-muted">Prefix for internal SKU barcodes (e.g., MWF-001, MWF-002)</small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_add_scanned_items" 
                               name="auto_add_scanned_items" {{ ($settings['auto_add_scanned_items'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_add_scanned_items">
                            <strong>Auto-add scanned items to cart</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="scanner_beep_on_scan" 
                               name="scanner_beep_on_scan" {{ ($settings['scanner_beep_on_scan'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="scanner_beep_on_scan">
                            <strong>Beep on successful scan</strong>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Receipt Printer -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-receipt text-warning"></i> Receipt Printer
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="receipt_printer_type" class="form-label">Printer Type</label>
                        <select class="form-select" id="receipt_printer_type" name="receipt_printer_type">
                            <option value="thermal_58mm" {{ ($settings['receipt_printer_type'] ?? 'thermal_58mm') === 'thermal_58mm' ? 'selected' : '' }}>Thermal 58mm</option>
                            <option value="thermal_80mm" {{ ($settings['receipt_printer_type'] ?? '') === 'thermal_80mm' ? 'selected' : '' }}>Thermal 80mm</option>
                            <option value="impact" {{ ($settings['receipt_printer_type'] ?? '') === 'impact' ? 'selected' : '' }}>Impact/Dot Matrix</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="receipt_printer_connection" class="form-label">Connection Type</label>
                        <select class="form-select" id="receipt_printer_connection" name="receipt_printer_connection">
                            <option value="usb" {{ ($settings['receipt_printer_connection'] ?? 'usb') === 'usb' ? 'selected' : '' }}>USB</option>
                            <option value="network" {{ ($settings['receipt_printer_connection'] ?? '') === 'network' ? 'selected' : '' }}>Network (IP)</option>
                            <option value="bluetooth" {{ ($settings['receipt_printer_connection'] ?? '') === 'bluetooth' ? 'selected' : '' }}>Bluetooth</option>
                            <option value="serial" {{ ($settings['receipt_printer_connection'] ?? '') === 'serial' ? 'selected' : '' }}>Serial Port</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="receipt_printer_ip" class="form-label">Printer IP Address (Network Only)</label>
                    <input type="text" class="form-control" id="receipt_printer_ip" name="receipt_printer_ip" 
                           value="{{ $settings['receipt_printer_ip'] ?? '' }}" placeholder="192.168.1.200">
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_print_receipt" 
                               name="auto_print_receipt" {{ ($settings['auto_print_receipt'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_print_receipt">
                            <strong>Auto-print receipt after transaction</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="receipt_cut_after_print" 
                               name="receipt_cut_after_print" {{ ($settings['receipt_cut_after_print'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="receipt_cut_after_print">
                            <strong>Auto-cut receipt after printing</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="receipt_footer_text" class="form-label">Receipt Footer Message</label>
                    <textarea class="form-control" id="receipt_footer_text" name="receipt_footer_text" 
                              rows="2" placeholder="Thank you for supporting local farming!">{{ $settings['receipt_footer_text'] ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cash Drawer -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-cash-register text-info"></i> Cash Drawer
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cash_drawer_type" class="form-label">Cash Drawer Type</label>
                        <select class="form-select" id="cash_drawer_type" name="cash_drawer_type">
                            <option value="automatic" {{ ($settings['cash_drawer_type'] ?? 'automatic') === 'automatic' ? 'selected' : '' }}>Automatic (Connected to Receipt Printer)</option>
                            <option value="manual" {{ ($settings['cash_drawer_type'] ?? '') === 'manual' ? 'selected' : '' }}>Manual (No Electronic Trigger)</option>
                            <option value="none" {{ ($settings['cash_drawer_type'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="cash_drawer_open_code" class="form-label">Open Command (ESC/POS)</label>
                        <input type="text" class="form-control font-monospace" id="cash_drawer_open_code" name="cash_drawer_open_code" 
                               value="{{ $settings['cash_drawer_open_code'] ?? '\x1B\x70\x00' }}" placeholder="\x1B\x70\x00">
                        <small class="text-muted">ESC/POS command to open cash drawer</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="starting_cash_amount" class="form-label">Starting Cash Float (£)</label>
                    <input type="number" class="form-control" id="starting_cash_amount" name="starting_cash_amount" 
                           value="{{ $settings['starting_cash_amount'] ?? '100.00' }}" step="0.01" min="0">
                    <small class="text-muted">Default cash amount in drawer at start of day</small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="cash_drawer_open_on_sale" 
                               name="cash_drawer_open_on_sale" {{ ($settings['cash_drawer_open_on_sale'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="cash_drawer_open_on_sale">
                            <strong>Auto-open drawer on cash sale</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="require_manager_approval_large_cash" 
                               name="require_manager_approval_large_cash" {{ ($settings['require_manager_approval_large_cash'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="require_manager_approval_large_cash">
                            <strong>Require manager approval for large cash transactions</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="large_cash_threshold" class="form-label">Large Cash Threshold (£)</label>
                    <input type="number" class="form-control" id="large_cash_threshold" name="large_cash_threshold" 
                           value="{{ $settings['large_cash_threshold'] ?? '100.00' }}" step="0.01" min="0">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card Reader (Stripe Terminal) -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fab fa-cc-stripe text-primary"></i> Card Reader (Stripe Terminal)
                </h5>
                
                <p class="text-muted small mb-4">
                    Configure Stripe Terminal for in-person card payments. Requires Stripe POS hardware.
                </p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="stripe_terminal_location" class="form-label">Terminal Location ID</label>
                        <input type="text" class="form-control font-monospace small" id="stripe_terminal_location" 
                               name="stripe_terminal_location" value="{{ $settings['stripe_terminal_location'] ?? '' }}" 
                               placeholder="tml_...">
                        <small class="text-muted">From Stripe Dashboard → Terminal → Locations</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="stripe_terminal_reader_id" class="form-label">Reader ID (Optional)</label>
                        <input type="text" class="form-control font-monospace small" id="stripe_terminal_reader_id" 
                               name="stripe_terminal_reader_id" value="{{ $settings['stripe_terminal_reader_id'] ?? '' }}" 
                               placeholder="tmr_...">
                        <small class="text-muted">Specific reader to use (leave blank to select at runtime)</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="stripe_terminal_test_mode" 
                               name="stripe_terminal_test_mode" {{ ($settings['stripe_terminal_test_mode'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="stripe_terminal_test_mode">
                            <strong>Test Mode</strong>
                        </label>
                    </div>
                    <small class="text-muted">Use test readers and payments (for development/testing)</small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="card_reader_enabled" 
                               name="card_reader_enabled" {{ ($settings['card_reader_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="card_reader_enabled">
                            <strong>Enable Card Reader Integration</strong>
                        </label>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <strong>Setup Instructions:</strong><br>
                        1. Log into <a href="https://dashboard.stripe.com/terminal/locations" target="_blank">Stripe Terminal Dashboard</a><br>
                        2. Create a location for your farm/shop<br>
                        3. Register your card reader to that location<br>
                        4. Copy the Location ID (tml_...) above<br>
                        5. Ensure Stripe API keys are configured in Payment Integration settings
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scale Integration -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-weight text-secondary"></i> Digital Scale Integration
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="scale_type" class="form-label">Scale Type</label>
                        <select class="form-select" id="scale_type" name="scale_type">
                            <option value="none" {{ ($settings['scale_type'] ?? 'none') === 'none' ? 'selected' : '' }}>None</option>
                            <option value="usb" {{ ($settings['scale_type'] ?? '') === 'usb' ? 'selected' : '' }}>USB Scale</option>
                            <option value="serial" {{ ($settings['scale_type'] ?? '') === 'serial' ? 'selected' : '' }}>Serial/RS-232</option>
                            <option value="network" {{ ($settings['scale_type'] ?? '') === 'network' ? 'selected' : '' }}>Network Scale</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="scale_unit" class="form-label">Weight Unit</label>
                        <select class="form-select" id="scale_unit" name="scale_unit">
                            <option value="kg" {{ ($settings['scale_unit'] ?? 'kg') === 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                            <option value="g" {{ ($settings['scale_unit'] ?? '') === 'g' ? 'selected' : '' }}>Grams (g)</option>
                            <option value="lb" {{ ($settings['scale_unit'] ?? '') === 'lb' ? 'selected' : '' }}>Pounds (lb)</option>
                            <option value="oz" {{ ($settings['scale_unit'] ?? '') === 'oz' ? 'selected' : '' }}>Ounces (oz)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="scale_auto_read" 
                               name="scale_auto_read" {{ ($settings['scale_auto_read'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="scale_auto_read">
                            <strong>Auto-read weight when stable</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="scale_tare_on_scan" 
                               name="scale_tare_on_scan" {{ ($settings['scale_tare_on_scan'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="scale_tare_on_scan">
                            <strong>Auto-tare when scanning new item</strong>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
