<h4 class="mb-4"><i class="fas fa-print"></i> Printing & Document Settings</h4>

<div class="row">
    <!-- Printer Configuration -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-print text-primary"></i> Printer Configuration
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="default_printer" class="form-label">Default Printer</label>
                        <select class="form-select" id="default_printer" name="default_printer">
                            <option value="">Select Printer...</option>
                            <option value="receipt_printer" {{ ($settings['default_printer'] ?? '') === 'receipt_printer' ? 'selected' : '' }}>Receipt Printer</option>
                            <option value="label_printer" {{ ($settings['default_printer'] ?? '') === 'label_printer' ? 'selected' : '' }}>Label Printer</option>
                            <option value="invoice_printer" {{ ($settings['default_printer'] ?? '') === 'invoice_printer' ? 'selected' : '' }}>Invoice Printer</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="print_dpi" class="form-label">Print Quality (DPI)</label>
                        <select class="form-select" id="print_dpi" name="print_dpi">
                            <option value="203" {{ ($settings['print_dpi'] ?? '203') === '203' ? 'selected' : '' }}>203 DPI (Standard)</option>
                            <option value="300" {{ ($settings['print_dpi'] ?? '') === '300' ? 'selected' : '' }}>300 DPI (High Quality)</option>
                            <option value="600" {{ ($settings['print_dpi'] ?? '') === '600' ? 'selected' : '' }}>600 DPI (Premium)</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="printer_connection" class="form-label">Printer Connection Type</label>
                        <select class="form-select" id="printer_connection" name="printer_connection">
                            <option value="usb" {{ ($settings['printer_connection'] ?? 'usb') === 'usb' ? 'selected' : '' }}>USB</option>
                            <option value="network" {{ ($settings['printer_connection'] ?? '') === 'network' ? 'selected' : '' }}>Network (IP)</option>
                            <option value="bluetooth" {{ ($settings['printer_connection'] ?? '') === 'bluetooth' ? 'selected' : '' }}>Bluetooth</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="printer_ip" class="form-label">Printer IP Address (Network Only)</label>
                        <input type="text" class="form-control" id="printer_ip" name="printer_ip" 
                               value="{{ $settings['printer_ip'] ?? '' }}" placeholder="192.168.1.100">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Label Printing -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-tags text-success"></i> Label Printing
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="label_size" class="form-label">Label Size</label>
                        <select class="form-select" id="label_size" name="label_size">
                            <option value="4x6" {{ ($settings['label_size'] ?? '4x6') === '4x6' ? 'selected' : '' }}>4" x 6" (Standard Shipping)</option>
                            <option value="4x3" {{ ($settings['label_size'] ?? '') === '4x3' ? 'selected' : '' }}>4" x 3" (Product Label)</option>
                            <option value="2x1" {{ ($settings['label_size'] ?? '') === '2x1' ? 'selected' : '' }}>2" x 1" (Small Product)</option>
                            <option value="a4" {{ ($settings['label_size'] ?? '') === 'a4' ? 'selected' : '' }}>A4 Sheet (Multi-label)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="label_format" class="form-label">Label Format</label>
                        <select class="form-select" id="label_format" name="label_format">
                            <option value="zpl" {{ ($settings['label_format'] ?? 'zpl') === 'zpl' ? 'selected' : '' }}>ZPL (Zebra)</option>
                            <option value="epl" {{ ($settings['label_format'] ?? '') === 'epl' ? 'selected' : '' }}>EPL (Eltron)</option>
                            <option value="pdf" {{ ($settings['label_format'] ?? '') === 'pdf' ? 'selected' : '' }}>PDF (Standard Printer)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="print_barcode_on_label" 
                               name="print_barcode_on_label" {{ ($settings['print_barcode_on_label'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="print_barcode_on_label">
                            <strong>Print Barcode on Labels</strong>
                        </label>
                    </div>
                    <small class="text-muted">Include SKU/product barcode on printed labels</small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="print_qr_code_on_label" 
                               name="print_qr_code_on_label" {{ ($settings['print_qr_code_on_label'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="print_qr_code_on_label">
                            <strong>Print QR Code on Labels</strong>
                        </label>
                    </div>
                    <small class="text-muted">Include QR code linking to product details</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Packing Slips -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-box text-warning"></i> Packing Slips
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="packing_slip_paper_size" class="form-label">Paper Size</label>
                        <select class="form-select" id="packing_slip_paper_size" name="packing_slip_paper_size">
                            <option value="a4" {{ ($settings['packing_slip_paper_size'] ?? 'a4') === 'a4' ? 'selected' : '' }}>A4</option>
                            <option value="letter" {{ ($settings['packing_slip_paper_size'] ?? '') === 'letter' ? 'selected' : '' }}>US Letter</option>
                            <option value="thermal_4x6" {{ ($settings['packing_slip_paper_size'] ?? '') === 'thermal_4x6' ? 'selected' : '' }}>4" x 6" Thermal</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="packing_slip_logo_position" class="form-label">Logo Position</label>
                        <select class="form-select" id="packing_slip_logo_position" name="packing_slip_logo_position">
                            <option value="top_left" {{ ($settings['packing_slip_logo_position'] ?? 'top_left') === 'top_left' ? 'selected' : '' }}>Top Left</option>
                            <option value="top_center" {{ ($settings['packing_slip_logo_position'] ?? '') === 'top_center' ? 'selected' : '' }}>Top Center</option>
                            <option value="top_right" {{ ($settings['packing_slip_logo_position'] ?? '') === 'top_right' ? 'selected' : '' }}>Top Right</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_print_packing_slip" 
                               name="auto_print_packing_slip" {{ ($settings['auto_print_packing_slip'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_print_packing_slip">
                            <strong>Auto-print packing slips when order is processed</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="show_prices_on_packing_slip" 
                               name="show_prices_on_packing_slip" {{ ($settings['show_prices_on_packing_slip'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_prices_on_packing_slip">
                            <strong>Show prices on packing slips</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="packing_slip_footer_text" class="form-label">Footer Text (Optional)</label>
                    <textarea class="form-control" id="packing_slip_footer_text" name="packing_slip_footer_text" 
                              rows="2" placeholder="Thank you for your order!">{{ $settings['packing_slip_footer_text'] ?? '' }}</textarea>
                    <small class="text-muted">Custom message to appear at bottom of packing slips</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Invoice Settings -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-file-invoice text-info"></i> Invoice Settings
                </h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="invoice_prefix" class="form-label">Invoice Number Prefix</label>
                        <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" 
                               value="{{ $settings['invoice_prefix'] ?? 'MWF' }}" placeholder="MWF">
                        <small class="text-muted">e.g., MWF-2025-0001</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="invoice_start_number" class="form-label">Starting Invoice Number</label>
                        <input type="number" class="form-control" id="invoice_start_number" name="invoice_start_number" 
                               value="{{ $settings['invoice_start_number'] ?? '1' }}" min="1">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="invoice_paper_size" class="form-label">Paper Size</label>
                        <select class="form-select" id="invoice_paper_size" name="invoice_paper_size">
                            <option value="a4" {{ ($settings['invoice_paper_size'] ?? 'a4') === 'a4' ? 'selected' : '' }}>A4</option>
                            <option value="letter" {{ ($settings['invoice_paper_size'] ?? '') === 'letter' ? 'selected' : '' }}>US Letter</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="invoice_due_days" class="form-label">Payment Due (Days)</label>
                        <input type="number" class="form-control" id="invoice_due_days" name="invoice_due_days" 
                               value="{{ $settings['invoice_due_days'] ?? '30' }}" min="0">
                        <small class="text-muted">Default payment terms in days</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_email_invoice" 
                               name="auto_email_invoice" {{ ($settings['auto_email_invoice'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_email_invoice">
                            <strong>Auto-email invoices to customers</strong>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="invoice_footer_text" class="form-label">Invoice Footer</label>
                    <textarea class="form-control" id="invoice_footer_text" name="invoice_footer_text" 
                              rows="3" placeholder="Payment terms, bank details, etc.">{{ $settings['invoice_footer_text'] ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Document Templates -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-file-alt text-secondary"></i> Document Templates
                </h5>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Custom document templates can be created using Blade templates. 
                    Place template files in <code>resources/views/documents/</code>
                </div>
                
                <div class="mb-3">
                    <label for="custom_invoice_template" class="form-label">Custom Invoice Template</label>
                    <select class="form-select" id="custom_invoice_template" name="custom_invoice_template">
                        <option value="default" {{ ($settings['custom_invoice_template'] ?? 'default') === 'default' ? 'selected' : '' }}>Default Template</option>
                        <option value="modern" {{ ($settings['custom_invoice_template'] ?? '') === 'modern' ? 'selected' : '' }}>Modern Template</option>
                        <option value="classic" {{ ($settings['custom_invoice_template'] ?? '') === 'classic' ? 'selected' : '' }}>Classic Template</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="custom_packing_slip_template" class="form-label">Custom Packing Slip Template</label>
                    <select class="form-select" id="custom_packing_slip_template" name="custom_packing_slip_template">
                        <option value="default" {{ ($settings['custom_packing_slip_template'] ?? 'default') === 'default' ? 'selected' : '' }}>Default Template</option>
                        <option value="compact" {{ ($settings['custom_packing_slip_template'] ?? '') === 'compact' ? 'selected' : '' }}>Compact Template</option>
                        <option value="detailed" {{ ($settings['custom_packing_slip_template'] ?? '') === 'detailed' ? 'selected' : '' }}>Detailed Template</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
