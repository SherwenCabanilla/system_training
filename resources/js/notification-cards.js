document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-open-notification]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('button');
    if (button.disabled) return;
    const error = form.querySelector('[data-notification-error]');
    button.disabled = true;
    error.textContent = '';
    try {
        const response = await fetch(form.action, {
            method: 'PATCH', body: new FormData(form),
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            signal: AbortSignal.timeout(15000),
        });
        if (!response.ok) throw new Error('Unable to open notification. Please try again.');
        const data = await response.json();
        window.location.assign(data.url);
    } catch {
        error.textContent = 'Unable to open notification. Please try again.';
        button.disabled = false;
    }
});
