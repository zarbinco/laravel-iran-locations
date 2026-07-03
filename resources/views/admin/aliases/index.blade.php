@extends('iran-locations::admin.layout', ['title' => 'Aliases'])

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Aliases</h2>
        <a class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" href="{{ route('iran-locations.admin.aliases.create') }}">Create alias</a>
    </div>

    <form method="GET" class="mb-4 grid gap-3 rounded border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Search</span><input class="w-full rounded border border-slate-300 px-3 py-2" name="q" value="{{ request('q') }}"></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Source</span><select class="w-full rounded border border-slate-300 px-3 py-2" name="source">@foreach (['' => 'Any', 'package' => 'Package', 'custom' => 'Custom', 'all' => 'All'] as $value => $label)<option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Location type</span><select class="w-full rounded border border-slate-300 px-3 py-2" name="location_type"><option value="">Any</option>@foreach ($locationTypes as $value => $label)<option value="{{ $value }}" @selected(request('location_type') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Sort</span><select class="w-full rounded border border-slate-300 px-3 py-2" name="sort">@foreach (['alias' => 'Alias', '-alias' => 'Alias desc', 'source' => 'Source', '-source' => 'Source desc'] as $value => $label)<option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>@endforeach</select></label>
        <div class="flex items-end gap-2"><button class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button><a class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100" href="{{ url()->current() }}">Reset</a></div>
    </form>

    <div class="overflow-x-auto rounded border border-slate-200 bg-white"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-slate-600"><th class="px-4 py-3">Alias</th><th class="px-4 py-3">Target</th><th class="px-4 py-3">Source</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($aliases as $alias)<tr><td class="px-4 py-3 font-medium">{{ $alias->getAttribute('alias') }}</td><td class="px-4 py-3">{{ class_basename($alias->getAttribute('location_type')) }} #{{ $alias->getAttribute('location_id') }}</td><td class="px-4 py-3">{{ $alias->getAttribute('source') }}</td><td class="px-4 py-3 text-right"><a class="text-slate-700 underline" href="{{ route('iran-locations.admin.aliases.edit', $alias->getKey()) }}">Edit</a></td></tr>@endforeach</tbody></table></div>
    <div class="mt-4">{{ $aliases->links() }}</div>
@endsection
