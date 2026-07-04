<div class="mb-4">
    <label class="text-sm">
        <span class="mb-1 block font-medium text-slate-700">Province</span>
        <select class="w-full rounded border border-slate-300 px-3 py-2" name="province_id" required>
            <option value="">Select province</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->getKey() }}" @selected((string) old('province_id', $county->getAttribute('province_id')) === (string) $province->getKey())>{{ $province->getAttribute('name_fa') }} · {{ $province->getAttribute('code') }}</option>
            @endforeach
        </select>
    </label>
</div>
@include('iran-locations::admin.partials.location-fields', ['model' => $county])
