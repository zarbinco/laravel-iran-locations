<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Iran Locations Admin' }}</title>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @include('iran-locations::admin.partials.nav')
        @include('iran-locations::admin.partials.flash')
        @include('iran-locations::admin.partials.errors')

        <main class="mt-6">
            @yield('content')
        </main>
    </div>
</body>
</html>
