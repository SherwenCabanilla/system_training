<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Personal health hub</p><h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Medical records</h1><p class="mt-2 text-sm text-slate-600">A simple overview of your care history and available results.</p></div>
            <span class="rounded-full border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-800">Read-only patient view</span>
        </div>
    </x-slot>

    <div id="medical-records-page" class="space-y-6">
        @php
            $latestVitalReading = $vitalReadings->last();
        @endphp
        <section class="rounded-3xl bg-slate-950 p-4 text-white shadow-sm sm:p-5"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wider text-teal-300">Vital signs overview</p><h2 class="mt-1 text-lg font-semibold">Recent measurements</h2></div><span class="rounded-full border border-white/15 px-3 py-1 text-xs font-semibold text-slate-200">{{ $latestVitalReading?->recorded_on?->format('M j, Y') ?? 'No readings yet' }}</span></div><div class="mt-4 grid gap-3 sm:grid-cols-3"><div class="rounded-2xl bg-teal-500 p-3.5"><div class="flex items-center justify-between gap-2"><p class="text-xs font-medium text-teal-50">Heart rate</p><span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold text-white">{{ $heartRateStatus['label'] }}</span></div><p class="mt-1 text-2xl font-semibold">{{ $latestVitalReading?->heart_rate ?? '—' }}<span class="ml-1 text-xs font-medium">bpm</span></p></div><div class="rounded-2xl border border-white/10 bg-white/10 p-3.5"><div class="flex items-center justify-between gap-2"><p class="text-xs font-medium text-slate-200">Temperature</p><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $temperatureStatus['class'] }}">{{ $temperatureStatus['label'] }}</span></div><p class="mt-1 text-2xl font-semibold">{{ $latestVitalReading?->temperature ?? '—' }}<span class="ml-1 text-xs font-medium">°C</span></p></div><div class="rounded-2xl border border-white/10 bg-white/10 p-3.5"><div class="flex items-center justify-between gap-2"><p class="text-xs font-medium text-slate-200">Blood pressure</p><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $bloodPressureStatus['class'] }}">{{ $bloodPressureStatus['label'] }}</span></div><p class="mt-1 text-2xl font-semibold">{{ $latestVitalReading ? "{$latestVitalReading->systolic}/{$latestVitalReading->diastolic}" : '—' }}<span class="ml-1 text-xs font-medium">mmHg</span></p></div></div><p class="mt-3 text-xs text-slate-400"></p></section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.7fr)]"><div class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            @php
                $chartReadings = $vitalReadings->map(fn ($reading) => [
                    'date' => $reading->recorded_on->format('M j, Y'),
                    'shortDate' => $reading->recorded_on->format('M j'),
                    'heart_rate' => $reading->heart_rate,
                    'temperature' => $reading->temperature,
                    'systolic' => $reading->systolic,
                    'diastolic' => $reading->diastolic,
                ]);
            @endphp
            <div id="vital-trends" data-readings='@json($chartReadings)'>
                <p class="text-sm font-semibold text-teal-700">Vital signs trends</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">Last 7 readings</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $vitalReadings->isNotEmpty() ? $vitalReadings->first()->recorded_on->format('M j, Y').' - '.$vitalReadings->last()->recorded_on->format('M j, Y') : 'No readings yet' }}</p>
                <div class="mt-4 flex gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Vital sign charts">
                    @foreach (['heart_rate' => 'Heart rate', 'temperature' => 'Temperature', 'blood_pressure' => 'Blood pressure'] as $metric => $label)
                        <button id="trend-tab-{{ $metric }}" type="button" role="tab" data-trend="{{ $metric }}" aria-controls="trend-panel" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 transition hover:text-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 aria-selected:bg-white aria-selected:text-teal-800 aria-selected:shadow-sm">{{ $label }}</button>
                    @endforeach
                </div>
                <div id="trend-panel" role="tabpanel" aria-labelledby="trend-tab-heart_rate" tabindex="0" class="mt-4">
                    <div class="flex items-center justify-between gap-3"><h3 id="trend-title" class="text-sm font-semibold text-slate-800">Heart rate</h3><span id="trend-unit" class="text-xs text-slate-500">bpm</span></div>
                    <div id="trend-legend" class="mt-2 flex min-h-5 flex-wrap gap-4 text-xs text-slate-600"></div>
                    <div class="mt-2 flex h-64 items-center justify-center rounded-2xl bg-slate-50 p-2 sm:h-72">
                        <svg id="trend-chart" viewBox="0 0 600 260" class="h-full w-full" role="img" aria-label="Heart rate trend"></svg>
                        <p id="trend-empty" class="hidden px-4 text-center text-sm text-slate-500">No readings available yet.</p>
                    </div>
                    <div id="trend-stats" class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4"></div>
                    <p class="mt-3 text-xs text-slate-500">Each point represents a recorded visit. The scale adjusts to the selected measurement.</p>
                    <details class="mt-3 text-sm text-slate-600">
                        <summary class="cursor-pointer font-semibold text-teal-700">View exact readings</summary>
                        <div class="mt-2 overflow-x-auto"><table class="w-full text-left text-xs"><caption class="sr-only">Measurements for the same seven recent readings</caption><thead id="trend-table-head"></thead><tbody id="trend-table-body"></tbody></table></div>
                    </details>
                </div>
                <noscript><p class="mt-3 text-sm text-slate-500">Enable JavaScript to switch between charts. Your latest readings are shown above.</p></noscript>
            </div>
        </div><div class="self-start space-y-4"><div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><p class="text-sm font-semibold text-teal-700">Visit measurements</p><h2 class="mt-1 text-xl font-semibold text-slate-900">Recent appointments</h2><div class="mt-5 space-y-3">@forelse ($vitalReadings->reverse() as $reading)<div class="border-b border-slate-100 pb-3 last:border-0"><div class="flex items-center justify-between gap-3"><p class="text-sm font-semibold text-slate-800">{{ $reading->recorded_on->format('M j') }}</p><p class="text-sm font-semibold text-slate-700">{{ $reading->systolic }}/{{ $reading->diastolic }} <span class="text-xs font-normal text-slate-500">mmHg</span></p></div><p class="mt-1 text-xs text-slate-500">Heart {{ $reading->heart_rate }} bpm <span class="px-1 text-slate-300">•</span> {{ $reading->appointment?->department ?? 'Clinical visit' }}</p></div>@empty<p class="text-sm text-slate-500">No vital readings available yet.</p>@endforelse</div></div><div class="rounded-3xl bg-slate-950 p-5 text-white shadow-sm"><p class="text-sm font-semibold text-teal-300">Need help?</p><h2 class="mt-2 text-lg font-semibold">Understanding your records</h2><p class="mt-2 text-sm leading-6 text-slate-300">Select any record to read its full summary. For clinical questions, contact the listed provider.</p><button id="open-latest-record" type="button" class="mt-5 rounded-xl bg-teal-400 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" {{ $latestMedicalRecord ? '' : 'disabled' }}>View latest record</button></div></div></section>

        <section>
            <div class="flex min-h-[600px] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6"><div><p class="text-sm font-semibold text-teal-700">Care timeline</p><h2 class="mt-1 text-xl font-semibold text-slate-900">Recent health activity</h2></div><p class="text-xs text-slate-500">Select an item for details</p></div>
                <div id="health-activities" class="flex grow flex-col"><div class="divide-y divide-slate-100">
                    @forelse ($healthActivities as $activity)
                        <button type="button" data-record-key="{{ $activity['key'] }}" data-record-type="{{ $activity['type'] }}" data-record-title="{{ $activity['title'] }}" data-record-provider="{{ $activity['provider'] }}" data-record-date="{{ $activity['date']->format('F j, Y') }}" data-record-summary="{{ $activity['summary'] }}" data-record-result="{{ $activity['result'] }}" class="group flex w-full items-center gap-4 px-5 py-4 text-left transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-600 sm:px-6"><div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-2xl {{ $activity['is_vital_reading'] ? 'bg-teal-100 text-teal-900' : 'bg-slate-100 text-slate-700' }}"><span class="text-[9px] font-semibold uppercase tracking-widest">{{ $activity['date']->format('M') }}</span><span class="text-lg font-bold leading-none">{{ $activity['date']->format('d') }}</span></div><div class="min-w-0 grow"><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-900">{{ $activity['title'] }}</h3><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $activity['is_vital_reading'] ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-600' }}">{{ $activity['type'] }}</span></div><p class="mt-1 truncate text-sm text-slate-500">{{ $activity['is_vital_reading'] ? "Heart {$activity['heart_rate']} bpm · {$activity['temperature']} °C · {$activity['blood_pressure']} mmHg" : $activity['provider'] }} <span class="px-1 text-slate-300">•</span> {{ $activity['date']->format('M j, Y') }}</p></div><span class="shrink-0 text-lg text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-teal-700">›</span></button>
                    @empty
                        <div class="px-6 py-14 text-center"><h3 class="font-semibold text-slate-900">No health activity yet</h3><p class="mt-2 text-sm text-slate-500">Records and vital-sign checks will appear here.</p></div>
                    @endforelse
                </div>
                @if ($healthActivities->hasPages())
                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 sm:px-6"><p class="text-xs text-slate-500">Showing {{ $healthActivities->firstItem() }}–{{ $healthActivities->lastItem() }} of {{ $healthActivities->total() }}</p><div class="grid grid-cols-[40px_auto_40px] items-center"><div>@if ($healthActivities->previousPageUrl())<a href="{{ $healthActivities->previousPageUrl() }}" aria-label="Previous page" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-semibold text-slate-600 hover:bg-slate-100">‹</a>@else<span aria-disabled="true" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-semibold text-slate-300">‹</span>@endif</div><div class="flex items-center justify-center gap-1">@for ($pageNumber = 1; $pageNumber <= $healthActivities->lastPage(); $pageNumber++)<a href="{{ $healthActivities->url($pageNumber) }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold {{ $pageNumber === $healthActivities->currentPage() ? 'bg-teal-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $pageNumber }}</a>@endfor</div><div class="text-right">@if ($healthActivities->nextPageUrl())<a href="{{ $healthActivities->nextPageUrl() }}" aria-label="Next page" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-semibold text-slate-600 hover:bg-slate-100">›</a>@else<span aria-disabled="true" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-semibold text-slate-300">›</span>@endif</div></div></div>
                @endif</div>
            </div>

        </section>

        <div id="record-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 p-4" role="dialog" aria-modal="true" aria-labelledby="record-modal-title"><div class="max-h-[85vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-7"><div class="flex items-start justify-between gap-4"><div><span id="record-modal-type" class="inline-flex rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800"></span><h2 id="record-modal-title" class="mt-3 text-2xl font-semibold tracking-tight text-slate-900"></h2><p id="record-modal-meta" class="mt-2 text-sm text-slate-600"></p></div><button id="record-modal-close" type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-600 transition hover:bg-slate-200" aria-label="Close record details">×</button></div><div class="mt-6 border-t border-slate-200 pt-5"><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Record summary</p><p id="record-modal-summary" class="mt-2 text-sm leading-6 text-slate-700"></p></div><div id="record-modal-result-section" class="mt-5 rounded-2xl border border-teal-100 bg-teal-50 p-4"><p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Result summary</p><p id="record-modal-result" class="mt-2 text-sm leading-6 text-teal-950"></p></div><p class="mt-6 text-xs leading-5 text-slate-500">This is a read-only demo record. Contact your healthcare provider if you have questions.</p></div></div>
    </div>
</x-app-layout>
