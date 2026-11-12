<article class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
    <div class="flex min-w-0 gap-4">
        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-100 text-slate-800">
            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ $appointment->scheduled_at->format('M') }}</span>
            <span class="text-xl font-bold leading-none">{{ $appointment->scheduled_at->format('d') }}</span>
        </div>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-semibold text-slate-900">{{ $appointment->patient->user->name }}</h3>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $appointment->status === 'Scheduled' ? 'bg-teal-100 text-teal-800' : ($appointment->status === 'Completed' ? 'bg-slate-100 text-slate-700' : 'bg-rose-100 text-rose-700') }}">{{ $appointment->status }}</span>
            </div>
            <p class="mt-1 break-words text-sm text-slate-600">{{ $appointment->patient->user->email }}</p>
            <p class="mt-2 text-sm font-medium text-slate-700">{{ $appointment->doctor_name }} <span class="px-1 text-slate-300">&bull;</span> {{ $appointment->department }}</p>
        </div>
    </div>
    <div class="rounded-xl bg-slate-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visit time</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $appointment->scheduled_at->format('D, M j, g:i A') }}</p>
        <p class="mt-1 break-words text-sm leading-6 text-slate-600">{{ $appointment->reason ?: 'No visit reason provided.' }}</p>
    </div>
    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4 lg:col-span-2">
        @php($vitalReading = $appointment->vitalReadings->first())
        @if ($appointment->status === 'Scheduled' && $canRecordMeasurements)
            <button type="button" @click="$dispatch('open-vitals', { action: @js(route('staff.appointments.vital-reading', $appointment)), patientName: @js($appointment->patient->user->name), department: @js($appointment->department), recordedOn: @js($vitalReading?->recorded_on?->format('Y-m-d') ?? now()->toDateString()), heartRate: @js($vitalReading?->heart_rate), temperature: @js($vitalReading?->temperature), systolic: @js($vitalReading?->systolic), diastolic: @js($vitalReading?->diastolic) })" class="rounded-xl bg-teal-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">Complete &amp; add measurements</button>
        @endif
        @if ($appointment->status === 'Completed')
            <button type="button" @click="$dispatch('open-vitals', { action: @js(route('staff.appointments.vital-reading', $appointment)), patientName: @js($appointment->patient->user->name), department: @js($appointment->department), recordedOn: @js($vitalReading?->recorded_on?->format('Y-m-d') ?? now()->toDateString()), heartRate: @js($vitalReading?->heart_rate), temperature: @js($vitalReading?->temperature), systolic: @js($vitalReading?->systolic), diastolic: @js($vitalReading?->diastolic) })" class="rounded-xl border border-teal-300 bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-800 transition hover:bg-teal-100">{{ $vitalReading ? 'Edit measurements' : 'Add measurements' }}</button>
        @endif
        @if ($appointment->status === 'Scheduled')
            <div class="grid grid-cols-2 gap-2">
                <form method="POST" action="{{ route('staff.appointments.status', $appointment) }}">
                    @csrf
                    @method('PATCH')
                    <input name="status" type="hidden" value="Missed">
                    <button class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">Mark missed</button>
                </form>
                <form method="POST" action="{{ route('staff.appointments.status', $appointment) }}">
                    @csrf
                    @method('PATCH')
                    <input name="status" type="hidden" value="Cancelled">
                    <button class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">Cancel visit</button>
                </form>
            </div>
        @endif
    </div>
</article>
