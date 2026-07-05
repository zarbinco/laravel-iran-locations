<div class="grid gap-4 sm:grid-cols-2">
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Code</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="code" value="{{ old('code', $model->getAttribute('code')) }}" required>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Persian name</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="name_fa" value="{{ old('name_fa', $model->getAttribute('name_fa')) }}" required>
    </label>
    @if (($includeEnglishName ?? true) === true)
        <label class="text-sm">
            <span class="mb-1 block font-medium text-slate-700">English name</span>
            <input class="w-full rounded border border-slate-300 px-3 py-2" name="name_en" value="{{ old('name_en', $model->getAttribute('name_en')) }}">
        </label>
    @endif
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Slug</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="slug" value="{{ old('slug', $model->getAttribute('slug')) }}">
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Display name override</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="display_name_fa" value="{{ old('display_name_fa', $model->getAttribute('display_name_fa')) }}">
    </label>
    @php
        $allowsPackageSource = (bool) config('iran-locations.data.allow_package_record_direct_edit', false);
        $currentSource = old('source', $model->getAttribute('source') ?: 'custom');
        $isPackageManaged = $model->exists && $model->getAttribute('source') === 'package';
    @endphp
    <div class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Source</span>
        @if ($allowsPackageSource)
            <select class="w-full rounded border border-slate-300 px-3 py-2" name="source">
                @foreach (['custom' => 'Custom', 'package' => 'Package'] as $value => $label)
                    <option value="{{ $value }}" @selected($currentSource === $value)>{{ $label }}</option>
                @endforeach
            </select>
        @elseif ($isPackageManaged)
            <div class="w-full rounded border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600">Package-managed</div>
        @else
            <input type="hidden" name="source" value="custom">
            <div class="w-full rounded border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600">Custom</div>
        @endif
    </div>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Source version</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="source_version" value="{{ old('source_version', $model->getAttribute('source_version')) }}">
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Data version</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="data_version" value="{{ old('data_version', $model->getAttribute('data_version')) }}">
    </label>
    <input type="hidden" name="is_active" value="0">
    <label class="flex items-center gap-2 text-sm">
        <input class="rounded border-slate-300" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $model->getAttribute('is_active') ?? true))>
        <span class="font-medium text-slate-700">Active</span>
    </label>
</div>
