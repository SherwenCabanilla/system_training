<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-teal-700">Patient dashboard</p>
        <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Welcome, {{ Auth::user()->name }}</h1>
                <p class="mt-1 text-sm text-slate-600">Your health information will be available here.</p>
            </div>
            <a class="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" href="{{ route('profile.edit') }}">Review profile</a>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                <svg aria-hidden="true" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m4-2a8 8 0 11-16 0 8 8 0 0116 0z" /></svg>
            </div>
            <h2 class="mt-5 text-lg font-semibold text-slate-900">Your portal is ready</h2>
            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">We have set up your secure portal access. Appointments, medical records, and reminders will appear here as each feature is completed.</p>

            <div class="mt-7 grid gap-3 sm:grid-cols-3">
                <a class="rounded-xl border border-slate-200 p-4 transition hover:border-teal-300 hover:bg-teal-50" href="{{ route('appointments.index') }}"><p class="text-sm font-semibold text-slate-800">Appointments</p><p class="mt-1 text-xs leading-5 text-slate-500">Manage your bookings</p></a>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-sm font-semibold text-slate-800">Medical records</p><p class="mt-1 text-xs leading-5 text-slate-500">Sample records will appear here</p></div>
                <div class="rounded-xl border border-slate-200 p-4"><p class="text-sm font-semibold text-slate-800">Notifications</p><p class="mt-1 text-xs leading-5 text-slate-500">Reminders will appear here</p></div>
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Quick navigation</h2>
            <nav class="mt-4 space-y-1" aria-label="Portal navigation">
                <a class="flex items-center justify-between rounded-lg bg-teal-50 px-3 py-2.5 text-sm font-semibold text-teal-800" href="{{ route('dashboard') }}"><span>Dashboard</span><span aria-hidden="true">›</span></a>
                <a class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-teal-700" href="{{ route('profile.edit') }}"><span>My profile</span><span aria-hidden="true">›</span></a>
            </nav>

            <div class="mt-6 rounded-xl bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-800">Keep your details current</p><p class="mt-1 text-xs leading-5 text-slate-600">Update your email address so future appointment reminders can reach you.</p></div>
        </aside>
    </div>
</x-app-layout>
