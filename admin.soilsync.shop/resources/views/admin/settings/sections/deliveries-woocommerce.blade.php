{{-- WooCommerce API Status --}}
<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>WooCommerce Integration:</strong> 
            @if(config('services.woocommerce.url'))
                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Connected to {{ config('services.woocommerce.url') }}</span>
            @else
                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not configured</span>
            @endif
            <br><small class="text-muted">API keys are configured in .env file by system administrator</small>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-balance-scale"></i> Solidarity Pricing</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Control minimum and maximum solidarity pricing ranges across all products.
                </div>
                <div class="mb-3">
                    <label for="solidarity_min_percent" class="form-label">
                        <strong>Minimum Price %</strong> <small class="text-muted">(of recommended price)</small>
                    </label>
                    <input type="number" class="form-control" id="solidarity_min_percent" name="solidarity_min_percent" 
                           value="{{ $settings['solidarity_min_percent'] ?? 70 }}" min="0" max="100" step="1">
                    <div class="form-text">Default: 70% (customers pay at least 70% of recommended price)</div>
                </div>
                <div class="mb-3">
                    <label for="solidarity_max_percent" class="form-label">
                        <strong>Maximum Price %</strong> <small class="text-muted">(of recommended price)</small>
                    </label>
                    <input type="number" class="form-control" id="solidarity_max_percent" name="solidarity_max_percent" 
                           value="{{ $settings['solidarity_max_percent'] ?? 167 }}" min="100" max="500" step="1">
                    <div class="form-text">Default: 167% (customers can pay up to 167% to support others)</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delivery & Collection Settings --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-truck"></i> Delivery & Collection Settings</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enable_route_optimization" 
                               name="enable_route_optimization" value="1" 
                               {{ ($settings['enable_route_optimization'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_route_optimization">
                            <strong>Enable Route Optimization</strong>
                        </label>
                    </div>
                    <div class="form-text"><i class="fas fa-route"></i> Show route planning and optimization features</div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="delivery_time_slots" 
                               name="delivery_time_slots" value="1" 
                               {{ ($settings['delivery_time_slots'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="delivery_time_slots">
                            <strong>Delivery Time Slots</strong>
                        </label>
                    </div>
                    <div class="form-text"><i class="fas fa-clock"></i> Enable specific delivery time slot selection</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="delivery_cutoff_day" class="form-label"><strong>Delivery Cut-off Day</strong></label>
                        <select class="form-select" id="delivery_cutoff_day" name="delivery_cutoff_day">
                            @php
                                $deliveryCutoffDay = $settings['delivery_cutoff_day'] ?? 'Thursday';
                            @endphp
                            <option value="Monday" {{ $deliveryCutoffDay === 'Monday' ? 'selected' : '' }}>Monday</option>
                            <option value="Tuesday" {{ $deliveryCutoffDay === 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="Wednesday" {{ $deliveryCutoffDay === 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="Thursday" {{ $deliveryCutoffDay === 'Thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="Friday" {{ $deliveryCutoffDay === 'Friday' ? 'selected' : '' }}>Friday</option>
                            <option value="Saturday" {{ $deliveryCutoffDay === 'Saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="Sunday" {{ $deliveryCutoffDay === 'Sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="delivery_cutoff_time" class="form-label"><strong>Delivery Cut-off Time</strong></label>
                        <input type="time" class="form-control" id="delivery_cutoff_time" name="delivery_cutoff_time" 
                               value="{{ $settings['delivery_cutoff_time'] ?? '10:00' }}">
                    </div>
                </div>
                <div class="form-text mb-3">Customers joining after this day/time won't appear on this week's delivery schedule</div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="collection_cutoff_day" class="form-label"><strong>Collection Cut-off Day</strong></label>
                        <select class="form-select" id="collection_cutoff_day" name="collection_cutoff_day">
                            @php
                                $collectionCutoffDay = $settings['collection_cutoff_day'] ?? 'Friday';
                            @endphp
                            <option value="Monday" {{ $collectionCutoffDay === 'Monday' ? 'selected' : '' }}>Monday</option>
                            <option value="Tuesday" {{ $collectionCutoffDay === 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="Wednesday" {{ $collectionCutoffDay === 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="Thursday" {{ $collectionCutoffDay === 'Thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="Friday" {{ $collectionCutoffDay === 'Friday' ? 'selected' : '' }}>Friday</option>
                            <option value="Saturday" {{ $collectionCutoffDay === 'Saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="Sunday" {{ $collectionCutoffDay === 'Sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="collection_cutoff_time" class="form-label"><strong>Collection Cut-off Time</strong></label>
                        <input type="time" class="form-control" id="collection_cutoff_time" name="collection_cutoff_time" 
                               value="{{ $settings['collection_cutoff_time'] ?? '12:00' }}">
                    </div>
                </div>
                <div class="form-text mb-3">Customers joining after this day/time won't appear on this week's collection schedule</div>

                <div class="mb-3">
                    <label for="collection_reminder_hours" class="form-label"><strong>Collection Reminder (Hours Before)</strong></label>
                    <select class="form-select" id="collection_reminder_hours" name="collection_reminder_hours">
                        <option value="2" {{ ($settings['collection_reminder_hours'] ?? 24) == 2 ? 'selected' : '' }}>2 hours before</option>
                        <option value="6" {{ ($settings['collection_reminder_hours'] ?? 24) == 6 ? 'selected' : '' }}>6 hours before</option>
                        <option value="24" {{ ($settings['collection_reminder_hours'] ?? 24) == 24 ? 'selected' : '' }}>1 day before</option>
                        <option value="48" {{ ($settings['collection_reminder_hours'] ?? 24) == 48 ? 'selected' : '' }}>2 days before</option>
                        <option value="72" {{ ($settings['collection_reminder_hours'] ?? 24) == 72 ? 'selected' : '' }}>3 days before</option>
                    </select>
                    <div class="form-text">When to send collection reminder emails/notifications</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notification Settings --}}
    <div class="col-lg-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-envelope"></i> Notification Settings</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="email_notifications" 
                               name="email_notifications" value="1" 
                               {{ ($settings['email_notifications'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_notifications">
                            <strong>Email Notifications</strong>
                        </label>
                    </div>
                    <div class="form-text"><i class="fas fa-mail-bulk"></i> Send automated email notifications to customers</div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="sms_notifications" 
                               name="sms_notifications" value="1" 
                               {{ ($settings['sms_notifications'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sms_notifications">
                            <strong>SMS Notifications</strong>
                        </label>
                    </div>
                    <div class="form-text"><i class="fas fa-sms"></i> Send SMS notifications (requires Twilio setup in Communications section)</div>
                </div>

                <div class="alert alert-light mt-3">
                    <i class="fas fa-info-circle"></i> <strong>Coming Soon:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Webhook integrations</li>
                        <li>Slack/Discord notifications</li>
                        <li>Push notifications</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

