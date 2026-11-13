<article data-assignment-row="{{ $appointment->id }}" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-sm font-semibold text-slate-900">{{ $appointment->patient->user->name }}</h3>
            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">Pending assignment</span>
        </div>
        <p class="mt-1 text-xs font-medium text-slate-600">Preferred {{ $appointment->scheduled_at->format('M j, Y') }} &middot; {{ $appointment->scheduled_at->format('g:i A') }}</p>
        <p class="mt-1 truncate text-sm text-slate-500">{{ $appointment->reason ?: 'No visit reason provided.' }}</p>
    </div>
    <button type="button" data-review-assignment data-patient="{{ $appointment->patient->user->name }}" data-email="{{ $appointment->patient->user->email }}" data-reason="{{ $appointment->reason ?: 'No visit reason provided.' }}" data-preferred="{{ $appointment->scheduled_at->format('Y-m-d\TH:i') }}" data-preferred-label="{{ $appointment->scheduled_at->format('M j, Y - g:i A') }}" data-action="{{ route('staff.appointments.assignment', $appointment) }}" data-availability="{{ route('staff.appointments.availability', $appointment) }}" class="inline-flex shrink-0 items-center justify-center gap-2 self-start rounded-lg bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-800 transition hover:bg-teal-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 sm:self-auto">
        Review &amp; assign <span aria-hidden="true">&rarr;</span>
    </button>
</article>
