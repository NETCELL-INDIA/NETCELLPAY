<form method="get" action="{{ $action }}" class="row gy-2 align-items-end mb-3">
    <div class="col-md-2">
        <label class="form-label mb-0" for="from">From</label>
        <input class="form-control" type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label mb-0" for="to">To</label>
        <input class="form-control" type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label mb-0" for="category">Category</label>
        <select class="form-select" id="category" name="category">
            <option value="">All</option>
            @foreach($categories as $category)
            <option value="{{ $category->code }}" @selected(($filters['category'] ?? '') === $category->code)>{{ $category->label }}</option>
            @endforeach
        </select>
    </div>
    @if(!empty($showPeriod))
    <div class="col-md-2">
        <label class="form-label mb-0" for="period">Period</label>
        <select class="form-select" id="period" name="period">
            <option value="">Custom</option>
            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
            <option value="{{ $value }}" @selected(($filters['period'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-md-2">
        <button class="btn btn-secondary w-100" type="submit">Filter</button>
    </div>
</form>
