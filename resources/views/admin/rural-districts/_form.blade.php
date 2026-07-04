<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Province</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="province_id" required>
            <option value="">Select province</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->getKey() }}" @selected((string) old('province_id', $ruralDistrict->getAttribute('province_id')) === (string) $province->getKey())>{{ $province->getAttribute('name_fa') }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">County</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="county_id" required>
            <option value="">Select county</option>
            @foreach ($counties as $county)
                <option value="{{ $county->getKey() }}" @selected((string) old('county_id', $ruralDistrict->getAttribute('county_id')) === (string) $county->getKey())>{{ $county->getAttribute('name_fa') }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Official district</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="official_district_id" required>
            <option value="">Select official district</option>
            @foreach ($officialDistricts as $officialDistrict)
                <option value="{{ $officialDistrict->getKey() }}" @selected((string) old('official_district_id', $ruralDistrict->getAttribute('official_district_id')) === (string) $officialDistrict->getKey())>{{ $officialDistrict->getAttribute('name_fa') }}</option>
            @endforeach
        </select>
    </label>
</div>
@include('iran-locations::admin.partials.location-fields', ['model' => $ruralDistrict])
