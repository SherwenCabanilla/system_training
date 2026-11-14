<dialog x-ref="medicalRecordDialog" aria-labelledby="medical-record-title" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-3xl p-0 backdrop:bg-slate-950/50">
    <form method="POST" :action="medicalRecordForm.action" x-data="{ recordVitals: false }" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-7">
        @csrf
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-teal-700">Clinical record</p>
                <h2 id="medical-record-title" class="mt-1 text-2xl font-semibold text-slate-900">Doctor's assessment</h2>
                <p class="mt-2 text-sm text-slate-600"><span x-text="medicalRecordForm.patientName"></span> <span class="text-slate-300">&bull;</span> <span x-text="medicalRecordForm.department"></span></p>
            </div>
            <button type="button" @click="$refs.medicalRecordDialog.close()" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xl text-slate-500 transition hover:bg-slate-100" aria-label="Close clinical record form">&times;</button>
        </div>

        <div class="mt-4 space-y-2 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-900">
            <p>Record the clinician's diagnosis, assessment, and any treatment or follow-up plan. This will be visible to the patient in Medical Records.</p>
            <p class="text-xs leading-5 text-teal-800">Vital signs are optional. Turn them on below only when they were measured during this visit.</p>
        </div>

        <div class="mt-6 grid gap-5">
            <div>
                <label for="medical-record-recorded_on" class="text-sm font-semibold text-slate-700">Date recorded</label>
                <input id="medical-record-recorded_on" name="recorded_on" type="date" x-model="medicalRecordForm.recordedOn" class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" required>
            </div>
            <div>
                <label for="medical-record-title-input" class="text-sm font-semibold text-slate-700">Diagnosis or health issue</label>
                <input id="medical-record-title-input" name="title" type="text" maxlength="255" placeholder="Example: Urinary tract infection (UTI)" class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" required>
            </div>
            <div>
                <label for="medical-record-summary" class="text-sm font-semibold text-slate-700">Clinical assessment and notes</label>
                <textarea id="medical-record-summary" name="summary" rows="5" maxlength="5000" placeholder="Document the clinician's findings and explanation for the patient." class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" required></textarea>
            </div>
            <div>
                <label for="medical-record-result_summary" class="text-sm font-semibold text-slate-700">Treatment or follow-up plan <span class="font-normal text-slate-500">(optional)</span></label>
                <textarea id="medical-record-result_summary" name="result_summary" rows="4" maxlength="5000" placeholder="Example: Prescribed antibiotics; follow up if symptoms do not improve." class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600"></textarea>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <label class="flex cursor-pointer items-center justify-between gap-4">
                    <span><span class="block text-sm font-semibold text-slate-800">Record vital signs</span><span class="mt-1 block text-xs leading-5 text-slate-500">Only enable this when measurements were taken.</span></span>
                    <input name="record_vitals" type="checkbox" value="1" x-model="recordVitals" class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                </label>
                <div x-show="recordVitals" x-cloak class="mt-4 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    <div><label for="medical-record-heart-rate" class="text-sm font-semibold text-slate-700">Heart rate (bpm)</label><input id="medical-record-heart-rate" name="heart_rate" type="number" min="20" max="250" :required="recordVitals" x-model="medicalRecordForm.heartRate" class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600"></div>
                    <div><label for="medical-record-temperature" class="text-sm font-semibold text-slate-700">Temperature (&deg;C)</label><input id="medical-record-temperature" name="temperature" type="number" min="30" max="45" step="0.1" :required="recordVitals" x-model="medicalRecordForm.temperature" class="mt-2 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600"></div>
                    <div class="sm:col-span-2"><span class="text-sm font-semibold text-slate-700">Blood pressure (mmHg)</span><div class="mt-2 grid grid-cols-2 gap-3"><input aria-label="Systolic blood pressure" name="systolic" type="number" min="70" max="250" placeholder="Systolic" :required="recordVitals" x-model="medicalRecordForm.systolic" class="block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600"><input aria-label="Diastolic blood pressure" name="diastolic" type="number" min="40" max="160" placeholder="Diastolic" :required="recordVitals" x-model="medicalRecordForm.diastolic" class="block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600"></div></div>
                </div>
            </div>
        </div>

        <div class="mt-7 flex flex-wrap justify-end gap-3">
            <button type="button" @click="$refs.medicalRecordDialog.close()" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
            <button type="submit" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-teal-800">Save clinical record</button>
        </div>
    </form>
</dialog>
