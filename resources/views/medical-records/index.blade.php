<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-teal-700">Patient care</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Medical records</h1>
        <p class="mt-1 text-sm text-slate-600">Sample records for this demonstration portal.</p>
    </x-slot>

    <div class="space-y-4">
        @forelse ($medicalRecords as $medicalRecord)
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-sm font-semibold text-teal-700">{{ $medicalRecord->record_type }}</p><h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $medicalRecord->title }}</h2><p class="mt-1 text-sm text-slate-600">{{ $medicalRecord->provider_name }} · {{ $medicalRecord->recorded_on->format('M j, Y') }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Read only</span></div>
                <p class="mt-4 text-sm leading-6 text-slate-700">{{ $medicalRecord->summary }}</p>
                @if ($medicalRecord->result_summary)<div class="mt-4 rounded-xl bg-teal-50 p-4 text-sm text-teal-900"><span class="font-semibold">Result:</span> {{ $medicalRecord->result_summary }}</div>@endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No medical records are available yet.</div>
        @endforelse
    </div>
</x-app-layout>
