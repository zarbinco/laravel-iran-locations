<div class="mb-4 grid gap-4 sm:grid-cols-2">
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Province</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="province_id" required>
            <option value="">Select province</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->getKey() }}" @selected((string) old('province_id', $officialDistrict->getAttribute('province_id')) === (string) $province->getKey())>{{ $province->getAttribute('name_fa') }} · {{ $province->getAttribute('code') }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">County</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="county_id" required>
            <option value="">Select county</option>
            @foreach ($counties as $county)
                <option value="{{ $county->getKey() }}" @selected((string) old('county_id', $officialDistrict->getAttribute('county_id')) === (string) $county->getKey())>{{ $county->getAttribute('name_fa') }} · {{ $county->getAttribute('code') }}</option>
            @endforeach
        </select>
    </label>
</div>
@include('iran-locations::admin.partials.location-fields', ['model' => $officialDistrict])
