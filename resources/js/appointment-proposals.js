document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-proposal-response]');
    if (!form) return;
    event.preventDefault();
    if (form.dataset.saving === 'true') return;
    const body = new FormData(form);
    if (event.submitter?.name) body.set(event.submitter.name, event.submitter.value);
    const buttons = [...form.querySelectorAll('button')];
    const feedback = form.querySelector('[data-proposal-feedback]');
    form.dataset.saving = 'true';
    buttons.forEach((button) => { button.disabled = true; });
    feedback.textContent = 'Saving your response...';
    try {
        const response = await fetch(form.action, {
            method: 'PATCH', body,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            signal: AbortSignal.timeout(15000),
        });
        const data = await response.json();
        feedback.textContent = Object.values(data.errors ?? {}).flat().join(' ') || data.message;
        if (response.ok || response.status === 409) {
            window.dispatchEvent(new CustomEvent('patient-notifications-updated', { detail: { revision: `proposal-${Date.now()}` } }));
            window.dispatchEvent(new Event('staff-proposal-updated'));
        } else {
            buttons.forEach((button) => { button.disabled = false; });
        }
    } catch {
        feedback.textContent = 'Unable to confirm the result. Refresh your appointments before retrying.';
    } finally {
        form.dataset.saving = 'false';
    }
});
