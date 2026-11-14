const page = document.querySelector('#medical-records-page');

if (page) {
    const trends = document.querySelector('#vital-trends');
    if (trends) {
        const readings = JSON.parse(trends.dataset.readings);
        const tabs = [...trends.querySelectorAll('[data-trend]')];
        const chart = document.querySelector('#trend-chart');
        const metrics = {
            heart_rate: { label: 'Heart rate', unit: 'bpm', decimals: 0, series: [{ key: 'heart_rate', label: 'Heart rate', color: '#0f766e' }] },
            temperature: { label: 'Temperature', unit: '\u00b0C', decimals: 1, series: [{ key: 'temperature', label: 'Temperature', color: '#b45309' }] },
            blood_pressure: { label: 'Blood pressure', unit: 'mmHg', decimals: 0, series: [{ key: 'systolic', label: 'Systolic', color: '#0f766e' }, { key: 'diastolic', label: 'Diastolic', color: '#7c3aed', dashed: true }] },
        };
        const valueOf = (reading, key) => reading[key] === null || reading[key] === undefined || reading[key] === '' ? null : Number(reading[key]);
        const svgElement = (tag, attributes, text) => {
            const element = document.createElementNS('http://www.w3.org/2000/svg', tag);
            Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, value));
            if (text !== undefined) element.textContent = text;
            return element;
        };
        const renderTrend = (key) => {
            const metric = metrics[key];
            tabs.forEach((tab) => {
                tab.setAttribute('aria-selected', String(tab.dataset.trend === key));
                tab.tabIndex = tab.dataset.trend === key ? 0 : -1;
            });
            document.querySelector('#trend-panel').setAttribute('aria-labelledby', `trend-tab-${key}`);
            document.querySelector('#trend-title').textContent = metric.label;
            document.querySelector('#trend-unit').textContent = metric.unit;
            chart.setAttribute('aria-label', `${metric.label} trend in ${metric.unit}. Exact values are available in the readings table.`);
            chart.replaceChildren();
            const legend = document.querySelector('#trend-legend');
            legend.replaceChildren();
            metric.series.forEach((series) => {
                const label = document.createElement('span');
                label.textContent = `${series.dashed ? '\u2504' : '\u2501'} ${series.label}`;
                label.style.color = series.color;
                legend.append(label);
            });
            const values = readings.flatMap((reading) => metric.series.map((series) => valueOf(reading, series.key))).filter(Number.isFinite);
            chart.classList.toggle('hidden', !values.length);
            document.querySelector('#trend-empty').classList.toggle('hidden', Boolean(values.length));
            if (values.length) {
                const minimum = Math.min(...values);
                const maximum = Math.max(...values);
                const padding = Math.max((maximum - minimum) * 0.2, metric.decimals ? 0.5 : 5);
                const lower = Math.max(0, Math.floor((minimum - padding) * 10 ** metric.decimals) / 10 ** metric.decimals);
                const upper = Math.ceil((maximum + padding) * 10 ** metric.decimals) / 10 ** metric.decimals;
                const x = (index) => readings.length === 1 ? 315 : 55 + index * 520 / (readings.length - 1);
                const y = (value) => 210 - (value - lower) / (upper - lower) * 185;
                for (let tick = 0; tick <= 4; tick++) {
                    const value = lower + (upper - lower) * tick / 4;
                    const position = y(value);
                    chart.append(svgElement('line', { x1: 55, x2: 575, y1: position, y2: position, stroke: '#e2e8f0' }));
                    chart.append(svgElement('text', { x: 45, y: position + 4, 'text-anchor': 'end', fill: '#64748b', 'font-size': 12 }, value.toFixed(metric.decimals)));
                }
                readings.forEach((reading, index) => {
                    chart.append(svgElement('text', { x: x(index), y: 240, 'text-anchor': 'middle', fill: '#64748b', 'font-size': 11 }, reading.shortDate));
                });
                metric.series.forEach((series) => {
                    let path = '';
                    let connected = false;
                    readings.forEach((reading, index) => {
                        const value = valueOf(reading, series.key);
                        if (!Number.isFinite(value)) { connected = false; return; }
                        path += `${connected ? 'L' : 'M'} ${x(index)} ${y(value)} `;
                        connected = true;
                    });
                    chart.append(svgElement('path', { d: path, fill: 'none', stroke: series.color, 'stroke-width': 3, 'stroke-linejoin': 'round', 'stroke-dasharray': series.dashed ? '7 5' : 'none' }));
                    readings.forEach((reading, index) => {
                        const value = valueOf(reading, series.key);
                        if (!Number.isFinite(value)) return;
                        const point = svgElement('circle', { cx: x(index), cy: y(value), r: 4, fill: series.color });
                        point.append(svgElement('title', {}, `${reading.date}: ${series.label} ${value.toFixed(metric.decimals)} ${metric.unit}`));
                        chart.append(point);
                    });
                });
            }
            const stats = document.querySelector('#trend-stats');
            stats.replaceChildren();
            ['Average', 'Lowest', 'Highest'].forEach((label) => {
                const card = document.createElement('div');
                card.className = 'rounded-xl bg-slate-50 p-3';
                const heading = document.createElement('p');
                heading.className = 'text-xs text-slate-500';
                heading.textContent = label;
                card.append(heading);
                metric.series.forEach((series) => {
                    const data = readings.map((reading) => valueOf(reading, series.key)).filter(Number.isFinite);
                    const result = !data.length ? null : label === 'Average' ? data.reduce((sum, value) => sum + value, 0) / data.length : label === 'Lowest' ? Math.min(...data) : Math.max(...data);
                    const line = document.createElement('p');
                    line.className = 'mt-1 text-sm font-semibold text-slate-800';
                    line.textContent = `${metric.series.length > 1 ? `${series.label}: ` : ''}${result === null ? '\u2014' : result.toFixed(metric.decimals)}`;
                    card.append(line);
                });
                stats.append(card);
            });
            const head = document.querySelector('#trend-table-head');
            const body = document.querySelector('#trend-table-body');
            head.replaceChildren(); body.replaceChildren();
            const header = document.createElement('tr');
            ['Date', ...metric.series.map((series) => `${series.label} (${metric.unit})`)].forEach((label) => {
                const cell = document.createElement('th'); cell.scope = 'col'; cell.className = 'px-2 py-2'; cell.textContent = label; header.append(cell);
            });
            head.append(header);
            readings.forEach((reading) => {
                const row = document.createElement('tr');
                [reading.date, ...metric.series.map((series) => {
                    const value = valueOf(reading, series.key);
                    return Number.isFinite(value) ? value.toFixed(metric.decimals) : '\u2014';
                })].forEach((value) => {
                    const cell = document.createElement('td'); cell.className = 'border-t border-slate-100 px-2 py-2'; cell.textContent = value; row.append(cell);
                });
                body.append(row);
            });
        };
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => renderTrend(tab.dataset.trend));
            tab.addEventListener('keydown', (event) => {
                let target;
                if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') target = (index + tabs.length - 1) % tabs.length;
                if (event.key === 'Home') target = 0;
                if (event.key === 'End') target = tabs.length - 1;
                if (target === undefined) return;
                event.preventDefault(); tabs[target].focus(); renderTrend(tabs[target].dataset.trend);
            });
        });
        renderTrend('heart_rate');
    }

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

    const targetRecord = new URLSearchParams(window.location.search).get('record');
    if (targetRecord) {
        const button = [...document.querySelectorAll('[data-record-key]')].find((item) => item.dataset.recordKey === targetRecord);
        if (button) {
            openRecord(button);
        } else {
            const notice = document.createElement('p');
            notice.className = 'rounded-xl bg-slate-100 p-4 text-sm text-slate-600';
            notice.textContent = 'This record is no longer available. Your current medical records are shown below.';
            page.prepend(notice);
        }
    }

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
