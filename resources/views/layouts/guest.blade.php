<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <div class="min-h-screen px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto flex w-full max-w-md flex-col items-center">
                <a href="{{ url('/') }}" class="flex items-center gap-3 text-slate-900">
                    <x-application-logo class="h-11 w-11 text-teal-600" />
                    <span class="text-lg font-semibold tracking-tight">SegHIS <span class="font-normal text-slate-500">Patient Portal</span></span>
                </a>

                <div class="mt-8 w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs leading-5 text-slate-500">For demonstration only. This portal uses sample patient information.</p>
            </div>
        </div>
    </body>
</html>
