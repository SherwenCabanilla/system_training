@php
    $isStaff = Auth::user()->is_staff;
    $staffLinks = ['staff.dashboard' => 'Today', 'staff.requests' => 'Assignment requests', 'staff.upcoming' => 'Upcoming', 'staff.history' => 'Visit history'];
    $homeRoute = $isStaff ? route('staff.dashboard') : route('dashboard');
@endphp

<aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-slate-800 bg-slate-950 px-5 py-6 text-white lg:flex">
    <a href="{{ $homeRoute }}" class="flex items-center gap-3 px-2"><x-application-logo class="h-11 w-11 text-teal-400" /><span class="text-lg font-semibold tracking-tight">SegHIS <span class="block text-xs font-normal tracking-normal text-slate-400">{{ $isStaff ? 'Staff Workspace' : 'Patient Portal' }}</span></span></a>
    <nav class="mt-10 space-y-1" aria-label="Primary navigation">
        @if ($isStaff)
            @foreach ($staffLinks as $routeName => $label)
                <a aria-current="{{ request()->routeIs($routeName) ? 'page' : 'false' }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs($routeName) ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route($routeName) }}">{{ $label }}</a>
            @endforeach
        @else
            <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('dashboard') }}"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-8h6v8" /></svg> Overview</a>
            <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('appointments.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('appointments.index') }}"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2M8 18h2" /></svg> Appointments</a>
            <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('medical-records.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('medical-records.index') }}"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2" /><path d="M9 8h6M9 12h6M9 16h4" /></svg> Medical records</a>
            <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('notifications.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}" href="{{ route('notifications.index') }}"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" /></svg> Notifications
                    <span data-notification-count class="{{ $unreadNotificationCount > 0 ? '' : 'hidden' }} ml-auto inline-flex min-w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold tabular-nums text-white">
                        <span data-notification-number aria-hidden="true">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                        <span data-notification-label class="sr-only">{{ $unreadNotificationCount > 0 ? $unreadNotificationCount.' unread notifications' : '' }}</span>
                    </span>
            </a>
        @endif
    </nav>
    <div class="mt-auto rounded-2xl border border-white/10 bg-white/5 p-4"><a href="{{ route('profile.edit') }}" class="block"><p class="text-sm font-semibold">{{ Auth::user()->name }}</p><p class="mt-1 text-xs text-slate-400">{{ $isStaff ? 'Staff account' : 'Manage profile' }} &rarr;</p></a><form data-confirm-logout class="mt-4" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-white/10" type="submit">Log out</button></form></div>
</aside>

<nav class="border-b border-slate-800 bg-slate-950 px-4 py-3 text-white lg:hidden">
    <div class="flex items-center justify-between gap-3"><a href="{{ $homeRoute }}" class="flex items-center gap-2"><x-application-logo class="h-9 w-9 text-teal-400" /><span class="text-sm font-semibold">SegHIS {{ $isStaff ? 'Staff' : 'Portal' }}</span></a><form data-confirm-logout method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-200" type="submit">Log out</button></form></div>
    <div class="mt-3 flex gap-1 overflow-x-auto text-xs font-semibold">
        @if ($isStaff)
            @foreach ($staffLinks as $routeName => $label)
                <a aria-current="{{ request()->routeIs($routeName) ? 'page' : 'false' }}" class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs($routeName) ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route($routeName) }}">{{ $label }}</a>
            @endforeach
        @else
            <a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('dashboard') }}">Overview</a><a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('appointments.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('appointments.index') }}">Appointments</a><a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('medical-records.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('medical-records.index') }}">Records</a><a class="whitespace-nowrap rounded-md px-3 py-2 {{ request()->routeIs('notifications.*') ? 'bg-teal-500 text-slate-950' : 'text-slate-300' }}" href="{{ route('notifications.index') }}">Alerts
                    <span data-notification-count class="{{ $unreadNotificationCount > 0 ? '' : 'hidden' }} ml-1 inline-flex min-w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold tabular-nums text-white">
                        <span data-notification-number aria-hidden="true">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                        <span data-notification-label class="sr-only">{{ $unreadNotificationCount > 0 ? $unreadNotificationCount.' unread notifications' : '' }}</span>
                    </span>
            </a>
        @endif
    </div>
</nav>
