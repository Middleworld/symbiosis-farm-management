@extends('layouts.admin')

@section('title', 'Customer Funds Details')

@section('styles')
<style>
.customer-detail-card {
    transition: transform 0.2s;
}
.customer-detail-card:hover {
    transform: translateY(-2px);
}
.balance-display {
    font-size: 2.5rem;
    font-weight: bold;
    color: #28a745;
}
.transaction-badge {
    font-size: 0.75rem;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if(isset($error))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                </div>
            @else
                <!-- Customer Header -->
                <div class="card customer-detail-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-user"></i> {{ $customer['name'] }}
                                </h3>
                                <p class="text-muted mb-0">{{ $customer['email'] }}</p>
                            </div>
                            <div class="text-right">
                                <a href="{{ route('admin.funds.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Funds
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Customer Information</h4>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Customer ID:</strong></td>
                                        <td>{{ $customer['id'] }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td>{{ $customer['name'] }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>{{ $customer['email'] }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Member Since:</strong></td>
                                        <td>{{ $customer['date_created'] ? \Carbon\Carbon::parse($customer['date_created'])->format('M j, Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td>{{ $customer['date_modified'] ? \Carbon\Carbon::parse($customer['date_modified'])->format('M j, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Orders:</strong></td>
                                        <td>{{ $customer['total_orders'] ?? 0 }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Funds Balance</h4>
                                <div class="text-center">
                                    <div class="balance-display">
                                        £{{ number_format($customer['balance'], 2) }}
                                    </div>
                                    <p class="text-muted">Current Store Credit Balance</p>
                                </div>

                                @if(session('admin_user')['role'] === 'super_admin')
                                    <div class="mt-3">
                                        <button class="btn btn-success btn-sm me-2" onclick="showAddFundsModal()">
                                            <i class="fas fa-plus"></i> Add Store Credit
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="showDeductFundsModal()">
                                            <i class="fas fa-minus"></i> Deduct Credit
                                        </button>
                                    </div>
                                @else
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-lock"></i> Fund management requires Super Admin access
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing & Shipping Address -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-map-marker-alt"></i> Billing Address
                                </h5>
                            </div>
                            <div class="card-body">
                                @if(!empty($customer['billing']))
                                    <address>
                                        {{ $customer['billing']['first_name'] ?? '' }} {{ $customer['billing']['last_name'] ?? '' }}<br>
                                        {{ $customer['billing']['company'] ?? '' }}<br>
                                        {{ $customer['billing']['address_1'] ?? '' }}<br>
                                        {{ $customer['billing']['address_2'] ?? '' }}<br>
                                        {{ $customer['billing']['city'] ?? '' }}, {{ $customer['billing']['state'] ?? '' }} {{ $customer['billing']['postcode'] ?? '' }}<br>
                                        {{ $customer['billing']['country'] ?? '' }}<br>
                                        {{ $customer['billing']['phone'] ?? '' }}
                                    </address>
                                @else
                                    <p class="text-muted">No billing address on file.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-truck"></i> Shipping Address
                                </h5>
                            </div>
                            <div class="card-body">
                                @if(!empty($customer['shipping']))
                                    <address>
                                        {{ $customer['shipping']['first_name'] ?? '' }} {{ $customer['shipping']['last_name'] ?? '' }}<br>
                                        {{ $customer['shipping']['company'] ?? '' }}<br>
                                        {{ $customer['shipping']['address_1'] ?? '' }}<br>
                                        {{ $customer['shipping']['address_2'] ?? '' }}<br>
                                        {{ $customer['shipping']['city'] ?? '' }}, {{ $customer['shipping']['state'] ?? '' }} {{ $customer['shipping']['postcode'] ?? '' }}<br>
                                        {{ $customer['shipping']['country'] ?? '' }}
                                    </address>
                                @else
                                    <p class="text-muted">No shipping address on file.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Transaction History
                                </h5>
                            </div>
                            <div class="card-body">
                                @if(!empty($customer['transactions']))
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Description</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer['transactions'] as $transaction)
                                                    <tr>
                                                        <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('M j, Y H:i') }}</td>
                                                        <td>
                                                            <span class="badge {{ $transaction['type'] === 'deposit' ? 'bg-success' : 'bg-warning' }} transaction-badge">
                                                                {{ ucfirst($transaction['type']) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold {{ $transaction['type'] === 'deposit' ? 'text-success' : 'text-danger' }}">
                                                                {{ $transaction['type'] === 'deposit' ? '+' : '-' }}£{{ number_format($transaction['amount'], 2) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if(isset($transaction['order_id']))
                                                                <a href="{{ route('admin.orders.show', $transaction['order_id']) }}" 
                                                                   class="text-decoration-none" 
                                                                   target="_blank" 
                                                                   title="View Order #{{ $transaction['order_id'] }}">
                                                                    Order #{{ $transaction['order_id'] }}
                                                                </a>
                                                            @else
                                                                {{ $transaction['description'] }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">{{ ucfirst($transaction['status'] ?? 'completed') }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No transactions found</h5>
                                        <p class="text-muted">This customer hasn't made any purchases yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function showAddFundsModal() {
    showAdjustModal('add');
}

function showDeductFundsModal() {
    showAdjustModal('subtract');
}

function showAdjustModal(action) {
    const actionText = action === 'add' ? 'Add' : 'Deduct';
    const actionColor = action === 'add' ? 'success' : 'warning';
    const icon = action === 'add' ? 'plus' : 'minus';

    // Create modal HTML
    const modalHtml = `
        <div class="modal fade" id="adjustFundsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-${icon}"></i>
                            ${actionText} Store Credit
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="adjustFundsForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (£)</label>
                                <input type="number" class="form-control" id="amount" name="amount"
                                       min="0.01" max="10000" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason (Optional)</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3"
                                          placeholder="Enter reason for adjustment..."></textarea>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Current balance: £{{ number_format($customer['balance'], 2) }}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-${actionColor}" id="adjustFundsBtn">
                                <i class="fas fa-${icon}"></i>
                                ${actionText} Credit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if present
    const existingModal = document.getElementById('adjustFundsModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('adjustFundsModal'));
    modal.show();

    // Handle form submission
    document.getElementById('adjustFundsForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('adjustFundsBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        const formData = new FormData(this);
        formData.append('action', action);

        try {
            const response = await fetch('{{ route("admin.funds.customers.adjust-balance", $customer["id"]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Close modal
                modal.hide();

                // Show success message
                showAlert('success', result.message || 'Funds adjusted successfully!');

                // Update balance display
                if (result.new_balance !== undefined) {
                    const balanceElement = document.querySelector('.balance-display');
                    if (balanceElement) {
                        balanceElement.textContent = '£' + parseFloat(result.new_balance).toFixed(2);
                    }
                }

                // Reload page after short delay to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 2000);

            } else {
                showAlert('danger', result.message || 'Failed to adjust funds');
            }

        } catch (error) {
            console.error('Error adjusting funds:', error);
            showAlert('danger', 'An error occurred while adjusting funds');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Insert at top of container
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
@endsection