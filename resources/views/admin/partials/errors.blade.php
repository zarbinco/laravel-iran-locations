@if ($errors->any())
    <div class="mt-4 rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <p class="font-medium">Please review the highlighted fields.</p>
        <ul class="mt-2 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
