@extends('iran-locations::admin.layout', ['title' => 'Neighborhoods'])

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Neighborhoods</h2>
        <a class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" href="{{ route('iran-locations.admin.neighborhoods.create') }}">Create neighborhood</a>
    </div>

    @include('iran-locations::admin.partials.filters', ['sorts' => ['name' => 'Name', '-name' => 'Name desc', 'city' => 'City', '-city' => 'City desc', 'region' => 'Region', '-region' => 'Region desc', 'type' => 'Type', '-type' => 'Type desc']])

    <div class="overflow-x-auto rounded border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead><tr class="text-left text-slate-600"><th class="px-4 py-3">Name</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Source</th><th class="px-4 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($neighborhoods as $neighborhood)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $neighborhood->getAttribute('name_fa') }}</td>
                        <td class="px-4 py-3">{{ $neighborhood->getAttribute('type') }}</td>
                        <td class="px-4 py-3">{{ $neighborhood->getAttribute('code') }}</td>
                        <td class="px-4 py-3">{{ $neighborhood->getAttribute('source') }}</td>
                        <td class="px-4 py-3 text-right"><a class="text-slate-700 underline" href="{{ route('iran-locations.admin.neighborhoods.edit', $neighborhood->getKey()) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $neighborhoods->links() }}</div>
@endsection
