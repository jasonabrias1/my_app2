(function () {
    if (typeof Chart === 'undefined' || typeof ANALYTICS_DATA === 'undefined') {
        return;
    }

    var MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];


    function formatLabel(raw) {
        var p = String(raw).split('-');
        var m = MONTH_NAMES[parseInt(p[1], 10) - 1] || raw;
        if (p.length === 3) {
            return m + ' ' + parseInt(p[2], 10);
        }
        return m + ' ' + p[0];
    }

    function sum(arr) {
        return arr.reduce(function (a, b) { return a + b; }, 0);
    }

    function el(id) {
        return document.getElementById(id);
    }


    function showEmpty(canvas, message) {
        var box = canvas.parentNode;
        canvas.style.display = 'none';
        var note = document.createElement('div');
        note.className = 'empty-state';
        note.style.cssText = 'display:flex;align-items:center;justify-content:center;height:100%;color:#64748b;font-size:13px;';
        note.textContent = message;
        box.appendChild(note);
    }

    var statusValues = ANALYTICS_DATA.statusValues;
    var typeLabels = ANALYTICS_DATA.typeLabels;
    var typeValues = ANALYTICS_DATA.typeValues;
    var monthLabels = ANALYTICS_DATA.monthLabels.map(formatLabel);
    var monthValues = ANALYTICS_DATA.monthValues;

    var statusCanvas = el('statusChart');
    if (statusCanvas) {
        if (sum(statusValues) === 0) {
            showEmpty(statusCanvas, 'No requests for this filter.');
        } else {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'For Pick Up', 'Completed'],
                    datasets: [{
                        data: statusValues,
                        backgroundColor: ['#d97706', '#1d4ed8', '#16a34a'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
                }
            });
        }
    }

    var monthCanvas = el('monthChart');
    if (monthCanvas) {
        if (monthValues.length === 0) {
            showEmpty(monthCanvas, 'No requests for this filter.');
        } else {
            new Chart(monthCanvas, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Requests',
                        data: monthValues,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,.1)',
                        fill: true,
                        tension: .3,
                        pointRadius: monthValues.length === 1 ? 5 : 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    }

    var typeCanvas = el('typeChart');
    if (typeCanvas) {
        if (typeValues.length === 0) {
            showEmpty(typeCanvas, 'No requests for this filter.');
        } else {
            new Chart(typeCanvas, {
                type: 'bar',
                data: {
                    labels: typeLabels,
                    datasets: [{ label: 'Requests', data: typeValues, backgroundColor: '#1e40af', borderRadius: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    }
})();