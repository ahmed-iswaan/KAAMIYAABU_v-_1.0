@section('title', 'Dashboard Overview')

<div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
    <div class="container-xxl">
        @can('agent-render')
        <!-- My Tasks FIRST (Craft style) -->
        <div class="row gx-6 gx-xl-9">
            <div class="col-12">
                <div class="card card-flush card-rounded shadow-sm border-0 my-tasks-card" style="background:linear-gradient(135deg,#0a2f7d 0%,#1542b9 55%,#2d63ff 100%); color:#fff;">
                    <div class="card-body py-6 px-7">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-6">
                            <div class="d-flex flex-column">
                                <span class="fs-1 fw-bold text-white">Overview</span>
                                <span class="fs-8 text-white">Totals for directories you can access (current filters/search applied).</span>
                            </div>
                            <span class="badge bg-white bg-opacity-15 border border-white border-opacity-25 text-white fw-semibold px-4 py-3">Total {{ number_format($overviewTotal ?? 0) }}</span>
                        </div>

                        @php
                            $tAll = (int)($overviewTotal ?? 0);
                            $tDone = (int)($overviewCompleted ?? 0);
                            $tPending = (int)($overviewPending ?? 0);
                            $tByMe = (int)($overviewCompletedByMe ?? 0);

                            $pendingPct = $tAll ? round(($tPending/$tAll)*100) : 0;
                            $completedPct = $tAll ? round(($tDone/$tAll)*100) : 0;
                            $byMePct = $tAll ? round(($tByMe/$tAll)*100) : 0;
                        @endphp

                        <div class="d-flex flex-column flex-lg-row align-items-stretch gap-6">
                            <!-- Pending -->
                            <div class="flex-lg-fill min-w-0">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-warning bg-opacity-20"><span class="text-warning fw-bold">P</span></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-white fw-semibold">Pending</span>
                                            <span class="fw-bold text-warning">{{ $tPending }} <span class="fs-8 text-white">({{ $pendingPct }}%)</span></span>
                                        </div>
                                        <div class="progress bg-white bg-opacity-15 mt-2" style="height:6px;">
                                            <div class="progress-bar bg-warning" style="width: {{ $pendingPct }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Completed -->
                            <div class="flex-lg-fill min-w-0">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-success bg-opacity-20"><span class="text-success fw-bold">C</span></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-white fw-semibold">Completed</span>
                                            <span class="fw-bold text-success">{{ $tDone }} <span class="fs-8 text-white">({{ $completedPct }}%)</span></span>
                                        </div>
                                        <div class="progress bg-white bg-opacity-15 mt-2" style="height:6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $completedPct }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Completed by me -->
                            <div class="flex-lg-fill min-w-0">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-primary bg-opacity-20"><span class="text-primary fw-bold">M</span></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-white fw-semibold">Completed by me</span>
                                            <span class="fw-bold text-primary">{{ $tByMe }} <span class="fs-8 text-white">({{ $byMePct }}%)</span></span>
                                        </div>
                                        <div class="progress bg-white bg-opacity-15 mt-2" style="height:6px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $byMePct }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-top border-white border-opacity-25">
                            <div class="d-flex justify-content-between small text-white">
                                <span>Refreshed {{ now()->format('H:i') }}</span>
                                <span>{{ auth()->user()->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        {{-- Replace Population by Island with admin-style charts --}}
        @can('directory-render')
        <div class="row gx-6 gx-xl-9 mt-6">
            <div class="col-xl-6">
                <div class="card card-flush p-6 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="fs-5 fw-bold">Directories Pending and Completed</div>
                        <div class="fs-7 text-muted">Current active election (call status)</div>
                    </div>
                    <div class="position-relative" style="height: 320px;">
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
            </div>
            <div class="col-xl-6">
                <div class="card card-flush p-6 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="fs-5 fw-bold">Directories by SubConsite</div>
                        <div class="fs-7 text-muted">Pending / Completed (current active election)</div>
                    </div>
                    <div style="height: 320px;">
                        <canvas id="dashSubConsiteStatusChart" wire:ignore></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('dashboard-task-performance')
        <!-- Users Task Performance separate row -->
        <div class="row gx-6 gx-xl-9 mt-6">
            <div class="col-12">
                <div class="card shadow-sm border-0 h-100 card-flush">
                    <div class="card-header border-0 pt-5 pb-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="symbol symbol-40px">
                                <div class="symbol-label bg-success bg-opacity-10"><i class="ki-duotone ki-chart-line-up text-success fs-2hx"></i></div>
                            </div>
                            <div>
                                <h4 class="fw-bold mb-0">Users Task Performance</h4>
                                <div class="text-muted fs-8">Limited to your SubConsite directories</div>
                            </div>
                        </div>
                        <span class="badge badge-light-success fs-8 fw-semibold px-4 py-2">{{ count($userTaskStats) }} Users</span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4 mb-0">
                                <thead>
                                    <tr class="text-gray-600 fw-semibold text-uppercase fs-8">
                                        <th class="ps-3">#</th>
                                        <th>User</th>
                                        <th class="text-center">Total Assigned</th>
                                        <th class="text-center">Pending</th>
                                        <th class="text-center">Completed (Assigned)</th>
                                        <th class="text-center">Completed By User</th>
                                        <th class="text-center">Completed Today</th>
                                        <th class="text-end pe-3" style="min-width:160px;">Completion %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($userTaskStats as $row)
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
                                            <td class="text-center fw-bold">{{ $row['total'] }}</td>
                                            <td class="text-center"><span class="badge badge-light-warning fw-semibold">{{ $row['pending'] }}</span></td>
                                            <td class="text-center"><span class="badge badge-light-success fw-semibold">{{ $row['completed_assigned'] ?? 0 }}</span></td>
                                            <td class="text-center"><span class="badge badge-light-primary fw-semibold" title="Completed by this user (election_directory_call_statuses.updated_by)">{{ $row['completed_by_user'] }}</span></td>
                                            <td class="text-center"><span class="badge badge-light-dark fw-semibold" title="Completed by this user today (election_directory_call_statuses.updated_by)">{{ $row['completed_by_user_today'] ?? 0 }}</span></td>
                                            <td class="text-end pe-3">
                                                <div class="d-flex align-items-center justify-content-end gap-3">
                                                    <div class="progress w-100" style="max-width:120px;height:6px;">
                                                        <div class="progress-bar bg-success" style="width: {{ $row['completed_pct'] }}%"></div>
                                                    </div>
                                                    <span class="fw-bold text-gray-700" style="min-width:42px;">{{ $row['completed_pct'] }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-muted fst-italic py-10 text-center">No data found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>
</div>

@push('scripts')
    {{-- Remove island population chart scripts; add Chart.js for new charts if not already present --}}
    <script>
        (function(){
            function ensureChartJs(cb){
                if(window.Chart){ return cb(); }
                const s=document.createElement('script');
                s.src='https://cdn.jsdelivr.net/npm/chart.js';
                s.onload=cb;
                document.head.appendChild(s);
            }

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

            function buildDashboardCharts(){
                // Pie (Election call status)
                const pieEl = document.getElementById('dashNoTaskPie');
                if(pieEl && window.Chart){
                    const data = [
                        parseInt(@json($pieElectionPendingDirs ?? 0), 10) || 0,
                        parseInt(@json($pieElectionCompletedDirs ?? 0), 10) || 0,
                    ];
                    const labels = ['Pending','Completed'];
                    const colors = ['#f6c000','#50cd89'];
                    if(window.__dashNoTaskPie){ window.__dashNoTaskPie.destroy(); }
                    window.__dashNoTaskPie = new Chart(pieEl, {
                        type: 'doughnut',
                        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
                        options: { responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{ legend:{ position:'bottom' } } }
                    });
                    const totalEl = document.getElementById('dashNoTaskTotal');
                    if(totalEl){ totalEl.textContent = (parseInt(@json($overviewTotal ?? 0),10)||0).toLocaleString(); }
                }

                // Stacked bar (Pending vs Completed by SubConsite)
                const barEl = document.getElementById('dashSubConsiteStatusChart');
                if(barEl && window.Chart){
                    const labels = @json($subConsiteLabels ?? []);
                    if(!labels.length) return;
                    const pending = @json($subConsitePending ?? []);
                    const completed = @json($subConsiteCompleted ?? []);

                    if(window.__dashSubChart){ window.__dashSubChart.destroy(); }
                    window.__dashSubChart = new Chart(barEl, {
                        type:'bar',
                        data:{
                            labels,
                            datasets:[
                                { label:'Pending', data:pending, backgroundColor:'#f6c000' },
                                { label:'Completed', data:completed, backgroundColor:'#50cd89' },
                            ]
                        },
                        options:{
                            responsive:true,
                            maintainAspectRatio:false,
                            scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } },
                            plugins:{ legend:{ position:'bottom' } }
                        },
                        plugins:[TotalsAboveBarsPlugin]
                    });
                }
            }

            function init(){ ensureChartJs(()=> setTimeout(buildDashboardCharts, 50)); }
            window.addEventListener('load', init);
            document.addEventListener('livewire:load', init);
            if(window.Livewire){ Livewire.hook('message.processed', buildDashboardCharts); }
        })();
    </script>

    {{-- keep existing Echo scripts below --}}
    <script>
        // Real-time task status updates for dashboard stats
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Echo === 'undefined') { console.warn('Echo not available for dashboard task updates'); return; }
            try {
                const userId = '{{ auth()->id() }}';
                // Listen on per-user private channel
                Echo.private('agent.tasks.' + userId)
                    .listen('.TaskDataChanged', (e) => {
                        if (!e || !e.change_type) return;
                        if (['status_updated','submission_submitted'].includes(e.change_type)) {
                            window.Livewire.dispatch('task-status-updated');
                        }
                    });
                // Listen on global tasks stats channel (ranked performance + summary updates)
                Echo.private('tasks.global')
                    .listen('.TaskStatsUpdated', (e) => {
                        if (!e || !e.change_type) return;
                        // Any task status affecting stats triggers recompute
                        if (['status_updated','submission_submitted'].includes(e.change_type)) {
                            window.Livewire.dispatch('task-status-updated');
                        }
                    });
            } catch (err) { console.error('Echo channel bind failed', err); }
        });
    </script>
@endpush
