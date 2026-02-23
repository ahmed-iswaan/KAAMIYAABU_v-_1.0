<div class="container-xxl py-6">
    <style>
        .faruma { font-family: 'Faruma', system-ui, 'Segoe UI', Arial, sans-serif; }
    </style>
    <div class="row g-6 mb-6">
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Directories Status (Pending / Completed)</div>
                <div class="fs-2 fw-bold">
                    {{ number_format($directoriesPendingTotal ?? ($directoriesActive ?? 0)) }}
                    <span class="fs-6 text-muted">/ {{ number_format($directoriesCompletedTotal ?? ($directoriesInactive ?? 0)) }}</span>
                </div>
                <div class="fs-8 text-muted mt-1">Total: {{ number_format($activeDirectories ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Pending (Directories)</div>
                <div class="fs-2 fw-bold text-warning">{{ number_format($directoriesPendingTotal ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Daily Completed (Directories)</div>
                <div class="fs-2 fw-bold text-primary">{{ number_format($directoriesCompletedTodayTotal ?? ($tasksCompletedToday ?? 0)) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Completed (Directories)</div>
                <div class="fs-2 fw-bold text-success">{{ number_format($directoriesCompletedTotal ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Directories Pending and Completed</div>
            <div class="fs-7 text-muted">Current active election (call status)</div>
        </div>
        <div class="position-relative" style="height: 360px;">
            <canvas id="dashNoTaskPie" wire:ignore class="position-absolute top-0 start-0 w-100 h-100"></canvas>
            <div class="position-absolute top-50 start-50 translate-middle text-center">
                <div class="fs-1 fw-bold" id="dashNoTaskTotal">0</div>
                <div class="fs-7 text-muted">Total Active</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-6 mt-4">
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#f6c000;"></span><span class="fs-7 text-muted">Pending</span></div>
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#50cd89;"></span><span class="fs-7 text-muted">Completed</span></div>
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Directories by SubConsite</div>
            <div class="fs-7 text-muted">Pending / Completed (current active election)</div>
        </div>
        <div style="height: 360px;">
            <canvas id="dashSubConsiteStatusChart" wire:ignore></canvas>
        </div>
    </div>

    {{-- Q1 / Q3 / Q4 / Q5 answers by SubConsite --}}
    @if(!empty($qsBySubCharts) && count($qsBySubCharts))
        @foreach($qsBySubCharts as $chart)
            <div class="card card-flush p-6 shadow-sm mt-6">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="fs-5 fw-bold faruma">Q{{ $chart['position'] }}. {{ $chart['text'] }}</div>
                    <div class="fs-7 text-muted faruma">Answers by SubConsite</div>
                </div>
                <div style="height: 360px">
                    <canvas id="chart_qpos_{{ $chart['position'] }}" wire:ignore></canvas>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-3">
                    @foreach(($chart['series'] ?? []) as $s)
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="width:12px;height:12px;background: {{ $s['color'] }}"></span>
                            <span class="fs-7 text-muted faruma">{{ $s['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="card card-flush p-6 shadow-sm mt-6">
            <div class="fs-6 fw-bold">Q1 / Q3 / Q4 / Q5 Answers by SubConsite</div>
            <div class="fs-7 text-muted mt-2">No chart data found. Check: selected form has questions at positions 1, 3, 4, 5 and there are submissions with directories linked to a SubConsite.</div>
        </div>
    @endif

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Provisional Pledges by SubConsite</div>
            <div class="fs-7 text-muted">Pledged / Not Pledged</div>
        </div>
        <div style="height: 360px;"><canvas id="provBySubConsite" wire:ignore></canvas></div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Final Pledges by SubConsite</div>
            <div class="fs-7 text-muted">Yes / No / Undecided / Pending</div>
        </div>
        <div style="height: 360px;"><canvas id="finalBySubConsite" wire:ignore></canvas></div>
    </div>

    <div class="card shadow-sm border-0 card-flush mt-6">
        <div class="card-header border-0 pt-5 pb-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="symbol symbol-40px">
                    <div class="symbol-label bg-success bg-opacity-10"><i class="ki-duotone ki-chart-line-up text-success fs-2hx"></i></div>
                </div>
                <div>
                    <h4 class="fw-bold mb-0">Users Performance</h4>
                    <div class="text-muted fs-8">Active election (from election_directory_call_statuses)</div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light" wire:click="downloadUsersPerformanceCsv">
                    Download CSV
                </button>

                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm" style="min-width: 220px;" wire:model="selectedUserPerformanceCsvUserId">
                        @foreach(($userPerformanceUsersForSelect ?? []) as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-light-primary" wire:click="downloadUserPerformanceDailyCsv">
                        Download Daily CSV
                    </button>
                    <button type="button" class="btn btn-sm btn-light" wire:click="downloadUsersPerformanceDailyZip">
                        Download All Daily (Users)
                    </button>
                </div>

                <button type="button" class="btn btn-sm btn-light-primary" wire:click="toggleShowAllUsersPerformance">
                    {{ ($showAllUsersPerformance ?? false) ? 'Show only active users' : 'Show all users' }}
                </button>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed gy-4 mb-0">
                    <thead>
                    <tr class="text-gray-600 fw-semibold text-uppercase fs-8">
                        <th class="ps-3">#</th>
                        <th>User</th>
                        <th class="text-center">Completed</th>
                        <th class="text-center">Attempts</th>
                        <th class="text-end pe-3">Daily</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse(($userPerformanceRows ?? []) as $row)
                        <tr class="align-middle">
                            <td class="ps-3 fw-bold text-gray-700">{{ $row['rank'] }}</td>
                            <td class="fw-semibold">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="symbol symbol-30px symbol-circle">
                                        <div class="symbol-label bg-light-primary text-primary fw-bold">{{ strtoupper(substr($row['name'],0,1)) }}</div>
                                    </div>
                                    <span class="text-gray-800 fw-semibold">{{ $row['name'] }}</span>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge badge-light-success fw-semibold">{{ $row['completed'] }}</span></td>
                            <td class="text-center"><span class="badge badge-light-dark fw-semibold">{{ $row['attempts'] }}</span></td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-light" wire:click="toggleUserDaily({{ $row['user_id'] }})">
                                    {{ (($expandedUserIds[$row['user_id']] ?? false) ? 'Hide' : 'Show') }}
                            </button>
                            </td>
                        </tr>
                        @if(($expandedUserIds[$row['user_id']] ?? false))
                            <tr>
                                <td colspan="5" class="bg-light">
                                    <div class="p-4">
                                        <div class="fw-bold mb-3">{{ $row['name'] }} — Daily</div>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                <tr class="text-muted text-uppercase fs-9">
                                                    <th>Date</th>
                                                    <th class="text-center">Completed</th>
                                                    <th class="text-center">Attempts</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @php
                                                    // Show ALL days (including completed=0) so attempts totals reconcile visually.
                                                    $dailyRows = collect($userDailyStats[$row['user_id']] ?? []);
                                                @endphp
                                                @forelse($dailyRows as $d)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $d['date'] }}</td>
                                                        <td class="text-center"><span class="badge badge-light-success">{{ $d['completed'] }}</span></td>
                                                        <td class="text-center"><span class="badge badge-light-dark">{{ $d['attempts'] }}</span></td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="3" class="text-muted fst-italic">No daily stats.</td></tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-muted fst-italic py-10 text-center">No data found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js first -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Enable legend click-to-toggle for all charts (global default)
// Works for both Chart.js v3/v4
(function(){
    if (!window.Chart) return;

    const handler = function(e, legendItem, legend){
        const ci = legend.chart;
        const index = legendItem.datasetIndex;
        if (ci.isDatasetVisible(index)) {
            ci.hide(index);
            legendItem.hidden = true;
        } else {
            ci.show(index);
            legendItem.hidden = false;
        }
    };

    try {
        Chart.defaults.plugins = Chart.defaults.plugins || {};
        Chart.defaults.plugins.legend = Chart.defaults.plugins.legend || {};
        Chart.defaults.plugins.legend.onClick = handler;
    } catch (e) {
        // ignore
    }
})();
</script>

<!-- Totals above bars plugin must be defined before charts use it -->
<script>
const TotalsAboveBarsPlugin = {
    id: 'totalsAboveBars',
    afterDatasetsDraw(chart) {
        const {ctx, data} = chart;
        const meta = chart.getDatasetMeta(0);
        if (!meta || !meta.data) return;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        const drawMode = chart?.options?.plugins?.totalsAboveBars?.mode || 'above';

        data.labels.forEach((label, index) => {
            let total = 0;
            data.datasets.forEach(ds => { total += (parseFloat(ds.data[index]) || 0); });
            const el = meta.data[index];
            if (!el) return;
            const x = el.x;

            if (drawMode === 'inside') {
                const yTop = chart.scales.y.getPixelForValue(total);
                const yBase = chart.scales.y.getPixelForValue(0);
                const y = (yTop + yBase) / 2;

                const text = total.toLocaleString();
                ctx.font = '700 12px system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';
                ctx.lineWidth = 3;
                ctx.strokeStyle = 'rgba(255,255,255,0.85)';
                ctx.strokeText(text, x, y);
                ctx.fillStyle = '#111827';
                ctx.fillText(text, x, y);
            } else {
                ctx.textBaseline = 'bottom';
                ctx.fillStyle = '#5e6278';
                ctx.font = '12px system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';
                const yTop = chart.scales.y.getPixelForValue(total);
                ctx.fillText(total.toLocaleString(), x, yTop - 6);
            }
        });
        ctx.restore();
    }
};
</script>

<!-- Total pie charts init -->
<script>
(function(){
    const palette = ['#3e97ff','#50cd89','#f6c000','#f1416c','#7239ea','#00a3ef','#a1a5b7','#e4e6ef','#ff6b6b','#2d3250'];

    const DoughnutCenterTextPlugin = {
        id: 'doughnutCenterText',
        afterDraw(chart, args, options) {
            const {ctx, chartArea} = chart;
            if (!chartArea) return;
            const dataset = chart.data?.datasets?.[0];
            if (!dataset) return;
            const total = (dataset.data || []).reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
            const text = (options && options.text) ? options.text : (total || 0).toLocaleString();

            const x = (chartArea.left + chartArea.right) / 2;
            const y = (chartArea.top + chartArea.bottom) / 2;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = (options && options.color) ? options.color : '#181c32';
            ctx.font = (options && options.font) ? options.font : '600 20px system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';
            ctx.fillText(text, x, y);
            if (options && options.subText) {
                ctx.fillStyle = options.subColor || '#a1a5b7';
                ctx.font = options.subFont || '12px system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';
                ctx.fillText(options.subText, x, y + 18);
            }
            ctx.restore();
        }
    };

    function buildPie(elId, labels, data){
        const el = document.getElementById(elId);
        if(!el) return;
        if(!labels || !labels.length) return;

        if (el.__chart) {
            try { el.__chart.destroy(); } catch(e) {}
            el.__chart = null;
        }

        const vals = (data || []).map(v => parseInt(v, 10) || 0);
        const colors = labels.map((_, i) => palette[i % palette.length]);
        el.__chart = new Chart(el, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: vals, backgroundColor: colors, borderWidth: 0 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom' },
                    doughnutCenterText: { subText: 'Total' }
                }
            },
            plugins: [DoughnutCenterTextPlugin]
        });
    }

    function init(){
        const pies = @json($formTotalsPies ?? []);
        pies.forEach(p => {
            buildPie('pie_total_q_' + p.questionId, p.labels || [], p.counts || []);
        });
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('livewire:navigated', init);
})();
</script>

<!-- No Task pie chart (4 segments) -->
<script>
(function(){
    const el = document.getElementById('noTaskPie');
    if(!el) return;
    const data = [
        parseInt(@json($directoriesWithNoTasks ?? 0), 10) || 0,
        parseInt(@json($piePendingDirs ?? 0), 10) || 0,
        parseInt(@json($pieFollowUpDirs ?? 0), 10) || 0,
        parseInt(@json($pieCompletedDirs ?? 0), 10) || 0,
    ];
    const labels = ['No Task','Pending','Follow-up','Completed'];
    const colors = ['#f1416c','#f6c000','#3e97ff','#50cd89'];
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors, borderWidth: 0 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Center total = Active Directories
    const totalEl = document.getElementById('noTaskTotal');
    if (totalEl) {
        const totalActive = parseInt(@json($activeDirectories ?? 0), 10) || 0;
        totalEl.textContent = totalActive.toLocaleString();
    }
})();
</script>

<!-- SubConsite tasks chart -->
<script>
(function(){
    const el = document.getElementById('subConsiteStatusChart');
    if(!el) return;
    const labels = @json($subConsiteLabels ?? []);
    const pending = @json($subConsitePending ?? []);
    const followUp = @json($subConsiteFollowUp ?? []);
    const completed = @json($subConsiteCompleted ?? []);
    const noTask = @json($subConsiteNoTask ?? []);
    if (!labels.length) return; // avoid errors when no data
    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Pending', data: pending, backgroundColor: '#f6c000' },
                { label: 'Follow-up', data: followUp, backgroundColor: '#3e97ff' },
                { label: 'Completed', data: completed, backgroundColor: '#50cd89' },
                { label: 'No Task', data: noTask, backgroundColor: '#f1416c' },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
            plugins: { legend: { position: 'bottom' } }
        },
        plugins: [TotalsAboveBarsPlugin]
    });
})();
</script>

<!-- Provisional and Final Pledges by SubConsite charts -->
<script>
(function(){
    const labels = @json($pledgeLabels ?? []);
    if(!labels.length) return;

    // Provisional pledged vs not pledged
    const provPledged = @json($provPledged ?? []);
    const provNotPledged = @json($provNotPledged ?? []);

    // Draw segment values inside each stacked segment (centered per-segment)
    const SegmentValuesPlugin = {
        id: 'segmentValues',
        afterDatasetsDraw(chart) {
            const {ctx} = chart;
            if (!chart?.data?.datasets?.length) return;

            const minPx = chart?.options?.plugins?.segmentValues?.minSegmentPixelHeight ?? 18;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = '600 11px system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';

            chart.data.datasets.forEach((ds, dsIndex) => {
                const meta = chart.getDatasetMeta(dsIndex);
                if (!meta || meta.hidden) return;

                meta.data.forEach((bar, index) => {
                    const v = parseFloat(ds.data?.[index]) || 0;
                    if (v <= 0) return;

                    // For stacked bars: each bar element has `y` (top) and `base` (bottom) for that segment
                    const yTop = bar.y;
                    const yBase = bar.base;
                    if (!isFinite(yTop) || !isFinite(yBase)) return;

                    // Skip very small segments to avoid clutter
                    if (Math.abs(yBase - yTop) < minPx) return;

                    const y = (yTop + yBase) / 2;
                    const text = v.toLocaleString();

                    ctx.lineWidth = 3;
                    ctx.strokeStyle = 'rgba(255,255,255,0.85)';
                    ctx.strokeText(text, bar.x, y);
                    ctx.fillStyle = '#111827';
                    ctx.fillText(text, bar.x, y);
                });
            });

            ctx.restore();
        }
    };

    const elProv = document.getElementById('provBySubConsite');
    if(elProv){
        new Chart(elProv, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Not Pledged', data: provNotPledged, backgroundColor: '#1b84ff' },
                    { label: 'Pledged', data: provPledged, backgroundColor: '#f6c000' },
                ]
            },
            options:
                {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
                    plugins: {
                        legend: { position: 'bottom' },
                        // Put the TOTAL above the bar to avoid overlapping the segment values
                        totalsAboveBars: { mode: 'above' },
                        segmentValues: { minSegmentPixelHeight: 18 }
                    }
                },
            plugins: [TotalsAboveBarsPlugin, SegmentValuesPlugin]
        });
    }

    // Final chart remains Yes/No/Undecided/Pending
    const final = {
        yes: @json($finalYes ?? []),
        no: @json($finalNo ?? []),
        undecided: @json($finalUndecided ?? []),
        pending: @json($finalPending ?? []),
    };
    const elFinal = document.getElementById('finalBySubConsite');
    if(elFinal){
        new Chart(elFinal, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Yes', data: final.yes, backgroundColor: '#3e97ff' },
                    { label: 'No', data: final.no, backgroundColor: '#f6c000' },
                    { label: 'Undecided', data: final.undecided, backgroundColor: '#a1a5b7' },
                    { label: 'Pending', data: final.pending, backgroundColor: '#e4e6ef' },
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
        });
    }
})();
</script>

<!-- Per-question charts init -->
<script>
(function(){
    function buildChart(elId, labels, series){
        const el = document.getElementById(elId);
        if(!el) return;
        if (!labels || !labels.length || !series || !series.length) return;
        new Chart(el, {
            type: 'bar',
            data: { labels, datasets: series.map(s => ({ label: s.label, data: s.data, backgroundColor: s.color })) },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
        });
    }
    document.addEventListener('DOMContentLoaded', function(){
        const charts = @json($fsAllCharts ?? []);
        charts.forEach(c => { buildChart('chart_q_' + c.questionId, c.labels || [], c.series || []); });
    });
})();
</script>

<script>
(function(){
    function buildPendingCompletedPie(){
        const el = document.getElementById('dashNoTaskPie');
        if(!el || !window.Chart) return;

        const pending = parseInt(@json($pieElectionPendingDirs ?? 0), 10) || 0;
        const completed = parseInt(@json($pieElectionCompletedDirs ?? 0), 10) || 0;
        const total = pending + completed;

        const totalEl = document.getElementById('dashNoTaskTotal');
        if(totalEl) totalEl.textContent = total.toLocaleString();

        if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
        el.__chart = new Chart(el, {
            type: 'doughnut',
            data: {
                labels: ['Pending','Completed'],
                datasets: [{
                    data: [pending, completed],
                    backgroundColor: ['#f6c000', '#50cd89'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                }
            }
        });
    }

    function buildSubConsiteBar(){
        const el = document.getElementById('dashSubConsiteStatusChart');
        if(!el || !window.Chart) return;

        const labels = @json($dashSubConsiteLabels ?? []);
        const pending = @json($dashSubConsitePending ?? []);
        const completed = @json($dashSubConsiteCompleted ?? []);

        if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
        el.__chart = new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Pending', data: pending, backgroundColor: '#f6c000', stack: 's1' },
                    { label: 'Completed', data: completed, backgroundColor: '#50cd89', stack: 's1' },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 } },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    legend: { position: 'bottom' },
                    totalsAboveBars: { mode: 'above' }
                }
            },
            plugins: [TotalsAboveBarsPlugin]
        });
    }

    // Ensure Chart.js is loaded (already included above) then render
    if (window.Chart) {
        buildPendingCompletedPie();
        buildSubConsiteBar();
    } else {
        const t = setInterval(function(){
            if(window.Chart){
                clearInterval(t);
                buildPendingCompletedPie();
                buildSubConsiteBar();
            }
        }, 50);
        setTimeout(()=>clearInterval(t), 5000);
    }
})();
</script>

<script>
(function(){
    function buildQPosCharts(){
        if(!window.Chart) return;
        const charts = @json($qsBySubCharts ?? []);
        if(!charts || !charts.length) return;

        charts.forEach(function(c){
            const el = document.getElementById('chart_qpos_' + c.position);
            if(!el) return;

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }

            const datasets = (c.series || []).map(function(s){
                return {
                    label: s.label,
                    data: s.data,
                    backgroundColor: s.color,
                    stack: 's1',
                };
            });

            el.__chart = new Chart(el, {
                type: 'bar',
                data: { labels: c.labels || [], datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 } },
                        y: { stacked: true, beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'bottom' },
                        totalsAboveBars: { mode: 'above' }
                    }
                },
                plugins: [TotalsAboveBarsPlugin]
            });
        });
    }

    // Render after Chart.js exists
    if (window.Chart) {
        buildQPosCharts();
    } else {
        const t = setInterval(function(){
            if(window.Chart){
                clearInterval(t);
                buildQPosCharts();
            }
        }, 50);
        setTimeout(()=>clearInterval(t), 5000);
    }
})();
</script>
