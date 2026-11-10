const page = document.querySelector('#medical-records-page');

if (page) {
    const modal = document.querySelector('#record-modal');
    const closeButton = document.querySelector('#record-modal-close');
    const resultSection = document.querySelector('#record-modal-result-section');
    const healthActivities = document.querySelector('#health-activities');

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const openRecord = (button) => {
        document.querySelector('#record-modal-type').textContent = button.dataset.recordType;
        document.querySelector('#record-modal-title').textContent = button.dataset.recordTitle;
        document.querySelector('#record-modal-meta').textContent = `${button.dataset.recordProvider} • ${button.dataset.recordDate}`;
        document.querySelector('#record-modal-summary').textContent = button.dataset.recordSummary;
        document.querySelector('#record-modal-result').textContent = button.dataset.recordResult;
        resultSection.classList.toggle('hidden', !button.dataset.recordResult);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        closeButton.focus();
    };

    healthActivities?.addEventListener('click', async (event) => {
        const recordButton = event.target.closest('[data-record-title]');
        if (recordButton) {
            openRecord(recordButton);
            return;
        }

        const pageLink = event.target.closest('a[href]');
        if (!pageLink) {
            return;
        }

        event.preventDefault();
        healthActivities.classList.add('pointer-events-none', 'opacity-50');
        const response = await fetch(pageLink.href, { headers: { Accept: 'application/json' } });
        if (response.ok) {
            const data = await response.json();
            healthActivities.innerHTML = data.html;
            window.history.replaceState({}, '', pageLink.href);
        }
        healthActivities.classList.remove('pointer-events-none', 'opacity-50');
    });

    document.querySelector('#open-latest-record')?.addEventListener('click', () => {
        const latestRecord = document.querySelector('[data-record-title]');
        if (latestRecord) {
            openRecord(latestRecord);
        }
    });

    closeButton.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
}
