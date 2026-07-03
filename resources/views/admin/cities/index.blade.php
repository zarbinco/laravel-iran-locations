@extends('iran-locations::admin.layout', ['title' => 'Cities'])

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Cities</h2>
        <a class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" href="{{ route('iran-locations.admin.cities.create') }}">Create city</a>
    </div>

    @include('iran-locations::admin.partials.filters', ['sorts' => ['name' => 'Name', '-name' => 'Name desc', 'province' => 'Province', '-province' => 'Province desc', 'code' => 'Code', '-code' => 'Code desc']])

    <div class="overflow-x-auto rounded border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Province</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($cities as $city)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $city->getAttribute('name_fa') }}</td>
                        <td class="px-4 py-3">{{ $city->getAttribute('code') }}</td>
                        <td class="px-4 py-3">{{ $city->getAttribute('province_id') }}</td>
                        <td class="px-4 py-3">{{ $city->getAttribute('source') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-slate-700 underline" href="{{ route('iran-locations.admin.cities.edit', $city->getKey()) }}">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $cities->links() }}</div>
@endsection
