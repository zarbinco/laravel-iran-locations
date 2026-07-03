<form method="GET" class="mb-4 grid gap-3 rounded border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Search</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="q" value="{{ request('q') }}">
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Status</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="status">
            @foreach (['' => 'Any', 'active' => 'Active', 'inactive' => 'Inactive', 'deprecated' => 'Deprecated', 'all' => 'All'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Source</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="source">
            @foreach (['' => 'Any', 'package' => 'Package', 'custom' => 'Custom', 'all' => 'All'] as $value => $label)
                <option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Code</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="code" value="{{ request('code') }}">
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Sort</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="sort">
            @foreach (($sorts ?? ['name' => 'Name', '-name' => 'Name desc', 'code' => 'Code', '-code' => 'Code desc', 'updated_at' => 'Updated', '-updated_at' => 'Updated desc']) as $value => $label)
                <option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <div class="flex items-end gap-2">
        <button class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
        <a class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100" href="{{ url()->current() }}">Reset</a>
    </div>
</form>
