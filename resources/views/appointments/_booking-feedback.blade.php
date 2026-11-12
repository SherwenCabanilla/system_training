<dialog id="appointment-confirmation-modal" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-3xl p-0 backdrop:bg-slate-950/50" aria-labelledby="confirmation-modal-title">
    <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl sm:p-7">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-teal-700">Almost finished</p>
                <h2 id="confirmation-modal-title" class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Confirm your appointment</h2>
            </div>
            <button id="close-confirmation-modal" type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close confirmation">&times;</button>
        </div>
        <p class="mt-3 text-sm leading-6 text-slate-600">Please check these details before we save your request.</p>
        <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70">
            <div class="grid grid-cols-[7rem_1fr] gap-3 px-4 py-3">
                <dt class="text-sm font-medium text-slate-500">Doctor</dt>
                <dd id="confirmation-doctor" class="text-sm font-semibold text-slate-900">
                </dd>
            </div>
            <div class="grid grid-cols-[7rem_1fr] gap-3 px-4 py-3">
                <dt class="text-sm font-medium text-slate-500">Date & time</dt>
                <dd id="confirmation-date" class="text-sm font-semibold text-slate-900">
                </dd>
            </div>
            <div class="grid grid-cols-[7rem_1fr] gap-3 px-4 py-3">
                <dt class="text-sm font-medium text-slate-500">Reason</dt>
                <dd id="confirmation-reason" class="text-sm text-slate-700">
                </dd>
            </div>
        </dl>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button id="edit-confirmation" type="button" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Go back and edit</button>
            <button id="confirm-appointment" type="button" class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Confirm appointment</button>
        </div>
    </div>
</dialog>

<div id="appointment-success-toast" class="fixed bottom-5 right-5 z-50 hidden w-[calc(100%-2.5rem)] max-w-md rounded-2xl border border-teal-200 bg-white p-4 shadow-xl" role="status" aria-live="polite">
    <div class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-lg font-bold text-teal-700">&#10003;</span>
        <div class="min-w-0 grow">
            <p class="font-semibold text-slate-900">Appointment booked</p>
            <p id="appointment-success-message" class="mt-1 text-sm leading-5 text-slate-600">
            </p>
        </div>
        <button id="close-success-toast" type="button" class="-mt-1 rounded-lg px-2 py-1 text-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Dismiss notification">&times;</button>
    </div>
</div>
