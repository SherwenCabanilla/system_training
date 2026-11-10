<nav class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <x-application-logo class="h-9 w-9 text-teal-600" />
            <span class="text-base font-semibold tracking-tight text-slate-900">SegHIS <span class="font-normal text-slate-500">Patient Portal</span></span>
        </a>

        <div class="flex items-center gap-3">
            <a class="hidden text-sm font-medium text-slate-600 hover:text-teal-700 md:block" href="{{ route('appointments.index') }}">Appointments</a>
            <a class="hidden text-sm font-medium text-slate-600 hover:text-teal-700 lg:block" href="{{ route('medical-records.index') }}">Medical records</a>
            <a class="hidden text-sm font-medium text-slate-600 hover:text-teal-700 lg:block" href="{{ route('notifications.index') }}">Notifications</a>
            <a class="hidden text-sm font-medium text-slate-600 hover:text-teal-700 sm:block" href="{{ route('profile.edit') }}">My profile</a>
            <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>
            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" type="submit">Log out</button>
            </form>
        </div>
    </div>
</nav>
