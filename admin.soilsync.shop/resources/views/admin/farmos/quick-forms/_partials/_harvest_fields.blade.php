<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Harvest Date *</label>
            <input type="datetime-local" class="form-control" name="logs[harvest][date]"
                   value="{{ request('harvest_date', '') }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Completed</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="logs[harvest][done]" value="1">
                <label class="form-check-label">Mark as completed</label>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Quantity *</label>
    <div class="inline-quantity">
        <input type="number" class="form-control" name="logs[harvest][quantity][value]"
               value="{{ request('quantity', 100) }}" step="0.01" min="0" required>
        <select class="form-select" name="logs[harvest][quantity][units]">
            <option value="">Units</option>
            <option value="grams" selected>Grams</option>
            <option value="ounces">Ounces</option>
            <option value="pounds">Pounds</option>
            <option value="kilograms">Kilograms</option>
            <option value="plants">Plants</option>
            <option value="seeds">Seeds</option>
        </select>
        <select class="form-select" name="logs[harvest][quantity][measure]">
            <option value="weight" selected>Weight</option>
            <option value="count">Count</option>
            <option value="area">Area</option>
            <option value="volume">Volume</option>
            <option value="ratio">Ratio</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Notes</label>
    <input type="text" class="form-control" name="logs[harvest][notes]"
           value="AI-calculated harvest for succession #{{ request('succession_number', 1) }}">
</div>