@extends('iran-locations::admin.layout', ['title' => 'Official Districts'])

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Official districts</h2>
        <a class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" href="{{ route('iran-locations.admin.official-districts.create') }}">Create official district</a>
    </div>

    <form method="GET" class="mb-4 grid gap-3 rounded border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        <label class="text-sm">
            <span class="mb-1 block font-medium text-slate-700">Search</span>
            <input class="w-full rounded border border-slate-300 px-3 py-2" name="q" value="{{ request('q') }}">
        </label>
        <label class="text-sm">
            <span class="mb-1 block font-medium text-slate-700">Province</span>
            <select class="w-full rounded border border-slate-300 px-3 py-2" name="province_id">
                <option value="">Any</option>
                @foreach ($provinces as $province)
                    <option value="{{ $province->getKey() }}" @selected((string) request('province_id') === (string) $province->getKey())>{{ $province->getAttribute('name_fa') }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="mb-1 block font-medium text-slate-700">County</span>
            <select class="w-full rounded border border-slate-300 px-3 py-2" name="county_id">
                <option value="">Any</option>
                @foreach ($counties as $county)
                    <option value="{{ $county->getKey() }}" @selected((string) request('county_id') === (string) $county->getKey())>{{ $county->getAttribute('name_fa') }}</option>
                @endforeach
            </select>
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
            <span class="mb-1 block font-medium text-slate-700">Sort</span>
            <select class="w-full rounded border border-slate-300 px-3 py-2" name="sort">
                @foreach (['name' => 'Name', '-name' => 'Name desc', 'county' => 'County', '-county' => 'County desc', 'code' => 'Code', '-code' => 'Code desc'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
            <a class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100" href="{{ url()->current() }}">Reset</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">County</th>
                    <th class="px-4 py-3">Province</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($officialDistricts as $officialDistrict)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $officialDistrict->getAttribute('name_fa') }}</td>
                        <td class="px-4 py-3">{{ $officialDistrict->getAttribute('code') }}</td>
                        <td class="px-4 py-3">{{ $officialDistrict->county?->getAttribute('name_fa') ?? $officialDistrict->getAttribute('county_id') }}</td>
                        <td class="px-4 py-3">{{ $officialDistrict->province?->getAttribute('name_fa') ?? $officialDistrict->getAttribute('province_id') }}</td>
                        <td class="px-4 py-3">{{ $officialDistrict->getAttribute('source') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-slate-700 underline" href="{{ route('iran-locations.admin.official-districts.edit', $officialDistrict->getKey()) }}">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $officialDistricts->links() }}</div>
@endsection
