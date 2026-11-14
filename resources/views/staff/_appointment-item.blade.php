<details class="group rounded-xl border border-slate-200 bg-white open:border-teal-200">
    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-4 py-3 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 [&::-webkit-details-marker]:hidden">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-slate-900">{{ $appointment->patient->user->name }}</span>
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $appointment->status === 'Scheduled' ? 'bg-teal-100 text-teal-800' : ($appointment->status === 'Completed' ? 'bg-slate-100 text-slate-700' : 'bg-rose-100 text-rose-700') }}">{{ $appointment->status }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ $appointment->scheduled_at->format('M j, Y - g:i A') }} <span class="px-1 text-slate-300">&bull;</span> {{ $appointment->doctor_name }}</p>
        </div>
        <span class="hidden text-xs font-semibold text-teal-700 sm:inline">Review</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m5 7.5 5 5 5-5" /></svg>
    </summary>
    <div class="border-t border-slate-100 px-4 pb-4 pt-3">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold text-slate-500">Patient contact</dt>
                <dd class="mt-1 break-words text-sm text-slate-700">{{ $appointment->patient->user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Department</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $appointment->department }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold text-slate-500">Patient's concern</dt>
                <dd class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $appointment->reason ?: 'No visit reason provided.' }}</dd>
            </div>
        </dl>
        <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
            @php($vitalReading = $appointment->vitalReadings->first())
            @if (in_array($appointment->status, ['Scheduled', 'Completed'], true))
                <button type="button" @click="$dispatch('open-medical-record', { action: @js(route('staff.appointments.medical-records.store', $appointment)), patientName: @js($appointment->patient->user->name), department: @js($appointment->department), recordedOn: @js(now()->toDateString()), heartRate: @js($vitalReading?->heart_rate), temperature: @js($vitalReading?->temperature), systolic: @js($vitalReading?->systolic), diastolic: @js($vitalReading?->diastolic) })" class="rounded-xl bg-teal-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">{{ $appointment->status === 'Scheduled' ? 'Complete visit' : 'Add visit record' }}</button>
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
    </div>
</details>
