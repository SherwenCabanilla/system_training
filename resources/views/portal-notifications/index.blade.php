<x-app-layout>
    <x-slot name="header"><p class="text-sm font-semibold text-teal-700">Patient care</p><h1 class="mt-1 text-2xl font-semibold text-slate-900">Notifications</h1></x-slot>
    <div class="space-y-3">
        @forelse ($portalNotifications as $portalNotification)
            <article class="rounded-2xl border {{ $portalNotification->read_at ? 'border-slate-200 bg-white' : 'border-teal-200 bg-teal-50' }} p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-sm font-semibold text-slate-900">{{ $portalNotification->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $portalNotification->body }}</p><p class="mt-2 text-xs text-slate-500">{{ $portalNotification->created_at->format('M j, Y g:i A') }}</p></div>@if (! $portalNotification->read_at)<form method="POST" action="{{ route('notifications.read', $portalNotification) }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-teal-700">Mark as read</button></form>@endif</div></article>
        @empty <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">You have no notifications.</div>
        @endforelse
    </div>
</x-app-layout>
