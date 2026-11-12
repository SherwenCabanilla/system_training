<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-teal-700">Staff workspace</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Clinic overview</h1>
                <p class="mt-2 text-sm text-slate-600">Resolve outstanding visits and work through today's queue.</p>
            </div>
            <p class="text-sm font-medium text-slate-500">{{ now()->format('l, M j, Y') }}</p>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ vitalForm: {} }" @open-vitals.window="vitalForm = $event.detail; $refs.vitalDialog.showModal()">
        @include('staff._feedback')
        <section aria-label="Clinic summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Needs assignment', 'count' => $assignmentRequestCount, 'href' => route('staff.requests'), 'hint' => 'Review patient requests'],
                ['label' => 'Overdue outcomes', 'count' => $appointmentsNeedingOutcome->total(), 'href' => '#overdue', 'hint' => 'Record a visit outcome'],
                ['label' => 'Scheduled today', 'count' => $todayAppointments->total(), 'href' => '#today', 'hint' => 'Work through the queue'],
                ['label' => 'Upcoming visits', 'count' => $upcomingAppointmentCount, 'href' => route('staff.upcoming'), 'hint' => 'Plan the days ahead'],
                ] as $summary)
                <a href="{{ $summary['href'] }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-teal-400 focus:ring-2 focus:ring-teal-600">
                    <p class="text-sm font-medium text-slate-600">{{ $summary['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $summary['count'] }}</p>
                    <p class="mt-2 text-xs font-medium text-teal-700">{{ $summary['hint'] }} &rarr;</p>
                </a>
            @endforeach
        </section>
        <section class="space-y-6">
            @include('staff._queue', [
            'appointments' => $appointmentsNeedingOutcome,
            'id' => 'overdue',
            'title' => 'Overdue visit outcomes',
            'description' => 'These past visits need a recorded outcome.',
            'emptyMessage' => 'No overdue visits. You are caught up.',
            ])
            @include('staff._queue', [
            'appointments' => $todayAppointments,
            'id' => 'today',
            'title' => "Today's schedule",
            'description' => 'Visits are ordered by appointment time.',
            'emptyMessage' => 'No visits scheduled today. Future bookings are on the Upcoming page.',
            ])
        </section>
        @include('staff._vital-modal')
    </div>
</x-app-layout>
