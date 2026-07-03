@if (session('status'))
    <div class="mt-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
@endif
