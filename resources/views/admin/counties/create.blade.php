@extends('iran-locations::admin.layout', ['title' => 'Create county'])

@section('content')
    <h2 class="mb-4 text-lg font-semibold">Create county</h2>
    <form method="POST" action="{{ route('iran-locations.admin.counties.store') }}" class="rounded border border-slate-200 bg-white p-5">
        @csrf
        @include('iran-locations::admin.counties._form', ['county' => $county, 'provinces' => $provinces])
        <div class="mt-5 flex gap-2">
            <button class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Create</button>
            <a class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100" href="{{ route('iran-locations.admin.counties.index') }}">Cancel</a>
        </div>
    </form>
@endsection
