<dialog id="confirmation-dialog" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl shadow-slate-950/15 backdrop:bg-slate-950/35 backdrop:backdrop-blur-[2px]">
    <form method="dialog" class="p-6 sm:p-7">
        <div class="flex items-start justify-between gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50" aria-hidden="true">
                <svg class="h-5 w-5 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v4m0 4h.01" /><path d="M10.3 3.9 2.8 16.5A1.8 1.8 0 0 0 4.4 19h15.2a1.8 1.8 0 0 0 1.6-2.5L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>
            </div>
            <button value="cancel" aria-label="Close confirmation" class="-mr-2 -mt-2 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
        </div>
        <h2 id="confirmation-dialog-title" class="mt-5 text-lg font-semibold tracking-tight">Please confirm</h2>
        <p id="confirmation-dialog-message" class="mt-2 text-sm leading-6 text-slate-500"></p>
        <div class="mt-7 flex items-center justify-end gap-3">
            <button id="confirmation-dialog-cancel" value="cancel" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-300">Keep appointment</button>
            <button id="confirmation-dialog-confirm" value="confirm" class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2">Confirm</button>
        </div>
    </form>
</dialog>
