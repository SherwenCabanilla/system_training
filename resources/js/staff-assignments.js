const panel = document.querySelector('#assignment-panel');

if (panel) {
    const form = document.querySelector('#assignment-form');
    const doctorSearch = document.querySelector('#assignment-doctor-search');
    const doctorId = document.querySelector('#assignment-doctor-id');
    const doctors = [...document.querySelectorAll('#assignment-doctors option')];
    const date = document.querySelector('#assignment-date');
    const time = document.querySelector('#assignment-time');
    const confirm = document.querySelector('#confirm-assignment');
    const error = document.querySelector('#assignment-error');
    const availabilityMessage = document.querySelector('#assignment-availability-message');
    let activeRequest;
    let trigger;
    let loading = false;
    let saving = false;
    let requestVersion = 0;

    const updateSummary = () => {
        document.querySelector('#assignment-summary-doctor').textContent = doctorId.value ? doctorSearch.value : 'Choose a doctor';
        document.querySelector('#assignment-summary-time').textContent = time.value
            ? `${date.value} - ${time.selectedOptions[0].textContent}`
            : 'Choose an available date and time';
        confirm.disabled = saving || loading || !doctorId.value || !time.value;
    };
    const loadTimes = async () => {
        const version = ++requestVersion;
        const previousTime = time.value || activeRequest?.preferred;
        time.replaceChildren(new Option('Choose a doctor and date', ''));
        time.disabled = true;
        loading = false;
        availabilityMessage.textContent = 'Select a doctor and date to check availability.';
        updateSummary();
        if (!activeRequest || !doctorId.value || !date.value) return;
        loading = true;
        time.replaceChildren(new Option('Checking availability...', ''));
        availabilityMessage.textContent = 'Checking the doctor and patient schedules...';
        updateSummary();
        try {
            const url = new URL(activeRequest.availability, window.location.origin);
            url.searchParams.set('doctor_id', doctorId.value);
            url.searchParams.set('date', date.value);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: AbortSignal.timeout(15000) });
            const data = await response.json();
            if (version !== requestVersion || !panel.open) return;
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Unable to check availability.');
            time.replaceChildren(new Option(data.slots.length ? 'Choose a time' : 'No available times', ''));
            data.slots.forEach((slot) => time.add(new Option(slot.label, slot.value)));
            time.disabled = false;
            if (data.slots.some((slot) => slot.value === previousTime)) time.value = previousTime;
            availabilityMessage.textContent = !data.slots.length
                ? 'No available times. Choose another doctor or date.'
                : time.value === activeRequest.preferred
                    ? 'The patient\'s preferred time is available.'
                    : 'Choose an available time. The patient will receive the confirmed schedule.';
        } catch (failure) {
            if (version !== requestVersion) return;
            time.replaceChildren(new Option('Unable to load times', ''));
            availabilityMessage.textContent = failure.message || 'Unable to load times. Select the doctor or date again to retry.';
        } finally {
            if (version === requestVersion) { loading = false; updateSummary(); }
        }
    };
    const closePanel = () => {
        if (saving) return;
        requestVersion++;
        panel.close();
    };
    panel.querySelectorAll('[data-close-assignment]').forEach((button) => button.addEventListener('click', closePanel));
    panel.addEventListener('cancel', (event) => { if (saving) event.preventDefault(); });
    panel.addEventListener('close', () => { requestVersion++; if (trigger?.isConnected) trigger.focus(); });
    panel.addEventListener('click', (event) => { if (event.target === panel) closePanel(); });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-review-assignment]');
        if (!button || saving) return;
        trigger = button;
        activeRequest = { ...button.dataset };
        form.reset();
        doctorSearch.setCustomValidity('');
        doctorId.value = '';
        error.textContent = '';
        form.action = activeRequest.action;
        date.value = activeRequest.preferred.slice(0, 10) < form.dataset.today ? form.dataset.today : activeRequest.preferred.slice(0, 10);
        document.querySelector('#assignment-patient').textContent = activeRequest.patient;
        document.querySelector('#assignment-email').textContent = activeRequest.email;
        document.querySelector('#assignment-concern').textContent = activeRequest.reason;
        document.querySelector('#assignment-preference').textContent = activeRequest.preferredLabel;
        panel.showModal();
        panel.querySelector('.overflow-y-auto').scrollTop = 0;
        loadTimes();
    });
    const chooseDoctor = () => {
        const doctor = doctors.find((option) => option.value === doctorSearch.value);
        doctorId.value = doctor?.dataset.id ?? '';
        doctorSearch.setCustomValidity(doctorSearch.value && !doctor ? 'Choose a doctor from the suggestions.' : '');
        error.textContent = '';
        loadTimes();
    };
    doctorSearch.addEventListener('input', chooseDoctor);
    doctorSearch.addEventListener('change', chooseDoctor);
    date.addEventListener('click', () => { if (typeof date.showPicker === 'function') date.showPicker(); });
    date.addEventListener('change', () => { error.textContent = ''; loadTimes(); });
    time.addEventListener('change', updateSummary);

    const refreshRequests = async () => {
        const url = new URL(window.location.href);
        for (let attempt = 0; attempt < 2; attempt++) {
            const response = await fetch(url, { headers: { Accept: 'text/html' }, cache: 'no-store', signal: AbortSignal.timeout(15000) });
            if (!response.ok || response.redirected) throw new Error('Unable to refresh requests.');
            const incoming = new DOMParser().parseFromString(await response.text(), 'text/html').querySelector('#staff-appointments-list');
            if (!incoming) throw new Error('Unable to refresh requests.');
            if (Number(url.searchParams.get('page') ?? 1) > Number(incoming.dataset.lastPage)) {
                url.searchParams.set('page', incoming.dataset.lastPage);
                continue;
            }
            document.querySelector('#staff-appointments-list').replaceWith(incoming);
            window.history.replaceState({}, '', url);
            return;
        }
        throw new Error('Unable to refresh requests.');
    };
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (saving || loading || !doctorId.value || !time.value || !form.reportValidity()) return;
        saving = true;
        error.textContent = '';
        confirm.textContent = 'Confirming...';
        updateSummary();
        const body = new FormData(form);
        const controls = [...form.querySelectorAll('input, select, button')];
        const previousDisabled = controls.map((control) => control.disabled);
        controls.forEach((control) => { control.disabled = true; });
        try {
            const response = await fetch(form.action, { method: 'PATCH', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body, signal: AbortSignal.timeout(15000) });
            const data = await response.json();
            if (!response.ok) {
                error.textContent = Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Unable to confirm this request.';
                await loadTimes();
                return;
            }
            panel.close();
            const feedback = document.querySelector('#assignment-feedback');
            feedback.textContent = data.message;
            feedback.classList.remove('hidden');
            try {
                await refreshRequests();
            } catch {
                trigger?.closest('[data-assignment-row]')?.remove();
                feedback.textContent = `${data.message} Refresh the list to update totals.`;
            }
            document.querySelector('#staff-appointments-list h2')?.setAttribute('tabindex', '-1');
            document.querySelector('#staff-appointments-list h2')?.focus({ preventScroll: true });
        } catch {
            error.textContent = 'The result could not be confirmed. Check your connection and refresh the requests list before retrying.';
        } finally {
            saving = false;
            controls.forEach((control, index) => { control.disabled = previousDisabled[index]; });
            confirm.textContent = 'Confirm appointment';
            updateSummary();
        }
    });
}
