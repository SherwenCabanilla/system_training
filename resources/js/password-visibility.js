document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.querySelector(`#${button.getAttribute('aria-controls')}`);
    const label = button.querySelector('[data-password-toggle-label]');

    if (!input || !label) {
        return;
    }

    button.addEventListener('click', () => {
        const isVisible = input.type === 'text';

        input.type = isVisible ? 'password' : 'text';
        label.textContent = isVisible ? 'Show' : 'Hide';
        button.setAttribute('aria-pressed', String(!isVisible));
    });
});
