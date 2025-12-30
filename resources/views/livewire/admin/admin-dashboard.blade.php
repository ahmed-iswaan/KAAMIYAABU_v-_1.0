<div class="container-xxl py-6">
    <style>
        .faruma { font-family: 'Faruma', system-ui, 'Segoe UI', Arial, sans-serif; }
    </style>
    <div class="row g-6 mb-6">
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Active Directories</div>
                <div class="fs-2 fw-bold">{{ number_format($activeDirectories) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Pending Tasks</div>
                <div class="fs-2 fw-bold text-warning">{{ number_format($tasksPending) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Follow-up Tasks</div>
                <div class="fs-2 fw-bold text-info">{{ number_format($tasksFollowUp) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 p-6 shadow-sm">
                <div class="fs-7 text-muted">Completed Tasks</div>
                <div class="fs-2 fw-bold text-success">{{ number_format($tasksCompleted) }}</div>
            </div>
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Directories Without Tasks</div>
            <div class="fs-7 text-muted">{{ number_format($directoriesWithNoTasks) }} Active directories have no tasks</div>
        </div>
        <div class="position-relative" style="height: 360px;">
            <canvas id="noTaskPie" wire:ignore class="position-absolute top-0 start-0 w-100 h-100"></canvas>
            <div id="noTaskCenter" class="position-absolute top-50 start-50 translate-middle text-center">
                <div class="fs-1 fw-bold" id="noTaskTotal">0</div>
                <div class="fs-7 text-muted">Total Active</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-6 mt-4">
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#f1416c;"></span><span class="fs-7 text-muted">No Task</span></div>
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#f6c000;"></span><span class="fs-7 text-muted">Pending</span></div>
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#3e97ff;"></span><span class="fs-7 text-muted">Follow-up</span></div>
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#50cd89;"></span><span class="fs-7 text-muted">Completed</span></div>
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Tasks by SubConsite</div>
            <div class="fs-7 text-muted">Pending / Follow-up / Completed</div>
        </div>
        <div style="height: 360px;">
            <canvas id="subConsiteStatusChart" wire:ignore></canvas>
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Provisional Pledges by SubConsite</div>
            <div class="fs-7 text-muted">Yes / No / Undecided / Pending</div>
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

    <!-- Total pies per question (dropdown/radio/select) -->
    @if(!empty($formTotalsPies) && count($formTotalsPies))
        @foreach($formTotalsPies as $pie)
            <div class="card card-flush p-6 shadow-sm mt-6">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="fs-5 fw-bold faruma">{{ $pie['text'] }}</div>
                    <div class="fs-7 text-muted">Total (All submissions)</div>
                </div>
                <div style="height: 340px;">
                    <canvas id="pie_total_q_{{ $pie['questionId'] }}" wire:ignore></canvas>
                </div>
            </div>
        @endforeach
    @else
        <div class="card card-flush p-6 shadow-sm mt-6">
            <div class="fs-7 text-muted">No dropdown / radio / select questions with data found for this form.</div>
        </div>
    @endif

    <!-- Additional charts per question -->
    @foreach($fsAllCharts as $chart)
    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold faruma">{{ $chart['text'] }}</div>
            <div class="fs-7 text-muted faruma">Options by SubConsite</div>
        </div>
        <div style="height: 360px">
            <canvas id="chart_q_{{ $chart['questionId'] }}" wire:ignore></canvas>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-3">
            @foreach($chart['series'] as $s)
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="width:12px;height:12px;background: {{ $s['color'] }}"></span>
                    <span class="fs-7 text-muted faruma">{{ $s['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

<!-- Load Chart.js first -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        ctx.textBaseline = 'bottom';
        ctx.fillStyle = '#5e6278';
        ctx.font = '12px system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';
        data.labels.forEach((label, index) => {
            let total = 0;
            data.datasets.forEach(ds => { total += (parseFloat(ds.data[index]) || 0); });
            const el = meta.data[index];
            if (!el) return;
            const x = el.x;
            const yTop = chart.scales.y.getPixelForValue(total);
            ctx.fillText(total.toLocaleString(), x, yTop - 6);
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
    const prov = {
        yes: @json($provYes ?? []),
        no: @json($provNo ?? []),
        undecided: @json($provUndecided ?? []),
        pending: @json($provPending ?? []),
    };
    const elProv = document.getElementById('provBySubConsite');
    if(elProv){
        new Chart(elProv, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Yes', data: prov.yes, backgroundColor: '#3e97ff' },
                    { label: 'No', data: prov.no, backgroundColor: '#f6c000' },
                    { label: 'Undecided', data: prov.undecided, backgroundColor: '#a1a5b7' },
                    { label: 'Pending', data: prov.pending, backgroundColor: '#e4e6ef' },
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
        });
    }

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
