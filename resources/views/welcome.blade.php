<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <main class="min-h-screen">
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-2.5"><x-application-logo class="h-9 w-9 text-teal-600" /><span class="text-base font-semibold tracking-tight">SegHIS <span class="font-normal text-slate-500">Patient Portal</span></span></div>
                    @auth
                        <a class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800" href="{{ route('dashboard') }}">Go to dashboard</a>
                    @else
                        <a class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800" href="{{ route('login') }}">Sign in</a>
                    @endauth
                </div>
            </header>

            <section class="mx-auto grid max-w-6xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[1.1fr_.9fr] lg:items-center lg:px-8">
                <div>
                    <p class="text-sm font-semibold text-teal-700">Simplified patient care access</p>
                    <h1 class="mt-4 max-w-xl text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Your health information, in one secure place.</h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">Use the SegHIS Patient Portal to manage your profile, view medical records, and keep up with appointments.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a class="rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-800" href="{{ route('login') }}">Sign in to portal</a>
                        @if (Route::has('register'))<a class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="{{ route('register') }}">Create account</a>@endif
                    </div>
                </div>
                <div class="rounded-3xl border border-teal-100 bg-teal-50 p-6 shadow-sm sm:p-8">
                    <div class="rounded-2xl bg-white p-6 shadow-sm"><p class="text-sm font-semibold text-slate-900">Portal features</p><div class="mt-5 space-y-4 text-sm text-slate-600"><div class="flex gap-3"><span class="font-bold text-teal-700">01</span><span>Secure account and profile management</span></div><div class="flex gap-3"><span class="font-bold text-teal-700">02</span><span>Appointment scheduling and reminders</span></div><div class="flex gap-3"><span class="font-bold text-teal-700">03</span><span>Sample medical history and results</span></div></div></div>
                    <p class="mt-5 text-xs leading-5 text-slate-500">Prototype environment: all information shown is for demonstration purposes only.</p>
                </div>
            </section>
        </main>
    </body>
</html>
