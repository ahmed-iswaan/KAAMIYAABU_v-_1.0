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
                                <span class="fs-1 fw-bold text-white">My Tasks</span>
                                <span class="fs-8 text-white">Live status summary</span>
                            </div>
                            <span class="badge bg-white bg-opacity-15 border border-white border-opacity-25 text-white fw-semibold px-4 py-3">Total {{ $taskTotal }}</span>
                        </div>
                        @php $pendingPct = $taskTotal ? round(($taskPending/$taskTotal)*100) : 0; $followPct = $taskTotal ? round(($taskFollowUp/$taskTotal)*100) : 0; $completedPct = $taskTotal ? round(($taskCompleted/$taskTotal)*100) : 0; @endphp
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
                                            <span class="fw-bold text-warning">{{ $taskPending }} <span class="fs-8 text-white">({{ $pendingPct }}%)</span></span>
                                        </div>
                                        <div class="progress bg-white bg-opacity-15 mt-2" style="height:6px;">
                                            <div class="progress-bar bg-warning" style="width: {{ $pendingPct }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Follow Up -->
                            <div class="flex-lg-fill min-w-0">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-info bg-opacity-20"><span class="text-info fw-bold">F</span></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-white fw-semibold">Follow Up</span>
                                            <span class="fw-bold text-info">{{ $taskFollowUp }} <span class="fs-8 text-white">({{ $followPct }}%)</span></span>
                                        </div>
                                        <div class="progress bg-white bg-opacity-15 mt-2" style="height:6px;">
                                            <div class="progress-bar bg-info" style="width: {{ $followPct }}%"></div>
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
                                            <span class="fw-bold text-success">{{ $taskCompleted }} <span class="fs-8 text-white">({{ $completedPct }}%)</span></span>
                                        </div>
                                        <div class="progress bg-white bg-opacity-15 mt-2" style="height:6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $completedPct }}%"></div>
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
       @can('directory-render')
        <!-- Population by Island row (full width) -->
        <div class="row gx-6 gx-xl-9 mt-6 align-items-stretch">
            <div class="col-12">
                <div class="card h-100 card-flush shadow-sm">
                    <div class="card-body p-9">
                        <div class="d-flex justify-content-between align-items-start mb-5">
                            <div class="d-flex flex-column">
                                <span class="fs-2 fw-bold">{{ $totalPopulation }}</span>
                                <span class="text-gray-400 fw-semibold">Population by Island</span>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <div wire:ignore class="w-100 mb-5" style="height:420px;position:relative;">
                                <canvas id="kt_island_population_chart" style="width:100%;height:100%;"></canvas>
                            </div>
                            <div id="island_population_payload" data-labels='@json($islandLabels)' data-males='@json($islandMaleCounts)' data-females='@json($islandFemaleCounts)'></div>
                            <div class="d-flex flex-wrap justify-content-center mb-4">
                                <div class="d-flex align-items-center me-6 mb-3">
                                    <span class="bullet bg-primary me-2" style="width:12px;height:12px;border-radius:50%;"></span>
                                    <span class="text-gray-600 me-1">Male</span>
                                    <span class="fw-bold text-gray-800">{{ $maleCount }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="bullet me-2" style="width:12px;height:12px;border-radius:50%; background-color:#FF69B4;"></span>
                                    <span class="text-gray-600 me-1">Female</span>
                                    <span class="fw-bold text-gray-800">{{ $femaleCount }}</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center">
                                @foreach($islandLabels as $idx => $label)
                                    <div class="d-flex align-items-center me-6 mb-3" style="min-width:200px;">
                                        <span class="badge bg-light text-dark fw-semibold me-2" style="min-width:90px;">{{ $label }}</span>
                                        <span class="text-gray-600 me-1">M:</span><span class="fw-bold text-gray-800 me-3">{{ $islandMaleCounts[$idx] ?? 0 }}</span>
                                        <span class="text-gray-600 me-1">F:</span><span class="fw-bold text-gray-800 me-3">{{ $islandFemaleCounts[$idx] ?? 0 }}</span>
                                        <span class="text-gray-600 me-1">T:</span><span class="fw-bold text-gray-800">{{ $islandTotals[$idx] ?? 0 }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
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
                                <div class="text-muted fs-8">Ranked by total completed tasks • Daily completions shown</div>
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
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Pending</th>
                                        <th class="text-center">Follow Up</th>
                                        <th class="text-center">Completed</th>
                                        <th class="text-center">Daily</th>
                                        <th class="text-end pe-3" style="min-width:160px;">Completion</th>
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
                                            <td class="text-center"><span class="badge badge-light-info fw-semibold">{{ $row['follow_up'] }}</span></td>
                                            <td class="text-center"><span class="badge badge-light-success fw-semibold">{{ $row['completed'] }}</span></td>
                                            <td class="text-center"><span class="badge badge-light-primary fw-semibold" title="Tasks completed today">{{ $row['completed_today'] }}</span></td>
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
                                        <tr><td colspan="8" class="text-muted fst-italic py-10 text-center">No task assignments found.</td></tr>
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
<script>
(function(){
    function ensureChartJs(cb){ if(window.Chart){ return cb(); } const s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/chart.js'; s.onload=cb; document.head.appendChild(s); }
    function buildOrUpdate(){
        const payloadEl=document.getElementById('island_population_payload');
        const canvas=document.getElementById('kt_island_population_chart');
        if(!payloadEl||!canvas){ return; }
        let labels, males, females;
        try{ labels=JSON.parse(payloadEl.getAttribute('data-labels')||'[]'); }catch{ labels=[]; }
        try{ males=JSON.parse(payloadEl.getAttribute('data-males')||'[]'); }catch{ males=[]; }
        try{ females=JSON.parse(payloadEl.getAttribute('data-females')||'[]'); }catch{ females=[]; }
        // If Livewire update fires before data ready, keep existing chart
        if(!Array.isArray(labels) || labels.length===0){ if(window.__islandChart){ console.log('[IslandChart] Skip update (empty labels)'); } return; }
        const ctx=canvas.getContext('2d'); if(!ctx){ return; }
        // Update existing chart without destroy to prevent flicker
        if(window.__islandChart){
            window.__islandChart.data.labels = labels;
            window.__islandChart.data.datasets[0].data = males.map(n=>+n||0);
            window.__islandChart.data.datasets[1].data = females.map(n=>+n||0);
            window.__islandChart.update();
            console.log('[IslandChart] Updated', labels.length);
            return;
        }
        const h=canvas.clientHeight||420;
        const maleGradient=ctx.createLinearGradient(0,0,0,h); maleGradient.addColorStop(0,'rgba(0,163,255,0.8)'); maleGradient.addColorStop(1,'rgba(0,163,255,0.2)');
        const femaleGradient=ctx.createLinearGradient(0,0,0,h); femaleGradient.addColorStop(0,'rgba(255,105,180,0.8)'); femaleGradient.addColorStop(1,'rgba(255,105,180,0.2)');
        window.__islandChart=new Chart(ctx,{ type:'bar', data:{ labels:labels, datasets:[
            { label:'Male', data:males.map(n=>+n||0), backgroundColor:maleGradient, borderRadius:6, maxBarThickness:42, stack:'gender' },
            { label:'Female', data:females.map(n=>+n||0), backgroundColor:femaleGradient, borderRadius:6, maxBarThickness:42, stack:'gender' }
        ]}, options:{ responsive:true, maintainAspectRatio:false, interaction:{ mode:'index', intersect:false }, animation:false, scales:{ x:{ stacked:true, grid:{ display:false } }, y:{ beginAtZero:true, stacked:true } }, plugins:{ legend:{ display:true, position:'top' } } } });
        console.log('[IslandChart] Created', labels.length);
    }
    function init(){ ensureChartJs(()=> setTimeout(buildOrUpdate, 30)); }
    window.addEventListener('load', init);
    document.addEventListener('livewire:load', init);
    if(window.Livewire){ Livewire.hook('message.processed', buildOrUpdate); }
})();
</script>

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
