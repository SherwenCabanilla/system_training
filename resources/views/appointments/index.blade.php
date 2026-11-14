<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Care planner</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Appointments</h1>
                <p class="mt-2 text-sm text-slate-600">Schedule a visit and keep every upcoming detail in one place.</p>
            </div>
            <button id="open-appointment-modal" type="button" aria-haspopup="dialog" aria-controls="appointment-modal" class="inline-flex shrink-0 items-center justify-center gap-2 self-start rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 sm:self-auto">
                <span aria-hidden="true" class="text-lg leading-none">+</span>
                Book appointment
            </button>
        </div>
    </x-slot>

    <div id="appointments-page" data-store-url="{{ route('appointments.store') }}" data-base-url="{{ url('/appointments') }}" class="space-y-6">
        @include('appointments._booking-form', ['doctors' => $doctors])

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-teal-700">My schedule</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">Appointments at a glance</h2>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                    <span data-appointment-total>{{ $upcomingAppointments->total() + $completedAppointments->count() }}</span> total</span>
            </div>
            <div class="mt-6 border-b border-slate-200">
                <div id="appointment-filters" class="flex gap-6 overflow-x-auto" role="tablist" aria-label="Appointment categories">
                    @foreach (['all' => 'All', 'pending' => 'Pending assignment', 'scheduled' => 'Scheduled', 'approval' => 'Awaiting your approval', 'reschedule' => 'Needs rescheduling', 'history' => 'Completed'] as $filter => $label)
                        <button id="{{ $filter === 'all' ? 'upcoming' : ($filter === 'history' ? 'completed' : $filter) }}-tab" type="button" role="tab" data-appointment-filter="{{ $filter }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" aria-controls="{{ $filter === 'history' ? 'completed' : 'upcoming' }}-panel" class="shrink-0 whitespace-nowrap border-b-2 border-transparent px-1 pb-3 text-sm font-semibold text-slate-500 transition hover:text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 aria-selected:border-teal-700 aria-selected:text-teal-800">{{ $label }} <span data-filter-count @if ($filter === 'all') data-upcoming-count @endif class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs tabular-nums">{{ $filter === 'all' ? $upcomingAppointments->total() : ($filter === 'history' ? $completedAppointments->count() : 0) }}</span></button>
                    @endforeach
                </div>
            </div>
            <div id="upcoming-panel" class="mt-6" role="tabpanel" aria-labelledby="upcoming-tab">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">Open appointments</h3>
                        <p class="mt-1 text-sm text-slate-500">All scheduled visits, pending requests, and changes awaiting your response.</p>
                    </div>
                    <span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800">
                        <span data-upcoming-count>{{ $upcomingAppointments->total() }}</span> open</span>
                </div>
                <p id="appointment-filter-empty" class="mt-4 hidden rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500" role="status">No appointments match this filter.</p>
                <div id="appointment-list" class="mt-4 space-y-3">
                    @forelse ($upcomingAppointments as $appointment)
                        <article data-id="{{ $appointment->id }}" data-appointment-status="{{ $appointment->status }}" data-scheduled-at="{{ $appointment->scheduled_at->format('c') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 p-4 transition hover:border-teal-300 hover:shadow-sm sm:p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-950 text-white">
                                    <span class="text-[10px] font-semibold uppercase tracking-widest text-teal-300">{{ $appointment->scheduled_at->format('M') }}</span>
                                    <span class="text-2xl font-bold leading-none">{{ $appointment->scheduled_at->format('d') }}</span>
                                </div>
                                <div class="grow">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-slate-900">{{ $appointment->doctor_name }}</h3>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $appointment->status === 'Scheduled' ? 'bg-teal-100 text-teal-800' : (in_array($appointment->status, ['Pending assignment', 'Awaiting patient approval']) ? 'bg-amber-100 text-amber-800' : ($appointment->status === 'Completed' ? 'bg-slate-100 text-slate-700' : 'bg-rose-100 text-rose-700')) }}">{{ $appointment->status }}</span>
                                        <span data-countdown class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $appointment->status === 'Pending assignment' && $appointment->scheduled_at->lte(now()) ? 'Needs rescheduling' : 'Calculating time left' }}</span>
                                    </div>
                                    <p class="mt-1 text-sm font-medium text-slate-600">{{ $appointment->department }} <span class="text-slate-300">·</span> {{ $appointment->scheduled_at->format('g:i A') }}</p>
                                    <p data-expired-request class="mt-2 text-sm text-amber-800 {{ $appointment->status === 'Pending assignment' && $appointment->scheduled_at->lte(now()) ? '' : 'hidden' }}">Preferred time passed without confirmation. Choose a new time or cancel this request.</p>
                                    <p class="mt-2 text-sm leading-5 text-slate-500">{{ $appointment->reason ?: 'No visit reason provided.' }}</p>
                                </div>
                                @if ($appointment->status !== 'Awaiting patient approval')
                                <div class="flex shrink-0 gap-3">
                                    <button data-edit='@json($appointment)' class="text-sm font-semibold text-teal-700 hover:text-teal-900">{{ $appointment->status === 'Pending assignment' && $appointment->scheduled_at->lte(now()) ? 'Reschedule' : 'Edit' }}</button>
                                    <button data-delete="{{ $appointment->id }}" class="text-sm font-semibold text-rose-600 hover:text-rose-800">Cancel</button>
                                </div>
                                @endif
                            </div>
                            @if ($appointment->status === 'Awaiting patient approval')
                                @include('appointments._proposal')
                            @endif
                        </article>
                    @empty
                            <div id="empty-state" class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-500">⌚</div>
                                <h4 class="mt-4 font-semibold text-slate-900">No upcoming appointments</h4>
                                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Select Book appointment to arrange your next visit. Your upcoming appointments will appear here.</p>
                            </div>
                        @endforelse
                        </div>
                    @if ($upcomingAppointments->hasMorePages())
                        <div class="mt-5 text-center">
                            <a id="load-more-appointments" href="{{ $upcomingAppointments->nextPageUrl() }}" class="inline-flex rounded-xl border border-teal-200 bg-white px-5 py-3 text-sm font-semibold text-teal-700 transition hover:bg-teal-50">Load more appointments</a>
                        </div>
                    @endif
                    </div>
                    <div id="completed-panel" class="mt-6 hidden" role="tabpanel" aria-labelledby="completed-tab">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-900">Appointment history</h3>
                                <p class="mt-1 text-sm text-slate-500">Completed, missed, and cancelled visits are kept here for reference.</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $completedAppointments->count() }} closed</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($completedAppointments as $appointment)
                                <article data-id="{{ $appointment->id }}" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                        <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-600 text-white">
                                            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-200">{{ $appointment->scheduled_at->format('M') }}</span>
                                            <span class="text-2xl font-bold leading-none">{{ $appointment->scheduled_at->format('d') }}</span>
                                        </div>
                                        <div class="grow">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-semibold text-slate-900">{{ $appointment->doctor_name }}</h4>
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $appointment->status === 'Completed' ? 'bg-slate-200 text-slate-700' : 'bg-rose-100 text-rose-700' }}">{{ $appointment->status }}</span>
                                            </div>
                                            <p class="mt-1 text-sm font-medium text-slate-600">{{ $appointment->department }} <span class="text-slate-400">·</span> {{ $appointment->scheduled_at->format('M j, Y g:i A') }}</p>
                                            <p class="mt-2 text-sm leading-5 text-slate-500">{{ $appointment->reason ?: 'No visit reason provided.' }}</p>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-8 text-center">
                                        <p class="font-semibold text-slate-800">No completed appointments yet</p>
                                        <p class="mt-1 text-sm text-slate-500">Completed visits will appear here.</p>
                                    </div>
                                @endforelse
                                </div>
                            </div>
                        </section>
                    </div>

    @include('appointments._booking-feedback')
</x-app-layout>
