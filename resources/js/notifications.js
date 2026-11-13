const notifications = document.querySelector('#patient-notifications');

if (notifications) {
    const toast = document.querySelector('#patient-notification-toast');
    const storageKey = `patient-notifications-seen:${notifications.dataset.userId}`;
    let seenId = 0;
    let polling = false;
    let stopped = false;
    let dismissTimer;
    let queued = [];
    const readSeenId = () => {
        try {
            const stored = Number(localStorage.getItem(storageKey));
            if (Number.isSafeInteger(stored) && stored >= 0) seenId = Math.max(seenId, stored);
        } catch {
            // Keep session deduplication when browser storage is unavailable.
        }
        return seenId;
    };
    const remember = (id) => {
        seenId = Math.max(readSeenId(), id);
        try { localStorage.setItem(storageKey, String(seenId)); } catch { /* In-memory fallback. */ }
    };
    const scheduleDismiss = () => {
        clearTimeout(dismissTimer);
        dismissTimer = setTimeout(() => {
            if (!toast.matches(':hover') && !toast.contains(document.activeElement)) dismiss();
        }, 10000);
    };
    const showQueued = () => {
        if (!toast.classList.contains('hidden') || document.hidden || document.querySelector('dialog[open]')) return;
        queued = queued.filter((item) => item.id > readSeenId());
        if (!queued.length) return;
        const batch = queued;
        queued = [];
        const latest = batch[batch.length - 1];
        document.querySelector('#patient-notification-title').textContent = batch.length > 1 ? `${batch.length} new updates` : latest.title;
        document.querySelector('#patient-notification-body').textContent = batch.length > 1 ? `${latest.title}: ${latest.body}` : latest.body;
        const link = document.querySelector('#patient-notification-link');
        link.href = batch.length > 1 ? notifications.dataset.notificationsUrl : latest.url;
        link.textContent = batch.length > 1 ? 'View all notifications' : latest.link_label;
        toast.classList.remove('hidden');
        remember(latest.id);
        scheduleDismiss();
    };
    const dismiss = () => {
        clearTimeout(dismissTimer);
        toast.classList.add('hidden');
        showQueued();
    };
    document.querySelector('#dismiss-patient-notification').addEventListener('click', dismiss);
    toast.addEventListener('mouseenter', () => clearTimeout(dismissTimer));
    toast.addEventListener('mouseleave', scheduleDismiss);
    toast.addEventListener('focusin', () => clearTimeout(dismissTimer));
    toast.addEventListener('focusout', scheduleDismiss);

    const poll = async () => {
        if (polling || stopped || document.hidden) return;
        polling = true;
        try {
            const url = new URL(notifications.dataset.feedUrl, window.location.origin);
            url.searchParams.set('after', readSeenId());
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: AbortSignal.timeout(15000) });
            if (response.status === 401 || response.status === 403) { stopped = true; return; }
            if (!response.ok) return;
            const data = await response.json();
            document.querySelectorAll('[data-notification-count]').forEach((badge) => {
                badge.classList.toggle('hidden', data.unread_count === 0);
                badge.querySelector('[data-notification-number]').textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                badge.querySelector('[data-notification-label]').textContent = data.unread_count ? `${data.unread_count} unread notifications` : '';
            });
            queued = data.notifications.filter((item) => item.id > readSeenId());
            showQueued();
            window.dispatchEvent(new CustomEvent('patient-notifications-updated', { detail: { revision: data.revision } }));
        } catch {
            // Retry on the next poll after a network failure.
        } finally {
            polling = false;
        }
    };
    document.addEventListener('close', showQueued, true);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) { showQueued(); poll(); }
    });
    window.addEventListener('online', poll);
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) readSeenId();
    });
    poll();
    setInterval(poll, 30000);
}
