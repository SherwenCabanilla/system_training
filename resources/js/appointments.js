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
    const visitDateInput = document.querySelector('#appointment-date');
    const visitTimeInput = document.querySelector('#appointment-time');
    let availabilityRequest = 0;
    let availabilityLoading = false;

    const loadAvailableTimes = async () => {
        const requestId = ++availabilityRequest;
        const selected = scheduledAtInput.value;
        scheduledAtInput.value = '';
        visitTimeInput.disabled = true;
        visitTimeInput.replaceChildren(new Option(visitDateInput.value ? 'Loading times...' : 'Choose a date first', ''));
        const needsDoctor = document.querySelector('input[name="booking_mode"]:checked').value === 'choose_doctor' && !doctorIdInput.value;
        visitDateInput.disabled = needsDoctor;
        if (needsDoctor) {
            availabilityLoading = false;
            visitTimeInput.replaceChildren(new Option('Choose a doctor first', ''));
            document.querySelector('#schedule-help').textContent = 'Choose a doctor to see available times.';
            dateFeedback.classList.add('hidden');
            return;
        }
        document.querySelector('#schedule-help').textContent = doctorIdInput.value
            ? 'Showing times for your selected doctor.'
            : 'Your preferred time is subject to staff confirmation.';
        availabilityLoading = Boolean(visitDateInput.value);
        if (!visitDateInput.value) return;
        const params = new URLSearchParams({ date: visitDateInput.value });
        if (doctorIdInput.value) params.set('doctor_id', doctorIdInput.value);
        if (appointmentId.value) params.set('appointment_id', appointmentId.value);
        try {
            const response = await fetch(`${form.dataset.availabilityUrl}?${params}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (requestId !== availabilityRequest) return;
            if (!response.ok) throw new Error(data.message || 'Unable to load available times.');
            visitTimeInput.replaceChildren(new Option(data.slots.length ? 'Choose a time' : 'No available times. Choose another date or doctor.', ''));
            data.slots.forEach((slot) => visitTimeInput.add(new Option(slot.label, slot.value)));
            visitTimeInput.disabled = false;
            if (data.slots.some((slot) => slot.value === selected)) {
                visitTimeInput.value = selected;
                scheduledAtInput.value = selected;
            }
            dateFeedback.classList.add('hidden');
        } catch (error) {
            if (requestId !== availabilityRequest) return;
            visitTimeInput.replaceChildren(new Option('Unable to load times', ''));
            dateFeedback.textContent = error.message || 'Unable to load times. Please try again.';
            dateFeedback.classList.remove('hidden');
        } finally {
            if (requestId === availabilityRequest) availabilityLoading = false;
        }
    };
    visitDateInput.addEventListener('click', () => {
        if (typeof visitDateInput.showPicker === 'function') {
            visitDateInput.showPicker();
        }
    });
    visitDateInput.addEventListener('change', loadAvailableTimes);
    visitTimeInput.addEventListener('change', () => { scheduledAtInput.value = visitTimeInput.value; });

    const confirmationModal = document.querySelector('#appointment-confirmation-modal');
    const confirmationDoctor = document.querySelector('#confirmation-doctor');
    const confirmationDate = document.querySelector('#confirmation-date');
    const confirmationReason = document.querySelector('#confirmation-reason');
    const confirmAppointmentButton = document.querySelector('#confirm-appointment');
    const successToast = document.querySelector('#appointment-success-toast');
    const successMessage = document.querySelector('#appointment-success-message');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const bookingModeInputs = document.querySelectorAll('input[name="booking_mode"]');
    const appointmentModal = document.querySelector('#appointment-modal');
    const openAppointmentModal = () => {
        appointmentModal.showModal();
    };

    const closeAppointmentModal = () => {
        appointmentModal.close();
    };

    const updateBookingMode = () => {
        const isChoosingDoctor = document.querySelector('input[name="booking_mode"]:checked').value === 'choose_doctor';
        doctorSearchInput.placeholder = isChoosingDoctor ? 'Search doctor, specialty, or department' : 'The clinic will assign your doctor';
        doctorSearchInput.required = isChoosingDoctor;
        form.reason.required = !isChoosingDoctor;
        document.querySelector('#appointment-time-label').textContent = isChoosingDoctor ? 'Available time' : 'Preferred time';

        doctorSearchInput.disabled = !isChoosingDoctor;
        if (!isChoosingDoctor) {
            doctorSearchInput.value = '';
            doctorSearchInput.setCustomValidity('');
        }
        doctorIdInput.value = isChoosingDoctor
            ? providerOptions.find((option) => option.value === doctorSearchInput.value)?.dataset.id ?? ''
            : '';
        loadAvailableTimes();
    };

    bookingModeInputs.forEach((input) => input.addEventListener('change', updateBookingMode));
    document.querySelector('#close-booking').addEventListener('click', closeAppointmentModal);

    const appointmentDate = (value) => {
        const parts = value.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);

        if (!parts) {
            return new Date(value);
        }

        const [, year, month, day, hour, minute] = parts;

        return new Date(Number(year), Number(month) - 1, Number(day), Number(hour), Number(minute));
    };

    const timeLeftLabel = (scheduledAt) => {
        const minutesRemaining = Math.ceil((scheduledAt.getTime() - Date.now()) / 60000);

        if (minutesRemaining <= 0) {
            return 'Awaiting clinic update';
        }

        const days = Math.floor(minutesRemaining / 1440);
        const hours = Math.floor((minutesRemaining % 1440) / 60);
        const minutes = minutesRemaining % 60;

        if (days > 0) {
            return `In ${days} day${days === 1 ? '' : 's'}${hours ? ` ${hours} hr` : ''}`;
        }

        if (hours > 0) {
            return `In ${hours} hr${minutes ? ` ${minutes} min` : ''}`;
        }

        return `In ${minutes} min`;
    };

    const updateCountdowns = () => {
        document.querySelectorAll('[data-scheduled-at]').forEach((appointment) => {
            const countdown = appointment.querySelector('[data-countdown]');

            if (!countdown) {
                return;
            }

            const isAwaitingUpdate = appointmentDate(appointment.dataset.scheduledAt) <= new Date();
            countdown.textContent = timeLeftLabel(appointmentDate(appointment.dataset.scheduledAt));
            countdown.classList.toggle('bg-amber-50', !isAwaitingUpdate);
            countdown.classList.toggle('text-amber-800', !isAwaitingUpdate);
            countdown.classList.toggle('bg-slate-100', isAwaitingUpdate);
            countdown.classList.toggle('text-slate-600', isAwaitingUpdate);
        });
    };

    const updateAppointmentCounts = (change) => {
        document.querySelectorAll('[data-upcoming-count]').forEach((element) => {
            element.textContent = Math.max(0, Number(element.textContent) + change);
        });

        document.querySelectorAll('[data-appointment-total]').forEach((element) => {
            element.textContent = Math.max(0, Number(element.textContent) + change);
        });
    };

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

    const updateProviderDetails = () => {
        const provider = providerOptions.find((option) => option.value === doctorSearchInput.value);
        doctorIdInput.value = provider?.dataset.id ?? '';
        doctorSearchInput.setCustomValidity(doctorSearchInput.value && !provider ? 'Please choose a provider from the suggestions.' : '');
        loadAvailableTimes();
    };

    const hasSelectedAvailableTime = () => !availabilityLoading && Boolean(scheduledAtInput.value);

    const reset = () => {
        form.reset();
        bookingModeInputs.forEach((input) => { input.disabled = false; });
        message.textContent = '';
        scheduledAtInput.classList.remove('border-rose-500');
        appointmentId.value = '';
        doctorSearchInput.setCustomValidity('');
        updateBookingMode();
        document.querySelector('#form-title').textContent = 'Book appointment';
        document.querySelector('#form-cancel').classList.add('hidden');
        dateFeedback.classList.add('hidden');
    };

    const edit = (appointment) => {
        reset();
        const bookingMode = appointment.doctor_id ? 'choose_doctor' : 'clinic_assignment';
        document.querySelector(`input[name="booking_mode"][value="${bookingMode}"]`).checked = true;
        updateBookingMode();
        appointmentId.value = appointment.id;
        bookingModeInputs.forEach((input) => {
            input.disabled = input.value !== bookingMode;
        });
        scheduledAtInput.value = new Intl.DateTimeFormat('sv-SE', { timeZone: form.dataset.timezone, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date(appointment.scheduled_at)).replace(' ', 'T');
        visitDateInput.value = scheduledAtInput.value.slice(0, 10);
        const provider = providerOptions.find((option) => option.dataset.id === String(appointment.doctor_id));
        doctorSearchInput.value = provider?.value ?? '';
        form.reason.value = appointment.reason ?? '';
        document.querySelector('#form-title').textContent = 'Edit appointment';
        document.querySelector('#form-cancel').classList.remove('hidden');
        updateProviderDetails();
        openAppointmentModal();
    };

    const closeConfirmationModal = () => {
        confirmationModal.close();
    };

    const refreshDashboardAppointments = async () => {
        const dashboardAppointments = document.querySelector('#dashboard-appointments');

        if (!dashboardAppointments) {
            return;
        }

        try {
            const response = await fetch(window.location.href, { headers: { Accept: 'text/html' } });
            if (!response.ok) {
                throw new Error('Unable to refresh appointments');
            }
            const document = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updatedAppointments = document.querySelector('#dashboard-appointments');
            if (!updatedAppointments) {
                throw new Error('Appointment summary unavailable');
            }
            dashboardAppointments.replaceWith(updatedAppointments);
        } catch {
            successMessage.textContent += ' Refresh the page to update your appointment summary.';
        }
    };

    const showSuccessToast = (appointment) => {
        successMessage.textContent = appointment.status === 'Pending assignment'
            ? 'Your request was saved. Clinic staff will review your concern and assign a doctor.'
            : `Your visit with ${appointment.doctor_name} was saved. A reminder is now in Notifications.`;
        successToast.classList.remove('hidden');

        window.setTimeout(() => successToast.classList.add('hidden'), 6500);
    };

    const openConfirmationModal = () => {
        const isEditing = Boolean(appointmentId.value);
        const isClinicAssignment = document.querySelector('input[name="booking_mode"]:checked').value === 'clinic_assignment';
        const scheduledAt = new Date(scheduledAtInput.value);

        document.querySelector('#confirmation-modal-title').textContent = isEditing ? 'Confirm appointment changes' : 'Confirm your appointment';
        confirmAppointmentButton.textContent = isEditing ? 'Save changes' : 'Confirm appointment';
        confirmationDoctor.textContent = isClinicAssignment ? 'Clinic staff will assign a doctor after reviewing your request' : doctorSearchInput.value;
        confirmationDate.textContent = scheduledAt.toLocaleString([], { dateStyle: 'full', timeStyle: 'short' });
        confirmationReason.textContent = form.reason.value.trim() || 'No reason provided';
        confirmationModal.showModal();
        confirmAppointmentButton.focus();
    };

    const remove = async (id) => {
        if (!confirm('Cancel this appointment?')) return;
        const response = await fetch(`${page.dataset.baseUrl}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
        if (response.ok) {
            document.querySelector(`[data-id="${id}"]`).remove();
            updateAppointmentCounts(-1);
        }
    };

    const render = (appointment) => {
        const article = document.createElement('article');
        article.dataset.id = appointment.id;
        article.className = 'group relative overflow-hidden rounded-2xl border border-slate-200 p-4 transition hover:border-teal-300 hover:shadow-sm sm:p-5';
        article.dataset.scheduledAt = appointment.scheduled_at.slice(0, 16);
        article.innerHTML = '<div class="flex flex-col gap-4 sm:flex-row sm:items-center"><div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-950 text-white"><span data-month class="text-[10px] font-semibold uppercase tracking-widest text-teal-300"></span><span data-day class="text-2xl font-bold leading-none"></span></div><div class="grow"><div class="flex flex-wrap items-center gap-2"><h3 data-doctor class="font-semibold text-slate-900"></h3><span data-status class="rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800"></span><span data-countdown class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800"></span></div><p data-provider class="mt-1 text-sm font-medium text-slate-600"></p><p data-reason class="mt-2 text-sm leading-5 text-slate-500"></p></div><div class="flex shrink-0 gap-3"><button data-edit class="text-sm font-semibold text-teal-700 hover:text-teal-900">Edit</button><button data-delete class="text-sm font-semibold text-rose-600 hover:text-rose-800">Cancel</button></div></div>';
        const scheduledAt = appointmentDate(appointment.scheduled_at);
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
    document.querySelector('#upcoming-tab')?.addEventListener('click', () => showAppointmentTab('upcoming'));
    document.querySelector('#completed-tab')?.addEventListener('click', () => showAppointmentTab('completed'));
    document.querySelector('#form-cancel').addEventListener('click', () => {
        reset();
        closeAppointmentModal();
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('#open-appointment-modal, [data-open-booking]')) {
            reset();
            openAppointmentModal();
        }
    });
    appointmentModal.addEventListener('click', (event) => {
        if (event.target === appointmentModal) closeAppointmentModal();
    });
    document.querySelector('#close-confirmation-modal').addEventListener('click', closeConfirmationModal);
    document.querySelector('#edit-confirmation').addEventListener('click', closeConfirmationModal);
    document.querySelector('#close-success-toast').addEventListener('click', () => successToast.classList.add('hidden'));
    updateCountdowns();
    updateBookingMode();
    window.setInterval(updateCountdowns, 60000);
    confirmationModal.addEventListener('click', (event) => {
        if (event.target === confirmationModal) {
            closeConfirmationModal();
        }
    });
    doctorSearchInput.addEventListener('input', updateProviderDetails);
    doctorSearchInput.addEventListener('change', updateProviderDetails);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity() || !hasSelectedAvailableTime()) return;
        openConfirmationModal();
    });

    confirmAppointmentButton.addEventListener('click', async () => {
        const id = appointmentId.value;
        confirmAppointmentButton.disabled = true;
        confirmAppointmentButton.textContent = 'Saving...';
        try {
            const response = await fetch(id ? `${page.dataset.baseUrl}/${id}` : page.dataset.storeUrl, { method: id ? 'PATCH' : 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: new FormData(form) });
            const data = await response.json();
            if (!response.ok) {
                message.textContent = Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Unable to save your appointment. Please try again.';
                message.className = 'mt-3 text-sm font-medium text-rose-700';
                closeConfirmationModal();
                loadAvailableTimes();
                confirmAppointmentButton.disabled = false;
                confirmAppointmentButton.textContent = id ? 'Save changes' : 'Confirm appointment';
                return;
            }
            if (list) {
                const old = id && document.querySelector(`[data-id="${id}"]`);
                const item = render(data.appointment);
                if (old) {
                    old.replaceWith(item);
                } else {
                    document.querySelector('#empty-state')?.remove();
                    list.prepend(item);
                    updateAppointmentCounts(1);
                }
            } else {
                refreshDashboardAppointments();
            }
            updateCountdowns();
            message.textContent = data.message;
            message.className = 'mt-3 text-sm font-medium text-teal-700';
            closeConfirmationModal();
            showSuccessToast(data.appointment);
            reset();
            closeAppointmentModal();
        } catch {
            message.textContent = 'Unable to save your appointment. Check your connection and try again.';
            message.className = 'mt-3 text-sm font-medium text-rose-700';
            closeConfirmationModal();
        } finally {
            confirmAppointmentButton.disabled = false;
            confirmAppointmentButton.textContent = id ? 'Save changes' : 'Confirm appointment';
        }
    });
}
