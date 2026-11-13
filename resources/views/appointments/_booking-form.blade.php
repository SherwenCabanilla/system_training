<dialog id="appointment-modal" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-3xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="form-title">
    <div>
        <form id="appointment-form" data-timezone="{{ config('app.timezone') }}" data-availability-url="{{ route('appointments.availability') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
            @csrf
            <input id="appointment-id" type="hidden">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-teal-700">Request a visit</p>
                    <h2 id="form-title" class="mt-1 text-xl font-semibold text-slate-900">Book appointment</h2>
                </div>
                <button id="close-booking" type="button" aria-label="Close appointment form" class="rounded-xl px-3 py-2 text-xl text-slate-500 hover:bg-slate-100">&times;</button>
            </div>
            <p class="mt-3 text-sm leading-6 text-slate-600">Choose how you would like to book, pick a date and time, then tell us what brings you in.</p>
            <div class="mt-5 space-y-5">
                <fieldset>
                    <legend class="text-sm font-semibold text-slate-700">Do you have a doctor in mind?</legend>
                    <div class="mt-2 grid gap-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50">
                            <input name="booking_mode" type="radio" value="clinic_assignment" checked class="mt-1 border-slate-300 text-teal-700 focus:ring-teal-600">
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">Let the clinic assign a doctor</span>
                                <span class="block text-xs text-slate-600">No preference? We will help you find the right doctor.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50">
                            <input name="booking_mode" type="radio" value="choose_doctor" class="mt-1 border-slate-300 text-teal-700 focus:ring-teal-600">
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">I have a preferred doctor</span>
                                <span class="block text-xs text-slate-600">Choose someone you already know.</span>
                            </span>
                        </label>
                    </div>
                </fieldset>
                <div id="doctor-choice">
                    <label class="text-sm font-semibold text-slate-700" for="doctor-search">Doctor</label>
                    <input id="doctor-search" type="text" list="provider-directory" autocomplete="off" placeholder="The clinic will assign your doctor" aria-describedby="doctor-choice-help" class="mt-2 block w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500" disabled>
                    <p id="doctor-choice-help" class="mt-2 text-xs text-slate-500">Choose a preferred doctor or leave the assignment to staff.</p>
                    <input id="doctor_id" name="doctor_id" type="hidden">
                    <datalist id="provider-directory">@foreach ($doctors as $doctor)<option value="{{ $doctor->name }} — {{ $doctor->department }} — {{ $doctor->specialty }}" data-id="{{ $doctor->id }}" data-department="{{ $doctor->department }}" data-specialty="{{ $doctor->specialty }}" data-availability="{{ $doctor->availability_note }}">
                        </option>@endforeach</datalist>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700" for="appointment-date">Preferred date</label>
                    <input id="appointment-date" aria-describedby="schedule-help" type="date" min="{{ now()->toDateString() }}" class="mt-2 block w-full cursor-pointer rounded-xl border-slate-300 px-3 py-2.5 text-sm focus:border-teal-600 focus:ring-teal-600 disabled:cursor-not-allowed disabled:bg-slate-100" required>
                    <label class="mt-3 block text-sm font-semibold text-slate-700" id="appointment-time-label" for="appointment-time">Preferred time</label>
                    <select id="appointment-time" class="mt-2 block w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm focus:border-teal-600 focus:ring-teal-600" required disabled>
                        <option value="">Choose a date first</option>
                    </select>
                    <p id="schedule-help" class="mt-2 min-h-8 text-xs text-slate-500" aria-live="polite">Your preferred time is subject to staff confirmation.</p>
                    <input id="scheduled_at" name="scheduled_at" type="hidden">
                    <p id="date-feedback" class="mt-2 hidden text-xs font-medium text-rose-700" role="alert"></p>
                    <p class="mt-2 text-xs text-slate-500">Clinic hours: 8:00 AM to 5:00 PM. Visits last 30 minutes; last start is 4:30 PM. Times are in {{ config('app.timezone') }}. Doctor availability is checked again when you book.</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700" for="reason">What brings you in?</label>
                    <textarea id="reason" name="reason" rows="3" maxlength="1000" required aria-describedby="reason-help" placeholder="Describe your symptoms, concern, or reason for a check-up in your own words." class="mt-2 block w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600"></textarea>
                    <p id="reason-help" class="mt-2 text-xs text-slate-500">A brief description helps the clinic arrange your care. No medical terms needed.</p>
                </div>
            </div>
            <div class="mt-6 flex items-center gap-3">
                <button id="form-submit" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Review appointment</button>
                <button id="form-cancel" type="button" class="hidden text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel edit</button>
            </div>
            <p id="form-message" class="mt-3 text-sm font-medium" aria-live="polite">
            </p>
        </form>
        <div class="mt-4 rounded-2xl border border-teal-100 bg-teal-50 p-4">
            <p class="text-sm font-semibold text-teal-900">Need a reminder?</p>
            <p class="mt-1 text-xs leading-5 text-teal-800">Appointment reminders appear in your Notifications area.</p>
        </div>
    </div>
</dialog>

