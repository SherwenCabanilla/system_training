<aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-slate-800 bg-slate-950 px-5 py-6 text-white lg:flex">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2">
        <x-application-logo class="h-11 w-11 text-teal-400" />
        <span class="text-lg font-semibold tracking-tight">SegHIS <span class="block text-xs font-normal tracking-normal text-slate-400">Patient Portal</span></span>
    </a>

    <nav class="mt-10 space-y-1" aria-label="Primary navigation">
        <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('dashboard') }}"><span class="text-base">⌂</span> Overview</a>
        <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('appointments.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('appointments.index') }}"><span class="text-base">⌚</span> Appointments</a>
        <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('medical-records.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('medical-records.index') }}"><span class="text-base">▣</span> Medical records</a>
        <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('notifications.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('notifications.index') }}"><span class="text-base">✦</span> Notifications</a>
    </nav>

    <div class="mt-auto rounded-2xl border border-white/10 bg-white/5 p-4">
        <a href="{{ route('profile.edit') }}" class="block"><p class="text-sm font-semibold">{{ Auth::user()->name }}</p><p class="mt-1 text-xs text-slate-400">Manage profile →</p></a>
        <form class="mt-4" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-white/10" type="submit">Log out</button></form>
    </div>
</aside>

<nav class="border-b border-slate-800 bg-slate-950 px-4 py-3 text-white lg:hidden">
    <div class="flex items-center justify-between gap-3"><a href="{{ route('dashboard') }}" class="flex items-center gap-2"><x-application-logo class="h-9 w-9 text-teal-400" /><span class="text-sm font-semibold">SegHIS Portal</span></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-200" type="submit">Log out</button></form></div>
    <div class="mt-3 flex gap-1 overflow-x-auto text-xs font-semibold"><a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('dashboard') }}">Overview</a><a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('appointments.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('appointments.index') }}">Appointments</a><a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('medical-records.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('medical-records.index') }}">Records</a><a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('notifications.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('notifications.index') }}">Alerts</a></div>
</nav>
