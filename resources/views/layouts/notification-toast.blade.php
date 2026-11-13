<div id="patient-notifications" data-feed-url="{{ route('notifications.feed') }}" data-notifications-url="{{ route('notifications.index') }}" data-user-id="{{ Auth::id() }}" class="pointer-events-none fixed bottom-4 right-4 z-50 w-[calc(100%-2rem)] max-w-sm">
    <div id="patient-notification-toast" class="pointer-events-auto hidden rounded-2xl border border-teal-200 bg-white p-4 shadow-xl" role="status" aria-live="polite" aria-atomic="true">
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" /></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p id="patient-notification-title" class="text-sm font-semibold text-slate-900"></p>
                <p id="patient-notification-body" class="mt-1 break-words text-sm leading-5 text-slate-600"></p>
                <a id="patient-notification-link" href="{{ route('notifications.index') }}" class="mt-3 inline-block text-sm font-semibold text-teal-700 underline-offset-4 hover:underline">View notifications</a>
            </div>
            <button id="dismiss-patient-notification" type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xl text-slate-500 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600" aria-label="Dismiss notification">&times;</button>
        </div>
    </div>
</div>
