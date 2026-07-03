@extends('iran-locations::admin.layout', ['title' => 'Provinces'])

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Provinces</h2>
        <a class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" href="{{ route('iran-locations.admin.provinces.create') }}">Create province</a>
    </div>

    @include('iran-locations::admin.partials.filters')

    <div class="overflow-x-auto rounded border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($provinces as $province)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $province->getAttribute('name_fa') }}</td>
                        <td class="px-4 py-3">{{ $province->getAttribute('code') }}</td>
                        <td class="px-4 py-3">{{ $province->getAttribute('source') }}</td>
                        <td class="px-4 py-3">{{ $province->getAttribute('deprecated_at') ? 'deprecated' : ((bool) $province->getAttribute('is_active') ? 'active' : 'inactive') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-slate-700 underline" href="{{ route('iran-locations.admin.provinces.edit', $province->getKey()) }}">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $provinces->links() }}</div>
@endsection
