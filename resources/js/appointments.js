const page = document.querySelector('#appointments-page');

if (page) {
    const form = document.querySelector('#appointment-form');
    const list = document.querySelector('#appointment-list');
    const message = document.querySelector('#form-message');
    const appointmentId = document.querySelector('#appointment-id');
    const doctorIdInput = document.querySelector('#doctor_id');
    const doctorSearchInput = document.querySelector('#doctor-search');
    const scheduledAtInput = document.querySelector('#scheduled_at');
    const dateFeedback = document.querySelector('#date-feedback');
    const confirmation = document.querySelector('#booking-confirmation');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const showAppointmentTab = (category) => {
        const isUpcoming = category === 'upcoming';
        document.querySelector('#upcoming-panel').classList.toggle('hidden', !isUpcoming);
        document.querySelector('#completed-panel').classList.toggle('hidden', isUpcoming);

        ['upcoming', 'completed'].forEach((tab) => {
            const isActive = tab === category;
            const button = document.querySelector(`#${tab}-tab`);
            button.setAttribute('aria-selected', String(isActive));
            button.classList.toggle('border-teal-700', isActive);
            button.classList.toggle('text-teal-800', isActive);
            button.classList.toggle('border-transparent', !isActive);
            button.classList.toggle('text-slate-500', !isActive);
        });
    };

    const providerOptions = [...document.querySelectorAll('#provider-directory option')];

    const showProviderSearch = () => {
        document.querySelector('#provider-search-area').classList.remove('hidden');
        document.querySelector('#selected-provider').classList.add('hidden');
    };

    const showSelectedProvider = (provider) => {
        document.querySelector('#selected-provider-name').textContent = provider.value;
        document.querySelector('#provider-search-area').classList.add('hidden');
        document.querySelector('#selected-provider').classList.remove('hidden');
    };

    const updateProviderDetails = () => {
        const provider = providerOptions.find((option) => option.value === doctorSearchInput.value);
        const details = document.querySelector('#doctor-details');

        doctorIdInput.value = provider?.dataset.id ?? '';
        doctorSearchInput.setCustomValidity(doctorSearchInput.value && !provider ? 'Please choose a provider from the suggestions.' : '');

        if (!provider) {
            details.classList.add('hidden');
            showProviderSearch();
            return;
        }

        document.querySelector('#doctor-specialty').textContent = `${provider.dataset.department} - ${provider.dataset.specialty}`;
        document.querySelector('#doctor-availability').textContent = `Demo availability: ${provider.dataset.availability}`;
        details.classList.remove('hidden');
        showSelectedProvider(provider);
    };

    const hasValidFutureDate = () => {
        if (!scheduledAtInput.value) return true;
        const isFutureDate = new Date(scheduledAtInput.value) > new Date();
        dateFeedback.textContent = isFutureDate ? '' : 'Please choose a future date and time. Past appointment dates are not available.';
        dateFeedback.classList.toggle('hidden', isFutureDate);
        scheduledAtInput.classList.toggle('border-rose-500', !isFutureDate);
        return isFutureDate;
    };

    const reset = (preserveConfirmation = false) => {
        form.reset();
        appointmentId.value = '';
        doctorSearchInput.setCustomValidity('');
        showProviderSearch();
        document.querySelector('#form-title').textContent = 'Book appointment';
        document.querySelector('#form-cancel').classList.add('hidden');
        document.querySelector('#doctor-details').classList.add('hidden');
        dateFeedback.classList.add('hidden');
        if (!preserveConfirmation) confirmation.classList.add('hidden');
    };

    const edit = (appointment) => {
        appointmentId.value = appointment.id;
        scheduledAtInput.value = appointment.scheduled_at.slice(0, 16);
        const provider = providerOptions.find((option) => option.dataset.id === String(appointment.doctor_id));
        doctorSearchInput.value = provider?.value ?? '';
        form.reason.value = appointment.reason ?? '';
        form.status.value = appointment.status;
        document.querySelector('#form-title').textContent = 'Edit appointment';
        document.querySelector('#form-cancel').classList.remove('hidden');
        confirmation.classList.add('hidden');
        updateProviderDetails();
    };

    const remove = async (id) => {
        if (!confirm('Cancel this appointment?')) return;
        const response = await fetch(`${page.dataset.baseUrl}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
        if (response.ok) document.querySelector(`[data-id="${id}"]`).remove();
    };

    const render = (appointment) => {
        const article = document.createElement('article');
        article.dataset.id = appointment.id;
        article.className = 'group relative overflow-hidden rounded-2xl border border-slate-200 p-4 transition hover:border-teal-300 hover:shadow-sm sm:p-5';
        article.innerHTML = '<div class="flex flex-col gap-4 sm:flex-row sm:items-center"><div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-950 text-white"><span data-month class="text-[10px] font-semibold uppercase tracking-widest text-teal-300"></span><span data-day class="text-2xl font-bold leading-none"></span></div><div class="grow"><div class="flex flex-wrap items-center gap-2"><h3 data-doctor class="font-semibold text-slate-900"></h3><span data-status class="rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800"></span></div><p data-provider class="mt-1 text-sm font-medium text-slate-600"></p><p data-reason class="mt-2 text-sm leading-5 text-slate-500"></p></div><div class="flex shrink-0 gap-3"><button data-edit class="text-sm font-semibold text-teal-700 hover:text-teal-900">Edit</button><button data-delete class="text-sm font-semibold text-rose-600 hover:text-rose-800">Cancel</button></div></div>';
        const scheduledAt = new Date(appointment.scheduled_at);
        article.querySelector('[data-month]').textContent = scheduledAt.toLocaleDateString(undefined, { month: 'short' });
        article.querySelector('[data-day]').textContent = scheduledAt.getDate();
        article.querySelector('[data-doctor]').textContent = appointment.doctor_name;
        article.querySelector('[data-status]').textContent = appointment.status;
        article.querySelector('[data-provider]').textContent = `${appointment.department} - ${scheduledAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
        article.querySelector('[data-reason]').textContent = appointment.reason || 'No visit reason provided.';
        article.querySelector('[data-edit]').addEventListener('click', () => edit(appointment));
        article.querySelector('[data-delete]').addEventListener('click', () => remove(appointment.id));
        return article;
    };

    document.querySelectorAll('[data-edit]').forEach((button) => button.addEventListener('click', () => edit(JSON.parse(button.dataset.edit))));
    document.querySelectorAll('[data-delete]').forEach((button) => button.addEventListener('click', () => remove(button.dataset.delete)));
    document.querySelector('#upcoming-tab').addEventListener('click', () => showAppointmentTab('upcoming'));
    document.querySelector('#completed-tab').addEventListener('click', () => showAppointmentTab('completed'));
    document.querySelector('#form-cancel').addEventListener('click', reset);
    scheduledAtInput.addEventListener('click', () => {
        if (typeof scheduledAtInput.showPicker === 'function') {
            scheduledAtInput.showPicker();
        }
    });
    scheduledAtInput.addEventListener('change', () => {
        hasValidFutureDate();
        scheduledAtInput.blur();
    });

    doctorSearchInput.addEventListener('input', updateProviderDetails);
    doctorSearchInput.addEventListener('change', updateProviderDetails);
    document.querySelector('#change-provider').addEventListener('click', () => {
        doctorSearchInput.value = '';
        doctorIdInput.value = '';
        document.querySelector('#doctor-details').classList.add('hidden');
        showProviderSearch();
        doctorSearchInput.focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!hasValidFutureDate() || !form.reportValidity()) return;
        const id = appointmentId.value;
        const response = await fetch(id ? `${page.dataset.baseUrl}/${id}` : page.dataset.storeUrl, { method: id ? 'PATCH' : 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: new FormData(form) });
        const data = await response.json();
        if (!response.ok) { message.textContent = Object.values(data.errors ?? {}).flat().join(' '); message.className = 'mt-3 text-sm font-medium text-rose-700'; return; }
        const old = id && document.querySelector(`[data-id="${id}"]`);
        const item = render(data.appointment);
        if (old) old.replaceWith(item); else { document.querySelector('#empty-state')?.remove(); list.prepend(item); }
        message.textContent = data.message;
        message.className = 'mt-3 text-sm font-medium text-teal-700';
        confirmation.classList.remove('hidden');
        confirmation.querySelector('#confirmation-details').textContent = `Your ${data.appointment.department} appointment with ${data.appointment.doctor_name} is scheduled for ${new Date(data.appointment.scheduled_at).toLocaleString()}. A reminder was added to your notifications.`;
        reset(true);
    });
}
