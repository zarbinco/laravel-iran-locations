@extends('iran-locations::admin.layout', ['title' => 'Iran Locations Data'])

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

    <section class="rounded border border-slate-200 bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Data status</h2>
                <p class="mt-1 text-sm text-slate-600">Package version {{ $dataVersion }} · synced {{ $synced ? 'yes' : 'no' }}</p>
                <p class="mt-1 text-sm text-slate-600">Checksum {{ $manifest['checksum'] ?? '' }}</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('iran-locations.admin.data.sync') }}">
                    @csrf
                    <input type="hidden" name="dry_run" value="1">
                    <button class="rounded border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-100">Dry-run sync</button>
                </form>
                <form method="POST" action="{{ route('iran-locations.admin.data.sync') }}">
                    @csrf
                    <button class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Apply sync</button>
                </form>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-600">
                        <th class="py-2 pr-4">Dataset</th>
                        <th class="py-2 pr-4">Package</th>
                        <th class="py-2 pr-4">Database</th>
                        <th class="py-2 pr-4">Package active</th>
                        <th class="py-2 pr-4">Authoritative</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($datasets as $dataset)
                        <tr>
                            <td class="py-2 pr-4 font-medium">{{ $dataset }}</td>
                            <td class="py-2 pr-4">{{ $manifest['counts'][$dataset] ?? 0 }}</td>
                            <td class="py-2 pr-4">{{ $databaseCounts[$dataset] ?? 'missing' }}</td>
                            <td class="py-2 pr-4">{{ $packageActiveCounts[$dataset] ?? 'missing' }}</td>
                            <td class="py-2 pr-4">{{ ($contains[$dataset] ?? false) ? 'yes' : 'no' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if (is_array($syncResult))
        <section class="mt-6 rounded border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Sync result</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-slate-600">
                            <th class="py-2 pr-4">Dataset</th>
                            <th class="py-2 pr-4">Created</th>
                            <th class="py-2 pr-4">Updated</th>
                            <th class="py-2 pr-4">Unchanged</th>
                            <th class="py-2 pr-4">Deprecated</th>
                            <th class="py-2 pr-4">Skipped</th>
                            <th class="py-2 pr-4">Failed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (($syncResult['datasets'] ?? []) as $dataset => $totals)
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $dataset }}</td>
                                <td class="py-2 pr-4">{{ $totals['created'] ?? 0 }}</td>
                                <td class="py-2 pr-4">{{ $totals['updated'] ?? 0 }}</td>
                                <td class="py-2 pr-4">{{ $totals['unchanged'] ?? 0 }}</td>
                                <td class="py-2 pr-4">{{ $totals['deprecated'] ?? 0 }}</td>
                                <td class="py-2 pr-4">{{ $totals['skipped'] ?? 0 }}</td>
                                <td class="py-2 pr-4">{{ $totals['failed'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
