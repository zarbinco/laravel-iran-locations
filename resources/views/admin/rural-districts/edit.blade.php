@extends('iran-locations::admin.layout', ['title' => 'Edit rural district'])

@section('content')
    <h2 class="mb-4 text-lg font-semibold">Edit rural district</h2>
    <form method="POST" action="{{ route('iran-locations.admin.rural-districts.update', $ruralDistrict->getKey()) }}" class="rounded border border-slate-200 bg-white p-5">
        @csrf
        @method('PUT')
        @include('iran-locations::admin.rural-districts._form', ['ruralDistrict' => $ruralDistrict, 'provinces' => $provinces, 'counties' => $counties, 'officialDistricts' => $officialDistricts])
        <div class="mt-5 flex gap-2">
            <button class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Save</button>
            <a class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100" href="{{ route('iran-locations.admin.rural-districts.index') }}">Back</a>
        </div>
    </form>
    <form method="POST" action="{{ route('iran-locations.admin.rural-districts.destroy', $ruralDistrict->getKey()) }}" class="mt-4">
        @csrf
        @method('DELETE')
        <button class="rounded border border-rose-300 px-4 py-2 text-sm text-rose-700 hover:bg-rose-50">Delete or deprecate</button>
    </form>
@endsection
