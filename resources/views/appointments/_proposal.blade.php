<section class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4" aria-label="Proposed appointment change">
    <h4 class="text-sm font-semibold text-amber-900">Your approval is needed</h4>
    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-xs font-semibold text-slate-500">Originally requested</dt><dd class="mt-1 text-slate-700">{{ \Illuminate\Support\Carbon::parse($appointment->requested_details['scheduled_at'])->format('M j, Y g:i A') }}<br>{{ $appointment->requested_details['doctor_id'] ? $appointment->requested_details['doctor_name'] : 'Let staff choose a doctor' }}</dd></div>
        <div><dt class="text-xs font-semibold text-slate-500">Proposed by the clinic</dt><dd class="mt-1 font-semibold text-slate-900">{{ $appointment->scheduled_at->format('M j, Y g:i A') }}<br>{{ $appointment->doctor_name }}</dd></div>
    </dl>
    <p class="mt-3 text-xs leading-5 text-amber-900">Not confirmed yet. This slot is held until {{ $appointment->proposal_expires_at->format('M j, Y g:i A') }} ({{ config('app.timezone') }}). If you do not respond, it returns to staff review.</p>
    <form data-proposal-response action="{{ route('appointments.proposal', $appointment) }}" method="POST" class="mt-3">
        @csrf
        @method('PATCH')
        <input type="hidden" name="proposal_token" value="{{ $appointment->proposal_token }}">
        <div class="flex flex-wrap gap-2">
            <button name="decision" value="accept" class="rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800 disabled:opacity-50">Accept schedule</button>
            <button name="decision" value="decline" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 disabled:opacity-50">Request another schedule</button>
            <button name="decision" value="cancel" class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 disabled:opacity-50">Cancel request</button>
        </div>
        <p data-proposal-feedback class="mt-2 text-sm text-slate-700 empty:hidden" role="status"></p>
    </form>
</section>
