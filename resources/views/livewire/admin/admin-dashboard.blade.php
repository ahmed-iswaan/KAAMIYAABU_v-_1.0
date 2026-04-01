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

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <div class="fs-5 fw-bold faruma">Q3 Support Export</div>
                <div class="fs-7 text-muted">Directory name, phone numbers, SubConsite code, address, selected option (filters follow the Call Center chart selection)</div>
            </div>
            <button type="button" class="btn btn-sm btn-light-primary" wire:click="downloadQ3SupportDirectoriesCsv">
                Download Q3 Support CSV
            </button>
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

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Call Center by SubConsite</div>
            <div class="fs-7 text-muted">Completed / Attempts / Pending (one bucket per directory)</div>
        </div>
        <div style="height: 360px;">
            <canvas id="ccCompletedAttemptsBySub" wire:ignore></canvas>
        </div>
        <div class="d-flex flex-wrap gap-6 mt-4">
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#50cd89;"></span><span class="fs-7 text-muted">Completed</span></div>
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#3e97ff;"></span><span class="fs-7 text-muted">Attempts</span></div>
            <div class="d-flex align-items-center gap-2"><span class="badge" style="width:12px;height:12px;background:#f6c000;"></span><span class="fs-7 text-muted">Pending</span></div>
        </div>
    </div>

    <!-- Users Performance (Chart) -->
    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Users Performance (Chart)</div>
            <div class="fs-7 text-muted">Completed vs Attempts (active election)</div>
        </div>
        <div style="height: 520px;">
            <canvas id="usersPerformanceBar" wire:ignore></canvas>
        </div>

        @php
            $upLabels = collect($userPerformanceRows ?? [])->pluck('name')->values()->all();
            $upCompleted = collect($userPerformanceRows ?? [])->pluck('completed')->values()->all();
            $upAttempts = collect($userPerformanceRows ?? [])->pluck('attempts')->values()->all();
        @endphp

        {{-- Chart is initialized by the global initAllAdminDashboardCharts() script below to avoid Livewire hook collisions. --}}
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Provisional Pledges (Total by Directory)</div>
            <div class="fs-7 text-muted">Each directory is counted once (Yes &gt; No &gt; Undecided &gt; Not voting &gt; Pending)</div>
        </div>
        <div class="position-relative" style="height: 320px;">
            <canvas id="provTotalsPie" wire:ignore class="position-absolute top-0 start-0 w-100 h-100"></canvas>
        </div>
        <div class="d-flex flex-wrap gap-4 mt-4">
            @foreach(($provTotalsPieLabels ?? []) as $i => $lbl)
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-light">{{ $lbl }}</span>
                    <span class="fs-7 text-muted">{{ (int) (($provTotalsPieCounts[$i] ?? 0)) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-flush p-6 shadow-sm mt-6">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="fs-5 fw-bold">Q3 Support (Total)</div>
            <div class="fs-7 text-muted">Options + Pending (Not answered) — active election</div>
        </div>
        <div class="position-relative" style="height: 320px;">
            <canvas id="ccQ3SupportPie" wire:ignore class="position-absolute top-0 start-0 w-100 h-100"></canvas>
        </div>
        <div class="d-flex flex-wrap gap-4 mt-4">
            @foreach(($ccQ3SupportPieLabels ?? []) as $i => $lbl)
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-light">{{ $lbl }}</span>
                    <span class="fs-7 text-muted">{{ (int) (($ccQ3SupportPieCounts[$i] ?? 0)) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Load Chart.js first -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function(){
    // Single source of truth initializer to avoid script collisions
    window.initAllAdminDashboardCharts = function initAllAdminDashboardCharts(){
        if (!window.Chart) return;

        // 1) Directories Pending/Completed (pie)
        (function(){
            const el = document.getElementById('dashNoTaskPie');
            if(!el) return;

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
                    datasets: [{ data: [pending, completed], backgroundColor: ['#f6c000', '#50cd89'], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } }
            });
        })();

        // 2) Directories by SubConsite (bar)
        (function(){
            const el = document.getElementById('dashSubConsiteStatusChart');
            if(!el) return;
            const labels = @json($dashSubConsiteLabels ?? []);
            const pending = @json($dashSubConsitePending ?? []);
            const completed = @json($dashSubConsiteCompleted ?? []);
            if (!labels || !labels.length) return;

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
            el.__chart = new Chart(el, {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Pending', data: pending, backgroundColor: '#f6c000', stack: 's1' },
                    { label: 'Completed', data: completed, backgroundColor: '#50cd89', stack: 's1' },
                ] },
                options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
                plugins: (typeof TotalsAboveBarsPlugin !== 'undefined') ? [TotalsAboveBarsPlugin] : []
            });
        })();

        // 3) Call Center by SubConsite (bar)
        (function(){
            const el = document.getElementById('ccCompletedAttemptsBySub');
            if(!el) return;
            const labels = @json($ccSubConsiteBarLabels ?? []);
            const completed = @json($ccSubConsiteBarCompleted ?? []);
            const attempts = @json($ccSubConsiteBarAttempts ?? []);
            const pending = @json($ccSubConsiteBarPending ?? []);
            if (!labels || !labels.length) return;

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
            el.__chart = new Chart(el, {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Completed', data: completed, backgroundColor: '#50cd89', stack: 's1' },
                    { label: 'Attempts', data: attempts, backgroundColor: '#3e97ff', stack: 's1' },
                    { label: 'Pending', data: pending, backgroundColor: '#f6c000', stack: 's1' },
                ] },
                options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
                plugins: (typeof TotalsAboveBarsPlugin !== 'undefined') ? [TotalsAboveBarsPlugin] : []
            });
        })();

        // 3.1) Users Performance (bar)
        (function(){
            const el = document.getElementById('usersPerformanceBar');
            if(!el) return;

            const labels = @json($upLabels ?? []);
            const completed = @json($upCompleted ?? []);
            const attempts = @json($upAttempts ?? []);
            if (!labels || !labels.length) return;

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
            el.__chart = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Attempts', data: attempts, backgroundColor: 'rgba(62, 151, 255, 0.85)', borderColor: 'rgba(62, 151, 255, 1)', borderWidth: 1, stack: 's1' },
                        { label: 'Completed', data: completed, backgroundColor: 'rgba(255, 159, 64, 0.85)', borderColor: 'rgba(255, 159, 64, 1)', borderWidth: 1, stack: 's1' },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: { display: true, text: 'Users Performance', font: { size: 18, weight: '600' }, padding: { top: 10, bottom: 10 } },
                        legend: { position: 'bottom', labels: { boxWidth: 14, boxHeight: 14 } },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { stacked: true, ticks: { autoSkip: false }, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
                    }
                },
                plugins: (typeof TotalsAboveBarsPlugin !== 'undefined') ? [TotalsAboveBarsPlugin] : []
            });
        })();

        // 4) Q-position charts (Q1/Q3/Q4/Q5 by SubConsite)
        (function(){
            const charts = @json($qsBySubCharts ?? []);
            if(!charts || !charts.length) return;

            charts.forEach(function(c){
                const el = document.getElementById('chart_qpos_' + c.position);
                if(!el) return;

                if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
                const datasets = (c.series || []).map(s => ({ label: s.label, data: s.data, backgroundColor: s.color, stack: 's1' }));

                el.__chart = new Chart(el, {
                    type: 'bar',
                    data: { labels: c.labels || [], datasets },
                    options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
                    plugins: (typeof TotalsAboveBarsPlugin !== 'undefined') ? [TotalsAboveBarsPlugin] : []
                });
            });
        })();

        // 5) Provisional totals by directory (pie)
        (function(){
            const el = document.getElementById('provTotalsPie');
            if(!el) return;
            const labels = @json($provTotalsPieLabels ?? []);
            const data = @json($provTotalsPieCounts ?? []);
            if (!labels || !labels.length) return;

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
            const vals = (data || []).map(v => parseInt(v, 10) || 0);
            const colors = labels.map((_, i) => ['#3e97ff','#50cd89','#f6c000','#f1416c','#a1a5b7'][i % 5]);
            el.__chart = new Chart(el, {
                type: 'doughnut',
                data: { labels, datasets: [{ data: vals, backgroundColor: colors, borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
            });
        })();

        // 5.1) Q3 Support (Call Center) totals (pie)
        (function(){
            const el = document.getElementById('ccQ3SupportPie');
            if(!el) return;

            const labels = @json($ccQ3SupportPieLabels ?? []);
            const data = @json($ccQ3SupportPieCounts ?? []);
            if (!labels || !labels.length) return;

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
            const vals = (data || []).map(v => parseInt(v, 10) || 0);
            const colors = labels.map((_, i) => ['#3e97ff','#50cd89','#f6c000','#f1416c','#a1a5b7','#7239ea'][i % 6]);

            el.__chart = new Chart(el, {
                type: 'doughnut',
                data: { labels, datasets: [{ data: vals, backgroundColor: colors, borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
            });
        })();

        // 6) Provisional pledges by SubConsite (stacked bar)
        (function(){
            const el = document.getElementById('provBySubConsite');
            if(!el) return;
            const labels = @json($pledgeLabels ?? []);
            if (!labels || !labels.length) return;

            const pledged = @json($provPledged ?? []);
            const notPledged = @json($provNotPledged ?? []);

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
            el.__chart = new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Not Pledged', data: notPledged, backgroundColor: '#1b84ff', stack: 's1' },
                        { label: 'Pledged', data: pledged, backgroundColor: '#f6c000', stack: 's1' },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
                    plugins: { legend: { position: 'bottom' } }
                },
                plugins: (typeof TotalsAboveBarsPlugin !== 'undefined') ? [TotalsAboveBarsPlugin] : []
            });
        })();

        // 7) Final pledges by SubConsite (stacked bar)
        (function(){
            const el = document.getElementById('finalBySubConsite');
            if(!el) return;
            const labels = @json($pledgeLabels ?? []);
            if (!labels || !labels.length) return;

            const yes = @json($finalYes ?? []);
            const no = @json($finalNo ?? []);
            const undecided = @json($finalUndecided ?? []);
            const pending = @json($finalPending ?? []);

            if (el.__chart) { try { el.__chart.destroy(); } catch(e) {} }
            el.__chart = new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Yes', data: yes, backgroundColor: '#3e97ff', stack: 's1' },
                        { label: 'No', data: no, backgroundColor: '#f6c000', stack: 's1' },
                        { label: 'Undecided', data: undecided, backgroundColor: '#a1a5b7', stack: 's1' },
                        { label: 'Pending', data: pending, backgroundColor: '#e4e6ef', stack: 's1' },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        })();
    };

    function init(){
        try { window.initAllAdminDashboardCharts(); } catch(e) {}
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('livewire:navigated', init);
    if (window.Livewire?.hook) {
        Livewire.hook('morph.updated', init);
    }
})();
</script>
