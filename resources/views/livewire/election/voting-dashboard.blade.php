<div class="container-fluid py-4">
    <div class="d-flex flex-wrap flex-stack mb-5">
        <div class="d-flex flex-column">
            <h1 class="d-flex align-items-center text-dark fw-bold my-1 fs-3">Voting Dashboard</h1>
            <div class="text-muted">Live updates when representatives are marked/undone as voted</div>
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        <div class="col-12 col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold">Total Voting</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-md-6">
                            <div id="voting_pie" style="min-height: 320px;"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between bg-light-success rounded px-4 py-3">
                                    <div class="fw-semibold text-gray-700">Total Voted</div>
                                    <div class="fw-bold fs-2 text-success">{{ number_format($stats['totalVoted'] ?? 0) }}</div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between bg-light-warning rounded px-4 py-3">
                                    <div class="fw-semibold text-gray-700">Total Not Voted</div>
                                    <div class="fw-bold fs-2 text-warning">{{ number_format($stats['totalNotVoted'] ?? 0) }}</div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between bg-light rounded px-4 py-3">
                                    <div class="fw-semibold text-gray-700">Total (Voted + Not Voted)</div>
                                    <div class="fw-bold fs-2 text-gray-800">{{ number_format(($stats['totalVoted'] ?? 0) + ($stats['totalNotVoted'] ?? 0)) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold">Final Pledge (Voted vs Not Voted)</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div id="voted_by_pledge" style="min-height: 360px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold">Voted by Sub Consite</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div id="voted_by_subconsite" style="min-height: 420px;"></div>
                </div>
            </div>
        </div>

        {{-- NEW: Final pledge YES only (Voted vs Not Voted) by sub consite --}}
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <div class="card-title">
                        <h3 class="fw-bold">Final Pledge Yes (Voted vs Not Voted) by Sub Consite</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div id="pledge_yes_by_subconsite" style="min-height: 420px;"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function(){
                function ensureApex(cb){
                    if(window.ApexCharts) return cb();
                    var s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                    s.onload = cb;
                    document.head.appendChild(s);
                }

                // Keep the latest stats in a global so JS always reads fresh values
                window.__votingDashboardStats = @json($stats);

                let charts = { pie: null, pledge: null, subconsite: null, pledgeYesBySub: null };
                let __lastStatsUpdateAt = 0;

                function getStats(){
                    return window.__votingDashboardStats || {};
                }

                function readStatsFromDom(){
                    try {
                        const el = document.getElementById('voting-dashboard-stats');
                        if(!el) return;
                        const raw = el.dataset.votingDashboardStats;
                        if(!raw) return;
                        window.__votingDashboardStats = JSON.parse(raw);
                    } catch(e){}
                }

                function toNumberArray(v){
                    if(!Array.isArray(v)) return [];
                    return v.map(x => Number(x || 0));
                }

                function toStringArray(v){
                    if(!Array.isArray(v)) return [];
                    return v.map(x => (x ?? '').toString());
                }

                function applyStats(nextStats){
                    if(!nextStats || typeof nextStats !== 'object') return;

                    if(window.__debugVotingEvents){
                        console.debug('[VotingDashboard] stats-updated', nextStats);
                    }

                    __lastStatsUpdateAt = Date.now();
                    window.__votingDashboardStats = nextStats;

                    setTimeout(build, 0);
                }

                function build(){
                    const stats = getStats();

                    const voted = Number(stats.totalVoted || 0);
                    const notVoted = Number(stats.totalNotVoted || 0);

                    const pledgeLabels = toStringArray(stats.pledgeLabels);
                    const pledgeVotedCounts = toNumberArray(stats.pledgeVotedCounts);
                    const pledgeNotVotedCounts = toNumberArray(stats.pledgeNotVotedCounts);

                    const subConsiteLabels = toStringArray(stats.subConsiteLabels);
                    const subConsiteVotedCounts = toNumberArray(stats.subConsiteVotedCounts);
                    const subConsiteNotVotedCounts = toNumberArray(stats.subConsiteNotVotedCounts);

                    // NEW
                    const subConsitePledgeYesVotedCounts = toNumberArray(stats.subConsitePledgeYesVotedCounts);
                    const subConsitePledgeYesNotVotedCounts = toNumberArray(stats.subConsitePledgeYesNotVotedCounts);

                    const pieOptions = {
                        series: [voted, notVoted],
                        labels: ['Voted', 'Not Voted'],
                        chart: { type: 'pie', height: 320, animations: { enabled: true } },
                        legend: { position: 'bottom' },
                        colors: ['#50cd89', '#ffc700'],
                        dataLabels: { enabled: true },
                        tooltip: { y: { formatter: (val) => Number(val).toLocaleString() } }
                    };

                    const pledgeOptions = {
                        series: [
                            { name: 'Voted', data: pledgeVotedCounts },
                            { name: 'Not Voted', data: pledgeNotVotedCounts },
                        ],
                        chart: { type: 'bar', height: 360, toolbar: { show: false }, animations: { enabled: true } },
                        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                        dataLabels: { enabled: true },
                        xaxis: { categories: pledgeLabels, labels: { rotate: -25 } },
                        yaxis: { labels: { formatter: (val) => Number(val).toLocaleString() } },
                        tooltip: { y: { formatter: (val) => Number(val).toLocaleString() } },
                        colors: ['#50cd89', '#a1a5b7']
                    };

                    const subOptions = {
                        series: [
                            { name: 'Voted', data: subConsiteVotedCounts },
                            { name: 'Not Voted', data: subConsiteNotVotedCounts },
                        ],
                        chart: {
                            type: 'bar',
                            height: 420,
                            stacked: true,
                            toolbar: { show: false },
                            animations: { enabled: true }
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                horizontal: false,
                                columnWidth: '55%'
                            }
                        },
                        dataLabels: { enabled: true },
                        xaxis: { categories: subConsiteLabels },
                        yaxis: { labels: { formatter: (val) => Number(val).toLocaleString() } },
                        tooltip: { y: { formatter: (val) => Number(val).toLocaleString() } },
                        legend: { position: 'bottom' },
                        colors: ['#ffc700', '#1b84ff']
                    };

                    // NEW chart options
                    const pledgeYesSubOptions = {
                        series: [
                            { name: 'Voted (Pledge Yes)', data: subConsitePledgeYesVotedCounts },
                            { name: 'Not Voted (Pledge Yes)', data: subConsitePledgeYesNotVotedCounts },
                        ],
                        chart: {
                            type: 'bar',
                            height: 420,
                            stacked: false,
                            toolbar: { show: false },
                            animations: { enabled: true }
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                horizontal: false,
                                columnWidth: '55%'
                            }
                        },
                        dataLabels: { enabled: true },
                        xaxis: { categories: subConsiteLabels },
                        yaxis: { labels: { formatter: (val) => Number(val).toLocaleString() } },
                        tooltip: { y: { formatter: (val) => Number(val).toLocaleString() } },
                        legend: { position: 'bottom' },
                        colors: ['#50cd89', '#a1a5b7']
                    };

                    try { charts.pie && charts.pie.destroy(); } catch(e){}
                    try { charts.pledge && charts.pledge.destroy(); } catch(e){}
                    try { charts.subconsite && charts.subconsite.destroy(); } catch(e){}
                    try { charts.pledgeYesBySub && charts.pledgeYesBySub.destroy(); } catch(e){}

                    const pieEl = document.querySelector('#voting_pie');
                    const pledgeEl = document.querySelector('#voted_by_pledge');
                    const subEl = document.querySelector('#voted_by_subconsite');
                    const pledgeYesSubEl = document.querySelector('#pledge_yes_by_subconsite');

                    if(pieEl){ charts.pie = new ApexCharts(pieEl, pieOptions); charts.pie.render(); }
                    if(pledgeEl){ charts.pledge = new ApexCharts(pledgeEl, pledgeOptions); charts.pledge.render(); }
                    if(subEl){ charts.subconsite = new ApexCharts(subEl, subOptions); charts.subconsite.render(); }
                    if(pledgeYesSubEl){ charts.pledgeYesBySub = new ApexCharts(pledgeYesSubEl, pledgeYesSubOptions); charts.pledgeYesBySub.render(); }

                    setTimeout(() => {
                        try {
                            charts.pie && charts.pie.resize();
                            charts.pledge && charts.pledge.resize();
                            charts.subconsite && charts.subconsite.resize();
                            charts.pledgeYesBySub && charts.pledgeYesBySub.resize();
                        } catch(e){}
                    }, 250);
                }

                ensureApex(() => {
                    readStatsFromDom();
                    build();

                    document.addEventListener('livewire:initialized', () => {
                        try {
                            if(window.Livewire){
                                // Livewire v3: listener callback receives a params object.
                                window.Livewire.on('voting-dashboard:stats-updated', (params = {}) => {
                                    const next = params?.stats ?? params;
                                    applyStats(next);
                                });

                                // Compatibility fallback in case something dispatches unnamed payload
                                window.Livewire.on('voting-dashboard:stats-updated-legacy', (payload) => {
                                    const next = payload?.stats ?? payload;
                                    applyStats(next);
                                });
                            }
                        } catch(e){}

                        if(window.Livewire && typeof Livewire.hook === 'function'){
                            Livewire.hook('commit', ({ succeed }) => {
                                succeed(() => {
                                    // If we just received a websocket-driven stats update, don't overwrite it with DOM data
                                    if(Date.now() - __lastStatsUpdateAt < 1500) {
                                        return;
                                    }
                                    readStatsFromDom();
                                    if(document.querySelector('#voting_pie')) setTimeout(build, 0);
                                });
                            });
                        }
                    });

                    document.addEventListener('livewire:navigated', () => {
                        readStatsFromDom();
                        setTimeout(build, 0);
                    });

                    window.addEventListener('resize', () => {
                        try {
                            charts.pie && charts.pie.resize();
                            charts.pledge && charts.pledge.resize();
                            charts.subconsite && charts.subconsite.resize();
                            charts.pledgeYesBySub && charts.pledgeYesBySub.resize();
                        } catch(e){}
                    });
                });
            })();
        </script>
        <div id="voting-dashboard-stats" class="d-none" data-voting-dashboard-stats='@json($stats)'></div>
    @endpush
</div>
