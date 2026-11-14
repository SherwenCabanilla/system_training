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
            <form data-open-notification method="POST" action="{{ route('notifications.read', $portalNotification) }}" class="overflow-hidden rounded-2xl border {{ $portalNotification->read_at ? 'border-slate-200 bg-white' : 'border-teal-200 bg-teal-50/60' }} shadow-sm">
                @csrf
                @method('PATCH')
                <input type="hidden" name="open" value="1">
                <button type="submit" class="group flex w-full items-start gap-3 p-5 text-left transition hover:bg-teal-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-teal-700 disabled:opacity-60">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $portalNotification->read_at ? 'bg-transparent' : 'bg-teal-600' }}" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        @if (! $portalNotification->read_at)<span class="sr-only">Unread: </span>@endif
                        <span class="block text-sm font-semibold text-slate-900">{{ $portalNotification->title }}</span>
                        <span class="mt-1 block break-words text-sm leading-6 text-slate-600">{{ $portalNotification->body }}</span>
                        <span class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <time datetime="{{ $portalNotification->created_at->toIso8601String() }}" class="text-slate-500">{{ $portalNotification->created_at->format('M j, Y g:i A') }}</time>
                            <span class="font-semibold text-teal-700">{{ str_starts_with($portalNotification->type, 'appointment_') ? 'View appointment' : ($portalNotification->type === 'vital_reading_available' ? 'View medical record' : 'Read notification') }} <span aria-hidden="true">&rarr;</span></span>
                        </span>
                    </span>
                </button>
                <p data-notification-error role="alert" class="px-5 pb-3 text-sm text-rose-700 empty:hidden"></p>
            </form>
        @empty <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">You have no notifications.</div>
        @endforelse
    </div>
</x-app-layout>
