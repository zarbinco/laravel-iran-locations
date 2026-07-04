<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Province</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="province_id" required>
            <option value="">Select province</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->getKey() }}" @selected((string) old('province_id', $city->getAttribute('province_id')) === (string) $province->getKey())>{{ $province->getAttribute('name_fa') }} · {{ $province->getAttribute('code') }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">County</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="county_id">
            <option value="">None</option>
            @foreach (($counties ?? []) as $county)
                <option value="{{ $county->getKey() }}" @selected((string) old('county_id', $city->getAttribute('county_id')) === (string) $county->getKey())>{{ $county->getAttribute('name_fa') }} · {{ $county->getAttribute('code') }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Official district</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="official_district_id">
            <option value="">None</option>
            @foreach (($officialDistricts ?? []) as $officialDistrict)
                <option value="{{ $officialDistrict->getKey() }}" @selected((string) old('official_district_id', $city->getAttribute('official_district_id')) === (string) $officialDistrict->getKey())>{{ $officialDistrict->getAttribute('name_fa') }} · {{ $officialDistrict->getAttribute('code') }}</option>
            @endforeach
        </select>
    </label>
</div>
@include('iran-locations::admin.partials.location-fields', ['model' => $city])
<div class="mt-4 grid gap-4 sm:grid-cols-3">
    <input type="hidden" name="is_province_capital" value="0">
    <label class="flex items-center gap-2 text-sm">
        <input class="rounded border-slate-300" type="checkbox" name="is_province_capital" value="1" @checked((bool) old('is_province_capital', $city->getAttribute('is_province_capital')))>
        <span class="font-medium text-slate-700">Province capital</span>
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Latitude</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="latitude" value="{{ old('latitude', $city->getAttribute('latitude')) }}">
    </label>
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Longitude</span>
        <input class="w-full rounded border border-slate-300 px-3 py-2" name="longitude" value="{{ old('longitude', $city->getAttribute('longitude')) }}">
    </label>
</div>
