const page = document.querySelector('#appointments-page');

if (page) {
    const form = document.querySelector('#appointment-form');
    const list = document.querySelector('#appointment-list');
    const message = document.querySelector('#form-message');
    const appointmentId = document.querySelector('#appointment-id');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const reset = () => {
        form.reset();
        appointmentId.value = '';
        document.querySelector('#form-title').textContent = 'Book appointment';
        document.querySelector('#form-cancel').classList.add('hidden');
    };

    const edit = (appointment) => {
        appointmentId.value = appointment.id;
        form.scheduled_at.value = appointment.scheduled_at.slice(0, 16);
        form.doctor_name.value = appointment.doctor_name;
        form.department.value = appointment.department;
        form.reason.value = appointment.reason ?? '';
        form.status.value = appointment.status;
        document.querySelector('#form-title').textContent = 'Edit appointment';
        document.querySelector('#form-cancel').classList.remove('hidden');
    };

    const remove = async (id) => {
        if (!confirm('Cancel this appointment?')) return;
        const response = await fetch(`${page.dataset.baseUrl}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
        if (response.ok) document.querySelector(`[data-id="${id}"]`).remove();
    };

    const render = (appointment) => {
        const article = document.createElement('article');
        article.dataset.id = appointment.id;
        article.className = 'rounded-xl border border-slate-200 p-4';
        article.innerHTML = '<p data-scheduled class="font-semibold"></p><p data-provider class="text-sm text-slate-600"></p><p data-reason class="mt-1 text-sm text-slate-500"></p><div class="mt-3 flex gap-3"><button data-edit class="text-sm font-semibold text-teal-700">Edit</button><button data-delete class="text-sm font-semibold text-red-600">Cancel appointment</button></div>';
        article.querySelector('[data-scheduled]').textContent = new Date(appointment.scheduled_at).toLocaleString();
        article.querySelector('[data-provider]').textContent = `${appointment.doctor_name} · ${appointment.department}`;
        article.querySelector('[data-reason]').textContent = appointment.reason ?? '';
        article.querySelector('[data-edit]').addEventListener('click', () => edit(appointment));
        article.querySelector('[data-delete]').addEventListener('click', () => remove(appointment.id));
        return article;
    };

    document.querySelectorAll('[data-edit]').forEach((button) => button.addEventListener('click', () => edit(JSON.parse(button.dataset.edit))));
    document.querySelectorAll('[data-delete]').forEach((button) => button.addEventListener('click', () => remove(button.dataset.delete)));
    document.querySelector('#form-cancel').addEventListener('click', reset);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const id = appointmentId.value;
        const response = await fetch(id ? `${page.dataset.baseUrl}/${id}` : page.dataset.storeUrl, { method: id ? 'PATCH' : 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: new FormData(form) });
        const data = await response.json();
        if (!response.ok) { message.textContent = Object.values(data.errors ?? {}).flat().join(' '); return; }
        const old = id && document.querySelector(`[data-id="${id}"]`);
        const item = render(data.appointment);
        if (old) old.replaceWith(item); else { document.querySelector('#empty-state')?.remove(); list.prepend(item); }
        message.textContent = data.message;
        reset();
    });
}
