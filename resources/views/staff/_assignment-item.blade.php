<details class="group rounded-xl border border-slate-200 bg-white open:border-teal-200">
    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-4 py-3 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 [&::-webkit-details-marker]:hidden">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-slate-900">{{ $appointment->patient->user->name }}</span>
                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">Pending assignment</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Requested {{ $appointment->scheduled_at->format('M j, Y - g:i A') }}</p>
        </div>
        <span class="hidden text-xs font-semibold text-teal-700 sm:inline">Review</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m5 7.5 5 5 5-5" /></svg>
    </summary>
    <div class="border-t border-slate-100 px-4 pb-4 pt-3">
        <p class="break-words text-sm text-slate-500">{{ $appointment->patient->user->email }}</p>
        <dl class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto]">
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Patient's concern</dt>
                <dd class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-800">{{ $appointment->reason ?: 'No visit reason provided.' }}</dd>
            </div>
            <div class="py-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Requested visit</dt>
                <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $appointment->scheduled_at->format('M j, Y') }}<span class="mt-1 block font-normal text-slate-600">{{ $appointment->scheduled_at->format('g:i A') }}</span>
                </dd>
            </div>
        </dl>
        <form method="POST" action="{{ route('staff.appointments.assignment', $appointment) }}" class="mt-5 border-t border-slate-100 pt-4">
            @csrf
            @method('PATCH')
            <label for="doctor-{{ $appointment->id }}" class="text-sm font-semibold text-slate-700">Assign a doctor</label>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                <select id="doctor-{{ $appointment->id }}" name="doctor_id" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600" required>
                    <option value="">Choose a doctor after reviewing the concern</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}">{{ $doctor->name }} &mdash; {{ $doctor->department }} / {{ $doctor->specialty }}</option>
                    @endforeach
                </select>
                <button @disabled($doctors->isEmpty()) class="shrink-0 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50">Assign &amp; notify patient</button>
            </div>
            @if ($doctors->isEmpty())
                <p class="mt-2 text-sm text-amber-800">No active doctors are available for assignment.</p>
            @endif
        </form>
    </div>
</details>
