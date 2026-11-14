export function initializeAppointmentFilters(list, controls, emptyMessage) {
    if (!list || !controls || !emptyMessage) return () => {};
    const buttons = [...controls.querySelectorAll('[data-appointment-filter]')];
    const upcomingPanel = list.closest?.('[role="tabpanel"]');
    const historyPanel = controls.ownerDocument?.querySelector('#completed-panel');
    let activeFilter = 'all';

    const refresh = () => {
        const appointments = [...list.querySelectorAll('[data-id][data-scheduled-at]')];
        const allButton = buttons.find((button) => button.dataset.appointmentFilter === 'all');
        const allCount = Number(allButton?.querySelector('[data-filter-count]')?.textContent) || appointments.length;
        const counts = { all: allCount, pending: 0, scheduled: 0, reschedule: 0, approval: 0 };
        const now = Date.now();
        appointments.forEach((appointment) => {
            const pending = appointment.dataset.appointmentStatus === 'Pending assignment';
            const category = appointment.dataset.appointmentStatus === 'Awaiting patient approval' ? 'approval' : pending
                ? (new Date(appointment.dataset.scheduledAt).getTime() <= now ? 'reschedule' : 'pending')
                : 'scheduled';
            counts[category]++;
            appointment.classList.toggle('hidden', activeFilter !== 'all' && activeFilter !== category);
        });
        buttons.forEach((button) => {
            const selected = button.dataset.appointmentFilter === activeFilter;
            button.setAttribute('aria-selected', String(selected));
            button.setAttribute('tabindex', selected ? '0' : '-1');
            if (button.dataset.appointmentFilter !== 'history') {
                button.querySelector('[data-filter-count]').textContent = counts[button.dataset.appointmentFilter];
            }
        });
        upcomingPanel?.classList.toggle('hidden', activeFilter === 'history');
        historyPanel?.classList.toggle('hidden', activeFilter !== 'history');
        const activeButton = buttons.find((button) => button.dataset.appointmentFilter === activeFilter);
        if (activeFilter !== 'history' && activeButton) {
            upcomingPanel?.setAttribute('aria-labelledby', activeButton.id);
        }
        const existingEmptyState = list.querySelector('#empty-state');
        emptyMessage.classList.toggle('hidden', counts[activeFilter] > 0 || (activeFilter === 'all' && Boolean(existingEmptyState)));
        existingEmptyState?.classList.toggle('hidden', activeFilter !== 'all');
        document.querySelector('#load-more-appointments')?.parentElement.classList.toggle('hidden', activeFilter !== 'all');
    };
    buttons.forEach((button) => button.addEventListener('click', () => {
        activeFilter = button.dataset.appointmentFilter;
        refresh();
    }));
    buttons.forEach((button, index) => button.addEventListener('keydown', (event) => {
        let target;
        if (event.key === 'ArrowRight') target = (index + 1) % buttons.length;
        if (event.key === 'ArrowLeft') target = (index - 1 + buttons.length) % buttons.length;
        if (event.key === 'Home') target = 0;
        if (event.key === 'End') target = buttons.length - 1;
        if (target === undefined) return;
        event.preventDefault();
        buttons[target].focus();
        buttons[target].click();
    }));
    return refresh;
}
