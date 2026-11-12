<article class="rounded-2xl border border-slate-200 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="font-semibold text-slate-900">{{ $appointment->patient->user->name }}</h3>
            <p class="mt-1 break-words text-sm text-slate-500">{{ $appointment->patient->user->email }}</p>
        </div>
        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">Pending assignment</span>
    </div>
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
</article>
