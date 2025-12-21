<div class="container-xxl py-6">
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
            <canvas id="noTaskPie" class="position-absolute top-0 start-0 w-100 h-100"></canvas>
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
            <canvas id="subConsiteStatusChart"></canvas>
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Active Directories by SubConsite & Gender</div>
            <div class="fs-7 text-muted">Male / Female / Other</div>
        </div>
        <div style="height: 360px;">
            <canvas id="dirBySubConsiteGender"></canvas>
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Provisional Pledges by SubConsite</div>
            <div class="fs-7 text-muted">Yes / No / Undecided / Pending</div>
        </div>
        <div style="height: 360px;"><canvas id="provBySubConsite"></canvas></div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Final Pledges by SubConsite</div>
            <div class="fs-7 text-muted">Yes / No / Undecided / Pending</div>
        </div>
        <div style="height: 360px;"><canvas id="finalBySubConsite"></canvas></div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Form Submissions by SubConsite</div>
            <div class="d-flex gap-3">
                <select class="form-select form-select-sm" style="min-width:220px" wire:model="selectedFormId">
                    @foreach($forms as $f)
                        <option value="{{ $f->id }}">{{ $f->title }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm" style="min-width:220px" wire:model="selectedQuestionId">
                    @php $questions = \App\Models\FormQuestion::where('form_id',$selectedFormId)->whereIn('type',["dropdown","radio"])->orderBy('position')->get(['id','question_text']); @endphp
                    @foreach($questions as $q)
                        <option value="{{ $q->id }}">{{ $q->question_text }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="height: 360px">
            <canvas id="formSubmissionsBySub"></canvas>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-3">
            @foreach($fsSeries as $s)
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="width:12px;height:12px;background: {{ $s['color'] }}"></span>
                    <span class="fs-7 text-muted">{{ $s['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
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

<!-- Directories by subConsite & gender chart -->
<script>
(function(){
    const el = document.getElementById('dirBySubConsiteGender');
    if(!el) return;
    const labels = @json($dirSubConsiteLabels ?? []);
    const male = @json($dirMaleCounts ?? []);
    const female = @json($dirFemaleCounts ?? []);
    const other = @json($dirOtherCounts ?? []);
    if (!labels.length) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Male', data: male, backgroundColor: '#3e97ff' },
                { label: 'Female', data: female, backgroundColor: '#f1416c' },
                { label: 'Other', data: other, backgroundColor: '#a1a5b7' },
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

<!-- Form Submissions by SubConsite chart -->
<script>
(function(){
    const el = document.getElementById('formSubmissionsBySub');
    if(!el) return;
    const labels = @json($fsLabels ?? []);
    const series = @json($fsSeries ?? []);
    if (!labels.length || !series.length) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: series.map(s => ({ label: s.label, data: s.data, backgroundColor: s.color }))
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
    });
})();
</script>
