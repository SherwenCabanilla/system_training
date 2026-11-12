@if (session('status'))
    <div class="rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm font-semibold text-teal-900" role="status">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-900" role="alert">
        <p class="font-semibold">Please review the following details.</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

