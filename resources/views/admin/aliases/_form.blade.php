@php
    $currentType = old('location_type');
    if (! $currentType) {
        foreach ($locationTypes as $key => $label) {
            if ($alias->getAttribute('location_type') === config("iran-locations.models.{$key}")) {
                $currentType = $key;
            }
        }
    }
@endphp
@php
    $allowsPackageSource = (bool) config('iran-locations.data.allow_package_record_direct_edit', false);
    $currentSource = old('source', $alias->getAttribute('source') ?: 'custom');
    $isPackageManaged = $alias->exists && $alias->getAttribute('source') === 'package';
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Location type</span><select class="w-full rounded border border-slate-300 px-3 py-2" name="location_type" required><option value="">Select type</option>@foreach ($locationTypes as $value => $label)<option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Location ID</span><input class="w-full rounded border border-slate-300 px-3 py-2" name="location_id" value="{{ old('location_id', $alias->getAttribute('location_id')) }}" required></label>
    <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Alias</span><input class="w-full rounded border border-slate-300 px-3 py-2" name="alias" value="{{ old('alias', $alias->getAttribute('alias')) }}" required></label>
    <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Reason</span><input class="w-full rounded border border-slate-300 px-3 py-2" name="reason" value="{{ old('reason', $alias->getAttribute('reason')) }}"></label>
    <div class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Source</span>
        @if ($allowsPackageSource)
            <select class="w-full rounded border border-slate-300 px-3 py-2" name="source">@foreach (['custom' => 'Custom', 'package' => 'Package'] as $value => $label)<option value="{{ $value }}" @selected($currentSource === $value)>{{ $label }}</option>@endforeach</select>
        @elseif ($isPackageManaged)
            <div class="w-full rounded border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600">Package-managed</div>
        @else
            <input type="hidden" name="source" value="custom">
            <div class="w-full rounded border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600">Custom</div>
        @endif
    </div>
</div>
