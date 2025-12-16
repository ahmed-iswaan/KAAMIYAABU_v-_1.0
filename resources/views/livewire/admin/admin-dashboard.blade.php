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
        <div class="d-flex gap-6 mt-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge" style="width:12px;height:12px;background:#50cd89;"></span>
                <span class="fs-7 text-muted">With Tasks</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge" style="width:12px;height:12px;background:#f1416c;"></span>
                <span class="fs-7 text-muted">No Tasks</span>
            </div>
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
</div>

{{-- Inline Chart.js for this blade only --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@push('scripts')
<script>
    (function(){
        const ctx = document.getElementById('noTaskPie');
        if(!ctx) return;
        const totalActive = {{ (int) $activeDirectories }};
        const noTasks = {{ (int) $directoriesWithNoTasks }};
        const withTasks = Math.max(0, totalActive - noTasks);

        // Update center total
        document.getElementById('noTaskTotal').textContent = totalActive.toLocaleString();

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['With Tasks','No Tasks'],
                datasets: [{
                    data: [withTasks, noTasks],
                    backgroundColor: ['#50cd89','#f1416c'],
                    borderWidth: 0,
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                }
            }
        });
    })();

    (function(){
        const ctx2 = document.getElementById('subConsiteStatusChart');
        if(!ctx2) return;
        const labels = @json($subConsiteLabels);
        const pending = @json($subConsitePending);
        const followUp = @json($subConsiteFollowUp);
        const completed = @json($subConsiteCompleted);
        const noTasks = @json($subConsiteNoTasks);
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Pending', data: pending, backgroundColor: '#f6c000' },
                    { label: 'Follow-up', data: followUp, backgroundColor: '#3e97ff' },
                    { label: 'Completed', data: completed, backgroundColor: '#50cd89' },
                    { label: 'No Tasks', data: noTasks, backgroundColor: '#f1416c' },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    })();
</script>
@endpush
