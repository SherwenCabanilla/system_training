<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-teal-700">Patient care</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Appointments</h1>
    </x-slot>

    <div id="appointments-page" data-store-url="{{ route('appointments.store') }}" data-base-url="{{ url('/appointments') }}" class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <form id="appointment-form" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <input id="appointment-id" type="hidden">
            <h2 id="form-title" class="font-semibold text-slate-900">Book appointment</h2>
            <input name="scheduled_at" type="datetime-local" class="w-full rounded border-slate-300" required>
            <input name="doctor_name" placeholder="Doctor name" class="w-full rounded border-slate-300" required>
            <input name="department" placeholder="Department" class="w-full rounded border-slate-300" required>
            <textarea name="reason" placeholder="Reason for visit" class="w-full rounded border-slate-300"></textarea>
            <input name="status" type="hidden" value="Scheduled">
            <div class="flex gap-3"><button id="form-submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Save appointment</button><button id="form-cancel" type="button" class="hidden text-sm font-semibold text-slate-600">Cancel edit</button></div>
            <p id="form-message" class="text-sm" aria-live="polite"></p>
        </form>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="font-semibold text-slate-900">My appointments</h2><div id="appointment-list" class="mt-4 space-y-3">
            @forelse ($appointments as $appointment)
                <article data-id="{{ $appointment->id }}" class="rounded-xl border border-slate-200 p-4"><p class="font-semibold">{{ $appointment->scheduled_at->format('M j, Y g:i A') }}</p><p class="text-sm text-slate-600">{{ $appointment->doctor_name }} &middot; {{ $appointment->department }}</p><p class="mt-1 text-sm text-slate-500">{{ $appointment->reason }}</p><div class="mt-3 flex gap-3"><button data-edit='@json($appointment)' class="text-sm font-semibold text-teal-700">Edit</button><button data-delete="{{ $appointment->id }}" class="text-sm font-semibold text-red-600">Cancel appointment</button></div></article>
            @empty <p id="empty-state" class="text-sm text-slate-500">No appointments yet.</p> @endforelse
        </div></section>
    </div>
</x-app-layout>
