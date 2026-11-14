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
        <main class="min-h-screen overflow-hidden">
            <header class="relative z-10 border-b border-slate-200/80 bg-white/90 backdrop-blur">
                <div class="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
                    <a href="{{ url('/') }}" class="flex items-center gap-3"><x-application-logo class="h-10 w-10 text-teal-600" /><span class="text-base font-semibold tracking-tight text-slate-950">SegHIS <span class="font-medium text-slate-400">Patient Portal</span></span></a>
                    @auth
                        <a class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" href="{{ route('dashboard') }}">Go to dashboard</a>
                    @else
                        <a class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" href="{{ route('login') }}">Sign in <span aria-hidden="true">&rarr;</span></a>
                    @endauth
                </div>
            </header>

            <section class="relative">
                <div class="absolute inset-x-0 top-0 h-[32rem] bg-gradient-to-br from-teal-100 via-slate-50 to-cyan-100/60" aria-hidden="true"></div>
                <div class="absolute -left-28 top-12 h-72 w-72 rounded-full bg-teal-300/20 blur-3xl" aria-hidden="true"></div>
                <div class="absolute -right-28 top-36 h-80 w-80 rounded-full bg-cyan-300/25 blur-3xl" aria-hidden="true"></div>

                <div class="relative mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[1.05fr_.95fr] lg:items-center lg:gap-16 lg:px-10 lg:py-24">
                    <div class="max-w-2xl">
                        <div class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-teal-800 shadow-sm"><span class="h-1.5 w-1.5 rounded-full bg-teal-500" aria-hidden="true"></span>Your care, in one place</div>
                        <h1 class="mt-6 text-4xl font-semibold tracking-[-0.04em] text-slate-950 sm:text-5xl lg:text-6xl">Stay connected to <span class="text-teal-700">your health.</span></h1>
                        <p class="mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">A simple, secure way to manage appointments, review your medical records, and keep your profile up to date.</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-700/20 transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" href="{{ route('login') }}">Sign in to portal <span aria-hidden="true">&rarr;</span></a>
                            @if (Route::has('register'))<a class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2" href="{{ route('register') }}">Create an account</a>@endif
                        </div>
                        <div class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium text-slate-600"><span class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs text-teal-800" aria-hidden="true">✓</span>Easy appointment tracking</span><span class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs text-teal-800" aria-hidden="true">✓</span>Private health records</span></div>
                    </div>

                    <div class="relative mx-auto w-full max-w-lg lg:max-w-none">
                        <div class="absolute -inset-4 rounded-[2rem] bg-white/40 blur-xl" aria-hidden="true"></div>
                        <div class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-slate-950 p-5 shadow-2xl shadow-slate-900/20 sm:p-7">
                            <div class="flex items-center justify-between"><div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-teal-400"></span><p class="text-sm font-semibold text-white">Your care overview</p></div><span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-slate-300">Secure portal</span></div>
                            <div class="mt-6 rounded-2xl bg-white p-5 sm:p-6"><p class="text-sm font-medium text-teal-700">Next appointment</p><div class="mt-4 flex items-center gap-4"><div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-950 text-white"><span class="text-[10px] font-semibold uppercase tracking-widest text-teal-300">Nov</span><span class="text-xl font-bold leading-none">14</span></div><div><p class="font-semibold text-slate-900">Care visit</p><p class="mt-1 text-sm text-slate-500">View details and manage your time</p></div></div></div>
                            <div class="mt-4 grid grid-cols-2 gap-4"><div class="rounded-2xl bg-white/10 p-4"><p class="text-xs font-medium text-slate-400">Appointments</p><p class="mt-2 text-2xl font-semibold text-white">01</p><p class="mt-1 text-xs text-teal-300">Upcoming visit</p></div><div class="rounded-2xl bg-white/10 p-4"><p class="text-xs font-medium text-slate-400">Health records</p><p class="mt-2 text-2xl font-semibold text-white">24</p><p class="mt-1 text-xs text-teal-300">Available anytime</p></div></div>
                            <div class="mt-4 rounded-2xl border border-teal-400/20 bg-teal-400/10 px-4 py-3"><p class="text-sm font-medium text-teal-100">Everything you need, without the paperwork.</p></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-200 bg-white"><div class="mx-auto grid max-w-7xl gap-6 px-5 py-10 sm:grid-cols-3 sm:px-8 lg:px-10"><div><p class="text-sm font-semibold text-slate-900">Appointments</p><p class="mt-1 text-sm leading-6 text-slate-500">Schedule and manage care visits with confidence.</p></div><div><p class="text-sm font-semibold text-slate-900">Medical records</p><p class="mt-1 text-sm leading-6 text-slate-500">Keep your health information organized and accessible.</p></div><div><p class="text-sm font-semibold text-slate-900">Built for patients</p><p class="mt-1 text-sm leading-6 text-slate-500">Clear, straightforward tools for your everyday care.</p></div></div></section>
        </main>
    </body>
</html>
