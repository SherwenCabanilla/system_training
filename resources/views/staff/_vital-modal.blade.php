<dialog x-ref="vitalDialog" aria-labelledby="vital-title" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-3xl p-0 backdrop:bg-slate-950/50">
    <form method="POST" :action="vitalForm.action" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-7">
        @csrf
        @method('PUT')
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-teal-700">Completed visit</p>
                <h2 id="vital-title" class="mt-1 text-2xl font-semibold text-slate-900">Visit measurements</h2>
                <p class="mt-2 text-sm text-slate-600">
                    <span x-text="vitalForm.patientName">
                    </span> <span class="text-slate-300">&bull;</span> <span x-text="vitalForm.department">
                    </span>
                </p>
            </div>
            <button type="button" @click="$refs.vitalDialog.close()" class="flex h-10 w-10 items-center justify-center rounded-xl text-xl text-slate-500 transition hover:bg-slate-100" aria-label="Close measurement form">&times;</button>
        </div>
        <p class="mt-4 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-900">Enter the readings recorded during this visit. The patient will see them in Medical Records after saving.</p>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="vital-recorded_on" class="text-sm font-semibold text-slate-700">Date recorded</label>
                <input id="vital-recorded_on" name="recorded_on" type="date" x-model="vitalForm.recordedOn" class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" required>
            </div>
            <div>
                <label for="vital-heart_rate" class="text-sm font-semibold text-slate-700">Heart rate (bpm)</label>
                <input id="vital-heart_rate" name="heart_rate" type="number" min="20" max="250" x-model="vitalForm.heartRate" class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" required>
            </div>
            <div>
                <label for="vital-temperature" class="text-sm font-semibold text-slate-700">Temperature (&deg;C)</label>
                <input id="vital-temperature" name="temperature" type="number" min="30" max="45" step="0.1" x-model="vitalForm.temperature" class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" required>
            </div>
            <div>
                <label class="text-sm font-semibold text-slate-700">Blood pressure</label>
                <div class="mt-2 flex items-center gap-2">
                    <input aria-label="Systolic blood pressure" name="systolic" type="number" min="70" max="250" x-model="vitalForm.systolic" class="block min-w-0 w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" placeholder="Top" required>
                    <span class="font-semibold text-slate-400">/</span>
                    <input aria-label="Diastolic blood pressure" name="diastolic" type="number" min="40" max="160" x-model="vitalForm.diastolic" class="block min-w-0 w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" placeholder="Bottom" required>
                </div>
            </div>
        </div>
        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" @click="$refs.vitalDialog.close()" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
            <button class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-800">Save measurements</button>
        </div>
    </form>
</dialog>
