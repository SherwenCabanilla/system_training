<section id="{{ $id }}" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $id === 'overdue' && $appointments->total() ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ $appointments->total() }} visits</span>
    </div>
    <div class="space-y-2 p-3 sm:p-4">
        @forelse ($appointments as $appointment)
            @include('staff._appointment-item', ['appointment' => $appointment, 'canRecordMeasurements' => true])
        @empty
            <p class="px-4 py-10 text-center text-sm text-slate-500">{{ $emptyMessage }}</p>
        @endforelse
    </div>
    @if ($appointments->hasPages())
        <div class="border-t border-slate-100 p-5">{{ $appointments->links() }}</div>
    @endif
</section>
