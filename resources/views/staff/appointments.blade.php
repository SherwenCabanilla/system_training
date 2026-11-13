<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-teal-700">Staff workspace</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
    </x-slot>

    <div class="space-y-6" x-data="{ vitalForm: {} }" @open-vitals.window="vitalForm = $event.detail; $refs.vitalDialog.showModal()">
        @include('staff._feedback')

        <section id="staff-appointments-list" data-last-page="{{ $appointments->lastPage() }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">{{ $isAssignment ? 'Waiting for a doctor' : 'Appointments' }}</h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $appointments->total() }} total</span>
            </div>
            <div class="space-y-2 p-3 sm:p-4">
                @forelse ($appointments as $appointment)
                    @if ($isAssignment)
                        @include('staff._assignment-item', ['appointment' => $appointment])
                    @else
                        @include('staff._appointment-item', ['appointment' => $appointment, 'canRecordMeasurements' => false])
                    @endif
                @empty
                    <div class="px-4 py-16 text-center">
                        <p class="text-lg font-semibold text-slate-900">{{ $isAssignment ? 'All requests are taken care of' : 'No appointments here yet' }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ $isAssignment ? 'New patient requests will appear here for review.' : 'Appointments will appear here as the clinic schedule changes.' }}</p>
                        <a href="{{ route('staff.dashboard') }}" class="mt-5 inline-block text-sm font-semibold text-teal-700 hover:text-teal-900">Return to today's queue &rarr;</a>
                    </div>
                @endforelse
            </div>
            @if ($appointments->hasPages())
                <div class="border-t border-slate-200 p-5">{{ $appointments->links() }}</div>
            @endif
        </section>
        @if ($isAssignment)
            @include('staff._assignment-panel')
        @endif
        @include('staff._vital-modal')
    </div>
</x-app-layout>
