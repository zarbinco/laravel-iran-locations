@extends('iran-locations::admin.layout', ['title' => 'Iran Locations Dashboard'])

@section('content')
    @php
        $contains = $manifest['contains'] ?? [];
        $synced = $latestAppliedVersion === $dataVersion;
        foreach ($datasets as $dataset) {
            if (($contains[$dataset] ?? false) === true && (($packageActiveCounts[$dataset] ?? null) !== ($manifest['counts'][$dataset] ?? null))) {
                $synced = false;
            }
        }
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded border border-slate-200 bg-white p-5 lg:col-span-2">
            <h2 class="text-lg font-semibold">Data status</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm text-slate-500">Package version</dt>
                    <dd class="mt-1 font-medium">{{ $dataVersion }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Latest applied</dt>
                    <dd class="mt-1 font-medium">{{ $latestAppliedVersion ?? 'none' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Synced</dt>
                    <dd class="mt-1 font-medium">{{ $synced ? 'yes' : 'no' }}</dd>
                </div>
            </dl>
            <div class="mt-5">
                <a class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" href="{{ route('iran-locations.admin.data.index') }}">Open data status</a>
            </div>
        </section>

        <section class="rounded border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Manage</h2>
            <div class="mt-4 grid gap-2 text-sm">
                <a class="rounded border border-slate-300 px-3 py-2 hover:bg-slate-50" href="{{ route('iran-locations.admin.provinces.index') }}">Provinces</a>
                <a class="rounded border border-slate-300 px-3 py-2 hover:bg-slate-50" href="{{ route('iran-locations.admin.cities.index') }}">Cities</a>
                <a class="rounded border border-slate-300 px-3 py-2 hover:bg-slate-50" href="{{ route('iran-locations.admin.city-regions.index') }}">City regions</a>
                <a class="rounded border border-slate-300 px-3 py-2 hover:bg-slate-50" href="{{ route('iran-locations.admin.city-areas.index') }}">City areas</a>
                <a class="rounded border border-slate-300 px-3 py-2 hover:bg-slate-50" href="{{ route('iran-locations.admin.neighborhoods.index') }}">Neighborhoods</a>
                <a class="rounded border border-slate-300 px-3 py-2 hover:bg-slate-50" href="{{ route('iran-locations.admin.aliases.index') }}">Aliases</a>
            </div>
        </section>
    </div>

    <section class="mt-6 rounded border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Package counts</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-600">
                        <th class="py-2 pr-4">Dataset</th>
                        <th class="py-2 pr-4">Package</th>
                        <th class="py-2 pr-4">Database</th>
                        <th class="py-2 pr-4">Package active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($datasets as $dataset)
                        <tr>
                            <td class="py-2 pr-4 font-medium">{{ $dataset }}</td>
                            <td class="py-2 pr-4">{{ $manifest['counts'][$dataset] ?? 0 }}</td>
                            <td class="py-2 pr-4">{{ $databaseCounts[$dataset] ?? 'missing' }}</td>
                            <td class="py-2 pr-4">{{ $packageActiveCounts[$dataset] ?? 'missing' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
