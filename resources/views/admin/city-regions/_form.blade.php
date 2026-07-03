<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <label class="text-sm sm:col-span-2"><span class="mb-1 block font-medium text-slate-700">City</span><select class="w-full rounded border border-slate-300 px-3 py-2" name="city_id" required><option value="">Select city</option>@foreach ($cities as $city)<option value="{{ $city->getKey() }}" @selected((string) old('city_id', $region->getAttribute('city_id')) === (string) $city->getKey())>{{ $city->getAttribute('name_fa') }} · {{ $city->getAttribute('code') }}</option>@endforeach</select></label>
    <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Number</span><input class="w-full rounded border border-slate-300 px-3 py-2" name="number" value="{{ old('number', $region->getAttribute('number')) }}"></label>
</div>
@include('iran-locations::admin.partials.location-fields', ['model' => $region])
<label class="mt-4 block text-sm"><span class="mb-1 block font-medium text-slate-700">Type</span><input class="w-full rounded border border-slate-300 px-3 py-2" name="type" value="{{ old('type', $region->getAttribute('type') ?: 'municipal_region') }}"></label>
