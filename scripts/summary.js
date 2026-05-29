// public/scripts/summary.js

document.addEventListener("DOMContentLoaded", function () {
    const dataContainer = window.dashboardData || {};
    if (!dataContainer) return;

    dataContainer.categories = Array.isArray(dataContainer.categories) ? dataContainer.categories : [];
    dataContainer.amounts = Array.isArray(dataContainer.amounts) ? dataContainer.amounts : [];
    dataContainer.calendarTransactions = dataContainer.calendarTransactions || {};
    dataContainer.dailyBalanceLabels = Array.isArray(dataContainer.dailyBalanceLabels) ? dataContainer.dailyBalanceLabels : [];
    dataContainer.dailyBalanceData = Array.isArray(dataContainer.dailyBalanceData) ? dataContainer.dailyBalanceData : [];
    dataContainer.monthlyBalanceTotals = dataContainer.monthlyBalanceTotals || {};

    // --- Selektory DOM ---
    const elements = {
        startDate: document.getElementById('startDateInput'),
        endDate: document.getElementById('endDateInput'),
        filterForm: document.getElementById('filterForm'),
        popover: document.getElementById('calendarQuickAdd'),
        popoverDate: document.getElementById('quickAddDate'),
        popoverMonth: document.getElementById('quickAddCalendarMonth'),
        closePopover: document.getElementById('closeQuickAdd'),
        prevMonth: document.getElementById('prevMonthBtn'),
        nextMonth: document.getElementById('nextMonthBtn'),
        calendarGrid: document.getElementById('calendarDaysGrid'),
        calendarLabel: document.getElementById('calendarMonthLabel'),
        prevCal: document.getElementById('prevCalendarMonthBtn'),
        nextCal: document.getElementById('nextCalendarMonthBtn'),
    };

    const today = new Date(dataContainer.today);
    const monthNames = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];

    // --- Helpery do Formatowania i Dat ---
    const formatDateString = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const formatMonthKey = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const clampToToday = d => d > today ? new Date(today) : d;
    
    const formatMoney = v => `${v.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} zł`;
    const compressMoney = v => v === 0 ? 0 : Math.sign(v) * Math.log10(Math.abs(v) + 1);
    const expandMoney = v => v === 0 ? 0 : Math.sign(v) * (Math.pow(10, Math.abs(v)) - 1);

    function formatMoneyShort(value) {
        const abs = Math.abs(value);
        const sign = value < 0 ? '-' : '';
        if (abs >= 1000000) return `${sign}${(abs / 1000000).toFixed(1)} mln`;
        if (abs >= 1000) return `${sign}${(abs / 1000).toFixed(0)} tys.`;
        return `${Math.round(value)} zł`;
    }

    const sameOrBeforeMonth = (d1, d2) => 
        d1.getFullYear() < d2.getFullYear() || (d1.getFullYear() === d2.getFullYear() && d1.getMonth() <= d2.getMonth());

    // Pomocnik do szybkiego tworzenia nodów DOM
    const createEl = (tag, classes = [], text = '') => {
        const el = document.createElement(tag);
        if (classes.length) el.classList.add(...classes);
        if (text) el.textContent = text;
        return el;
    };

    // --- 1. Popover (Szybkie dodawanie) ---
    const closeQuickAdd = () => elements.popover && (elements.popover.hidden = true);
    
    if (elements.closePopover) elements.closePopover.addEventListener('click', closeQuickAdd);
    document.addEventListener('keydown', e => e.key === 'Escape' && closeQuickAdd());
    document.addEventListener('click', e => {
        if (!elements.popover || elements.popover.hidden) return;
        if (!elements.popover.contains(e.target) && !e.target.closest('.calendar-add-link')) closeQuickAdd();
    });

    function openQuickAdd(dateKey, anchor) {
        if (!elements.popover || !elements.popoverDate) return;
        elements.popoverDate.value = dateKey;
        if (elements.popoverMonth) elements.popoverMonth.value = dateKey.slice(0, 7);
        elements.popover.hidden = false;

        const rect = anchor.getBoundingClientRect();
        const pop = elements.popover.getBoundingClientRect();
        const s = 10;

        let left = Math.min(Math.max(s, rect.right + s + pop.width > window.innerWidth - s ? rect.left - pop.width - s : rect.right + s), window.innerWidth - pop.width - s);
        let top = Math.min(Math.max(s, rect.top + pop.height > window.innerHeight - s ? window.innerHeight - pop.height - s : rect.top), window.innerHeight - pop.height - s);

        elements.popover.style.left = `${left}px`;
        elements.popover.style.top = `${top}px`;
    }

    // --- 2. Inteligentna Walidacja Dat i Nawigacja ---
    function handleDateChange(isStart) {
        if (!elements.startDate.value || !elements.endDate.value) return;
        let start = clampToToday(new Date(elements.startDate.value));
        let end = clampToToday(new Date(elements.endDate.value));

        if (isStart && start >= end) {
            end = new Date(start);
            end.setDate(start.getDate() + 1);
        } else if (!isStart && end <= start) {
            start = new Date(end);
            start.setDate(end.getDate() - 1);
        }

        elements.startDate.value = formatDateString(start);
        elements.endDate.value = formatDateString(clampToToday(end));
        elements.filterForm.submit();
    }

    if (elements.startDate) elements.startDate.addEventListener('change', () => handleDateChange(true));
    if (elements.endDate) elements.endDate.addEventListener('change', () => handleDateChange(false));

    if (elements.nextMonth && elements.endDate && new Date(elements.endDate.value) >= today) {
        elements.nextMonth.disabled = true;
    }

    function changeMonth(offset) {
        if (!elements.startDate.value) return;
        const current = new Date(elements.startDate.value);
        const newDate = new Date(current.getFullYear(), current.getMonth() + offset, 1);
        
        if (newDate > today) return;

        elements.startDate.value = formatDateString(newDate);
        elements.endDate.value = formatDateString(clampToToday(new Date(newDate.getFullYear(), newDate.getMonth() + 1, 0)));
        
        const hMonth = document.getElementById('hiddenMonth'), hYear = document.getElementById('hiddenYear');
        if (hMonth) hMonth.value = newDate.getMonth() + 1;
        if (hYear) hYear.value = newDate.getFullYear();
        elements.filterForm.submit();
    }

    if (elements.prevMonth) elements.prevMonth.addEventListener('click', () => changeMonth(-1));
    if (elements.nextMonth) elements.nextMonth.addEventListener('click', () => changeMonth(1));

    // --- 3. Kalendarz ---
    let displayedMonth = new Date(dataContainer.calendarYear, dataContainer.calendarMonth - 1, 1);
    const maxCalendarMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    function renderCalendar() {
        if (!elements.calendarGrid) return;
        const year = displayedMonth.getFullYear(), month = displayedMonth.getMonth();
        let firstDay = new Date(year, month, 1).getDay();
        firstDay = firstDay === 0 ? 6 : firstDay - 1;
        const totalDays = new Date(year, month + 1, 0).getDate();

        if (elements.calendarLabel) elements.calendarLabel.textContent = `${monthNames[month]} ${year}`;
        if (elements.nextCal) elements.nextCal.disabled = !sameOrBeforeMonth(new Date(year, month + 1, 1), maxCalendarMonth);

        elements.calendarGrid.innerHTML = '';

        for (let i = 0; i < firstDay; i++) elements.calendarGrid.appendChild(createEl('div', ['calendar-day', 'empty']));

        for (let day = 1; day <= totalDays; day++) {
            const dayCell = createEl('div', ['calendar-day']);
            dayCell.appendChild(createEl('span', ['day-num'], day));

            const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const tx = dataContainer.calendarTransactions ? dataContainer.calendarTransactions[dateKey] : null;
            const exp = tx ? parseFloat(tx.expense || 0) : 0, inc = tx ? parseFloat(tx.income || 0) : 0;

            if (exp > 0 || inc > 0) {
                const stack = createEl('div', ['day-amounts']);
                if (inc > 0) stack.appendChild(createEl('span', ['day-income'], `+${inc.toFixed(0)}zł`));
                if (exp > 0) stack.appendChild(createEl('span', ['day-expense'], `-${exp.toFixed(0)}zł`));
                dayCell.appendChild(stack);
                dayCell.classList.add('has-transaction', inc > exp ? 'day-net-positive' : exp > inc ? 'day-net-negative' : 'day-net-even');
            } else {
                dayCell.appendChild(createEl('span', ['day-expense', 'zero'], '0 zł'));
                dayCell.classList.add('no-expense');
            }

            if (new Date(year, month, day) <= today) {
                const btn = createEl('button', ['calendar-add-link'], '+');
                btn.type = 'button';
                btn.title = `Dodaj transakcję: ${dateKey}`;
                btn.addEventListener('click', e => { e.stopPropagation(); openQuickAdd(dateKey, dayCell); });
                dayCell.appendChild(btn);
            } else {
                dayCell.classList.add('future-day');
            }
            elements.calendarGrid.appendChild(dayCell);
        }

        while (elements.calendarGrid.children.length < 42) elements.calendarGrid.appendChild(createEl('div', ['calendar-day', 'empty']));
    }

    if (elements.prevCal) elements.prevCal.addEventListener('click', () => { displayedMonth.setMonth(displayedMonth.getMonth() - 1); renderCalendar(); });
    if (elements.nextCal) elements.nextCal.addEventListener('click', () => { displayedMonth.setMonth(displayedMonth.getMonth() + 1); renderCalendar(); });
    renderCalendar();

    // --- 4. WYKRESY (Chart.js Config Shared) ---
    const commonScales = {
        x: { grid: { display: false }, ticks: { color: '#b2bec3' } },
        y: { 
            grid: { color: 'rgba(255,255,255,0.05)' }, 
            ticks: { color: '#b2bec3', callback: val => formatMoneyShort(expandMoney(val)) } 
        }
    };
    const commonPlugins = (rawDataSource) => ({
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => `Bilans: ${formatMoney(rawDataSource[ctx.dataIndex] || 0)}` } }
    });

    // Wykres Bilansu Dziennego
    const dailyBalanceCtx = document.getElementById('dailyBalanceChart');
    if (dailyBalanceCtx) {
        const raw = dataContainer.dailyBalanceData || [];
        new Chart(dailyBalanceCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: dataContainer.dailyBalanceLabels,
                datasets: [{ data: raw.map(compressMoney), borderColor: '#00cec9', backgroundColor: 'rgba(0, 206, 201, 0.14)', fill: true, tension: 0.35, pointRadius: 2 }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { ...commonScales, x: { ...commonScales.x, ticks: { color: '#b2bec3', maxTicksLimit: 8 } } }, plugins: commonPlugins(raw) }
        });
    }

    // Wykres Bilansu Miesięcznego
    const monthlyCtx = document.getElementById('monthlyBalanceChart');
    if (monthlyCtx) {
        const mTotals = dataContainer.monthlyBalanceTotals || {};
        const currentMonthStr = dataContainer.monthlyBalanceCurrentMonth || formatDateString(today).slice(0, 7);
        let endMonth = new Date(currentMonthStr.split('-')[0], currentMonthStr.split('-')[1] - 1, 1);
        let monthlyChart = null;

        function getSlice() {
            const labels = [], values = [], raws = [];
            const start = new Date(endMonth.getFullYear(), endMonth.getMonth() - 11, 1);
            const shortMonths = ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'];

            for (let i = 0; i < 12; i++) {
                const d = new Date(start.getFullYear(), start.getMonth() + i, 1);
                const rawVal = parseFloat(mTotals[formatMonthKey(d)] || 0);
                labels.push(`${shortMonths[d.getMonth()]} ${d.getFullYear()}`);
                raws.push(rawVal);
                values.push(compressMoney(rawVal));
            }
            return { labels, values, raws };
        }

        function updateMonthly() {
            const slice = getSlice();
            const rangeEl = document.getElementById('monthlyBalanceRange');
            if (rangeEl) rangeEl.textContent = `${slice.labels[0]} - ${slice.labels[11]}`;
            const nextMonthlyBtn = document.getElementById('nextMonthlyBalanceBtn');
            if (nextMonthlyBtn) {
                nextMonthlyBtn.disabled = !sameOrBeforeMonth(new Date(endMonth.getFullYear(), endMonth.getMonth() + 1, 1), new Date(currentMonthStr.split('-')[0], currentMonthStr.split('-')[1] - 1, 1));
            }

            if (!monthlyChart) {
                monthlyChart = new Chart(monthlyCtx.getContext('2d'), {
                    type: 'bar',
                    data: { labels: slice.labels, datasets: [{ data: slice.values, backgroundColor: slice.raws.map(v => v >= 0 ? '#4cd137' : '#ff7675'), borderRadius: 6 }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: commonScales, plugins: commonPlugins(slice.raws) }
                });
            } else {
                monthlyChart.data.labels = slice.labels;
                monthlyChart.data.datasets[0].data = slice.values;
                monthlyChart.data.datasets[0].backgroundColor = slice.raws.map(v => v >= 0 ? '#4cd137' : '#ff7675');
                monthlyChart.options.plugins.tooltip.callbacks.label = ctx => `Bilans: ${formatMoney(slice.raws[ctx.dataIndex] || 0)}`;
                monthlyChart.update();
            }
        }

        updateMonthly();
        document.getElementById('prevMonthlyBalanceBtn')?.addEventListener('click', () => { endMonth.setMonth(endMonth.getMonth() - 1); updateMonthly(); });
        document.getElementById('nextMonthlyBalanceBtn')?.addEventListener('click', () => { endMonth.setMonth(endMonth.getMonth() + 1); updateMonthly(); });
    }

    // Wykres Pączek (Doughnut)
    const ctx = document.getElementById('expenseChart');
    if (ctx && dataContainer.amounts.length > 0) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: dataContainer.categories,
                datasets: [{ data: dataContainer.amounts, backgroundColor: ['#ff7675', '#00b894', '#0984e3', '#fdcb6e', '#6c5ce7', '#e17055', '#00cec9'], borderWidth: 2, borderColor: '#1e272e' }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#b2bec3', font: { size: 12, family: "'Inter', sans-serif" } } } } }
        });
    }
});
