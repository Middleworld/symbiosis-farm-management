<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Seeding Date *</label>
            <input type="datetime-local" class="form-control" name="logs[seeding][date]"
                   value="{{ request('seeding_date', date('Y-m-d\TH:i')) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Completed</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="logs[seeding][done]" value="1">
                <label class="form-check-label">Mark as completed</label>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Location *</label>
    <input type="text" class="form-control" name="logs[seeding][location]"
           value="{{ request('bed_name', '') }}" required>
    <div class="form-text">Where does the seeding take place?</div>
</div>

<div class="mb-3">
    <label class="form-label">Quantity *</label>
    <div class="inline-quantity">
        <input type="number" class="form-control" name="logs[seeding][quantity][value]"
               value="{{ request('quantity', 100) }}" step="0.01" min="0" required>
        <select class="form-select" name="logs[seeding][quantity][units]">
            <option value="">Units</option>
            <option value="seeds" selected>Seeds</option>
            <option value="plants">Plants</option>
            <option value="grams">Grams</option>
            <option value="ounces">Ounces</option>
            <option value="pounds">Pounds</option>
            <option value="kilograms">Kilograms</option>
        </select>
        <select class="form-select" name="logs[seeding][quantity][measure]">
            <option value="count" selected>Count</option>
            <option value="weight">Weight</option>
            <option value="area">Area</option>
            <option value="volume">Volume</option>
            <option value="ratio">Ratio</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Notes</label>
    <input type="text" class="form-control" name="logs[seeding][notes]"
           value="AI-calculated seeding for succession #{{ request('succession_number', 1) }}">
</div>