export function confirmAction({ title, message, confirmLabel, cancelLabel = 'Cancel' }) {
    const dialog = document.querySelector('#confirmation-dialog');

    if (!dialog) {
        return Promise.resolve(false);
    }

    dialog.querySelector('#confirmation-dialog-title').textContent = title;
    dialog.querySelector('#confirmation-dialog-message').textContent = message;
    dialog.querySelector('#confirmation-dialog-confirm').textContent = confirmLabel;
    dialog.querySelector('#confirmation-dialog-cancel').textContent = cancelLabel;

    return new Promise((resolve) => {
        dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true });
        dialog.showModal();
    });
}
