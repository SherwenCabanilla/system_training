import { confirmAction } from './confirmation-dialog';

document.querySelectorAll('[data-confirm-logout]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (await confirmAction({
            title: 'Log out of your account?',
            message: 'You will need to sign in again to access your care information.',
            confirmLabel: 'Log out',
            cancelLabel: 'Stay signed in',
        })) {
            form.submit();
        }
    });
});
