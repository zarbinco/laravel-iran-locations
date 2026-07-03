<header class="border-b border-slate-200 pb-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Iran Locations</h1>
            <p class="mt-1 text-sm text-slate-600">Package data administration</p>
        </div>
        <nav class="flex flex-wrap gap-2 text-sm">
            <a class="rounded border border-slate-300 bg-white px-3 py-2 hover:bg-slate-100" href="{{ route('iran-locations.admin.dashboard') }}">Dashboard</a>
            <a class="rounded border border-slate-300 bg-white px-3 py-2 hover:bg-slate-100" href="{{ route('iran-locations.admin.data.index') }}">Data</a>
            <a class="rounded border border-slate-300 bg-white px-3 py-2 hover:bg-slate-100" href="{{ route('iran-locations.admin.provinces.index') }}">Provinces</a>
            <a class="rounded border border-slate-300 bg-white px-3 py-2 hover:bg-slate-100" href="{{ route('iran-locations.admin.cities.index') }}">Cities</a>
            <a class="rounded border border-slate-300 bg-white px-3 py-2 hover:bg-slate-100" href="{{ route('iran-locations.admin.neighborhoods.index') }}">Neighborhoods</a>
        </nav>
    </div>
</header>
