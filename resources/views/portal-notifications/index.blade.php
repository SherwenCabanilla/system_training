<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-teal-700">Patient care</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Notifications</h1>
            </div>
            @if ($portalNotifications->whereNull('read_at')->isNotEmpty())
                <form class="shrink-0" method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">Mark all as read</button>
                </form>
            @endif
        </div>
    </x-slot>
    <div class="space-y-3">
        @forelse ($portalNotifications as $portalNotification)
            <article class="rounded-2xl border {{ $portalNotification->read_at ? 'border-slate-200 bg-white' : 'border-teal-200 bg-teal-50' }} p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-sm font-semibold text-slate-900">{{ $portalNotification->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $portalNotification->body }}</p><p class="mt-2 text-xs text-slate-500">{{ $portalNotification->created_at->format('M j, Y g:i A') }}</p></div>@if (! $portalNotification->read_at)<form method="POST" action="{{ route('notifications.read', $portalNotification) }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-teal-700">Mark as read</button></form>@endif</div></article>
        @empty <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">You have no notifications.</div>
        @endforelse
    </div>
</x-app-layout>
