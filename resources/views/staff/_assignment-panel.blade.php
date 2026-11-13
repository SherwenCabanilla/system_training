<p id="assignment-feedback" class="fixed bottom-4 right-4 z-40 hidden max-w-sm rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900 shadow-lg" role="status"></p>
<dialog id="assignment-panel" aria-labelledby="assignment-panel-title" class="fixed inset-y-0 left-auto right-0 m-0 h-dvh max-h-none w-full max-w-none border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/40 sm:max-w-xl">
    <form id="assignment-form" method="POST" class="flex h-full flex-col" data-today="{{ now()->toDateString() }}">
        @csrf
        @method('PATCH')
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Patient request</p><h2 id="assignment-panel-title" class="mt-1 text-xl font-semibold text-slate-900">Review &amp; assign</h2></div>
            <button data-close-assignment type="button" class="flex h-9 w-9 items-center justify-center rounded-lg text-2xl text-slate-500 hover:bg-slate-100" aria-label="Close assignment panel">&times;</button>
        </header>
        <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5 sm:px-6">
            <section aria-labelledby="assignment-patient" class="rounded-2xl bg-slate-50 p-4">
                <h3 id="assignment-patient" class="font-semibold text-slate-900"></h3>
                <p id="assignment-email" class="mt-1 break-words text-xs text-slate-500"></p>
                <p class="mt-4 text-xs font-semibold text-slate-500">Patient's concern</p>
                <p id="assignment-concern" class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-slate-700"></p>
                <div class="mt-4 border-t border-slate-200 pt-3"><p class="text-xs font-semibold text-slate-500">Preferred date and time</p><p id="assignment-preference" class="mt-1 text-sm font-semibold text-slate-800"></p></div>
            </section>
            <section class="space-y-4" aria-labelledby="assignment-schedule-title">
                <div><h3 id="assignment-schedule-title" class="font-semibold text-slate-900">Arrange the visit</h3><p class="mt-1 text-xs text-slate-500">Choose a doctor, then select an available time.</p></div>
                <div>
                    <label for="assignment-doctor-search" class="text-sm font-semibold text-slate-700">Doctor</label>
                    <input id="assignment-doctor-search" list="assignment-doctors" autocomplete="off" placeholder="Search name, department, or specialty" class="mt-2 block w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600" required @disabled($doctors->isEmpty())>
                    <input id="assignment-doctor-id" name="doctor_id" type="hidden">
                    <datalist id="assignment-doctors">@foreach ($doctors as $doctor)<option data-id="{{ $doctor->id }}" value="{{ $doctor->name }} - {{ $doctor->department }} / {{ $doctor->specialty }}"></option>@endforeach</datalist>
                    @if ($doctors->isEmpty())<p class="mt-2 text-xs text-amber-800">No active doctors are available for assignment.</p>@endif
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="assignment-date" class="text-sm font-semibold text-slate-700">Date</label><input id="assignment-date" type="date" min="{{ now()->toDateString() }}" class="mt-2 block w-full cursor-pointer rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600" required></div>
                    <div><label for="assignment-time" class="text-sm font-semibold text-slate-700">Available time</label><select id="assignment-time" name="scheduled_at" class="mt-2 block w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600" required disabled><option value="">Choose a doctor first</option></select></div>
                </div>
                <p class="text-xs text-slate-500">30-minute visits &middot; 8 AM to 5 PM &middot; {{ config('app.timezone') }}</p>
                <p id="assignment-availability-message" class="min-h-10 text-sm text-slate-600" role="status"></p>
            </section>
            <section class="rounded-2xl border border-teal-200 bg-teal-50 p-4" aria-label="Appointment confirmation summary">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Confirm these details</p>
                <p id="assignment-summary-doctor" class="mt-2 break-words text-sm font-semibold text-slate-900">Choose a doctor</p>
                <p id="assignment-summary-time" class="mt-1 text-sm text-slate-700">Choose an available date and time</p>
                <p class="mt-3 text-xs leading-5 text-teal-800">The patient will be notified of the confirmed doctor and schedule.</p>
            </section>
            <p id="assignment-error" class="min-h-5 text-sm font-medium text-rose-700" role="alert"></p>
        </div>
        <footer class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-white px-5 py-4 sm:px-6">
            <button data-close-assignment type="button" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
            <button id="confirm-assignment" type="submit" class="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50" disabled>Confirm appointment</button>
        </footer>
    </form>
</dialog>
