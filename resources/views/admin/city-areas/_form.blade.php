<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <label class="text-sm sm:col-span-2"><span class="mb-1 block font-medium text-slate-700">Region</span><select class="w-full rounded border border-slate-300 px-3 py-2" name="city_region_id" required><option value="">Select region</option>@foreach ($regions as $region)<option value="{{ $region->getKey() }}" @selected((string) old('city_region_id', $area->getAttribute('city_region_id')) === (string) $region->getKey())>{{ $region->getAttribute('name_fa') }} · {{ $region->getAttribute('code') }}</option>@endforeach</select></label>
    <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Number</span><input class="w-full rounded border border-slate-300 px-3 py-2" name="number" value="{{ old('number', $area->getAttribute('number')) }}"></label>
</div>
@include('iran-locations::admin.partials.location-fields', ['model' => $area, 'includeEnglishName' => false])
