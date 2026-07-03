@extends('iran-locations::admin.layout', ['title' => 'Edit city'])

@section('content')
    <h2 class="mb-4 text-lg font-semibold">Edit city</h2>
    <form method="POST" action="{{ route('iran-locations.admin.cities.update', $city->getKey()) }}" class="rounded border border-slate-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('iran-locations::admin.cities._form', ['city' => $city, 'provinces' => $provinces])
        <div class="mt-5 flex gap-2">
            <button class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Save</button>
            <a class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100" href="{{ route('iran-locations.admin.cities.index') }}">Back</a>
        </div>
    </form>
    <form method="POST" action="{{ route('iran-locations.admin.cities.destroy', $city->getKey()) }}" class="mt-4">
        @csrf
        @method('DELETE')
        <button class="rounded border border-rose-300 px-4 py-2 text-sm text-rose-700 hover:bg-rose-50">Delete or deprecate</button>
    </form>
@endsection
