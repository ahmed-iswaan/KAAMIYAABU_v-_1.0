<div class="container-xxl py-6">
    <div class="row g-6 mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div>
                            <h3 class="fw-bold mb-0">Election Results (By Voting Box)</h3>
                            <div class="text-muted small">Total = Candidate 1-5 + Invalid</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div wire:ignore>
                        <div id="vote_results_by_box" style="min-height: 420px;"></div>
                    </div>
                    <div id="vote-results-box-chart-data" class="d-none" data-chart='@json($boxChart ?? [])'></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-6 mb-6">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div>
                            <h3 class="fw-bold mb-0">Election Results (By SubConsite)</h3>
                            <div class="text-muted small">Total = Candidate 1-5 + Invalid</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div wire:ignore>
                        <div id="vote_results_by_sub_consite" style="min-height: 420px;"></div>
                    </div>
                    <div id="vote-results-subconsite-chart-data" class="d-none" data-chart='@json($subConsiteChart ?? [])'></div>
                    <div id="vote-results-totals-data" class="d-none" data-totals='@json($totals ?? [])'></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-6 mb-6">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div>
                            <h3 class="fw-bold mb-0">Total Votes</h3>
                            <div class="text-muted small">Candidates + Invalid</div>
                        </div>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <div wire:ignore>
                        <div id="vote_results_totals_pie" style="min-height: 360px;"></div>
                    </div>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">1. Ahmed Aiham Mohamed</span>
                            <span class="fw-semibold">{{ number_format(($totals['c1'] ?? 0)) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">2. Ismail Zariyandhu</span>
                            <span class="fw-semibold">{{ number_format(($totals['c2'] ?? 0)) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">3. Adam Azim</span>
                            <span class="fw-semibold">{{ number_format(($totals['c3'] ?? 0)) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">4. Moosa Ali Jaleel</span>
                            <span class="fw-semibold">{{ number_format(($totals['c4'] ?? 0)) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">5. Abdullah Mahzoom Majid</span>
                            <span class="fw-semibold">{{ number_format(($totals['c5'] ?? 0)) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Invalid Votes</span>
                            <span class="fw-semibold">{{ number_format(($totals['invalid'] ?? 0)) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Election Results Entry</h3>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Voting Box</label>
                    <select class="form-select" wire:model.live="votingBoxId">
                        <option value="">Select voting box</option>
                        @foreach(($votingBoxes ?? []) as $vb)
                            <option value="{{ $vb->id }}">{{ $vb->name }}</option>
                        @endforeach
                    </select>
                    @error('votingBoxId')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Result Date/Time</label>
                    <input type="datetime-local" class="form-control" wire:model.defer="resultDatetime" />
                    @error('resultDatetime')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <div class="alert alert-info mb-0">
                        <div class="d-flex flex-wrap gap-4 justify-content-between">
                            <div>
                                <div class="text-muted small">Total Votes (Entered)</div>
                                <div class="fw-bold fs-3">{{ number_format($totalVotes ?? 0) }}</div>
                                <div class="text-muted small">Candidates 1-5 + Invalid</div>
                            </div>
                            <div>
                                <div class="text-muted small">Total Directories (Voting Box)</div>
                                <div class="fw-bold fs-3">{{ number_format($eligibleCount ?? 0) }}</div>
                                <div class="text-muted small">Active directories in selected box</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">1. Ahmed Aiham Mohamed</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="candidate1Votes" />
                    @error('candidate1Votes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">2. Ismail Zariyandhu</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="candidate2Votes" />
                    @error('candidate2Votes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">3. Adam Azim</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="candidate3Votes" />
                    @error('candidate3Votes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">4. Moosa Ali Jaleel</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="candidate4Votes" />
                    @error('candidate4Votes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">5. Abdullah Mahzoom Majid</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="candidate5Votes" />
                    @error('candidate5Votes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Invalid Votes</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="invalidVotes" />
                    @error('invalidVotes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-end mt-6">
                <button type="button" class="btn btn-primary" wire:click="save">
                    Save
                </button>
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

    let barChartBox = null;
    let barChartSub = null;
    let pieChart = null;

    function readChartData(elId){
        try{
            const el = document.getElementById(elId);
            if(!el) return { labels: [], c1: [], c2: [], c3: [], c4: [], c5: [], invalid: [], total: [] };
            const raw = el.dataset.chart;
            if(!raw) return { labels: [], c1: [], c2: [], c3: [], c4: [], c5: [], invalid: [], total: [] };
            return JSON.parse(raw);
        }catch(e){
            return { labels: [], c1: [], c2: [], c3: [], c4: [], c5: [], invalid: [], total: [] };
        }
    }

    function readTotals(){
        try{
            const el = document.getElementById('vote-results-totals-data');
            if(!el) return { c1: 0, c2: 0, c3: 0, c4: 0, c5: 0, invalid: 0 };
            const raw = el.dataset.totals;
            if(!raw) return { c1: 0, c2: 0, c3: 0, c4: 0, c5: 0, invalid: 0 };
            const obj = JSON.parse(raw);
            return {
                c1: Number(obj.c1 || 0),
                c2: Number(obj.c2 || 0),
                c3: Number(obj.c3 || 0),
                c4: Number(obj.c4 || 0),
                c5: Number(obj.c5 || 0),
                invalid: Number(obj.invalid || 0),
            };
        }catch(e){
            return { c1: 0, c2: 0, c3: 0, c4: 0, c5: 0, invalid: 0 };
        }
    }

    function barOptions(labels, data){
        const c1 = (data.c1||[]).map(n => Number(n||0));
        const c2 = (data.c2||[]).map(n => Number(n||0));
        const c3 = (data.c3||[]).map(n => Number(n||0));
        const c4 = (data.c4||[]).map(n => Number(n||0));
        const c5 = (data.c5||[]).map(n => Number(n||0));
        const invalid = (data.invalid||[]).map(n => Number(n||0));
        const total = (data.total||[]).map(n => Number(n||0));

        return {
            series: [
                { name: '1. Ahmed Aiham Mohamed', data: c1 },
                { name: '2. Ismail Zariyandhu', data: c2 },
                { name: '3. Adam Azim', data: c3 },
                { name: '4. Moosa Ali Jaleel', data: c4 },
                { name: '5. Abdullah Mahzoom Majid', data: c5 },
                { name: 'Invalid Votes', data: invalid },
                { name: 'Total', data: total },
            ],
            chart: {
                type: 'bar',
                height: 420,
                stacked: true,
                toolbar: { show: false },
                animations: { enabled: true }
            },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            dataLabels: { enabled: true },
            xaxis: { categories: labels },
            legend: { position: 'bottom' },
            colors: ['#f97316', '#16a34a', '#0ea5e9', '#a855f7', '#1e5a7a', '#94a3b8', '#111827'],
            tooltip: { y: { formatter: (val) => Number(val).toLocaleString() } },
            yaxis: { labels: { formatter: (val) => Number(val).toLocaleString() } },
        };
    }

    function buildBars(){
        // By box
        const box = readChartData('vote-results-box-chart-data');
        const boxEl = document.querySelector('#vote_results_by_box');
        if(boxEl){
            try{ barChartBox && barChartBox.destroy(); }catch(e){}
            barChartBox = new ApexCharts(boxEl, barOptions(Array.isArray(box.labels)?box.labels:[], box));
            barChartBox.render();
        }

        // By subconsite
        const sub = readChartData('vote-results-subconsite-chart-data');
        const subEl = document.querySelector('#vote_results_by_sub_consite');
        if(subEl){
            try{ barChartSub && barChartSub.destroy(); }catch(e){}
            barChartSub = new ApexCharts(subEl, barOptions(Array.isArray(sub.labels)?sub.labels:[], sub));
            barChartSub.render();
        }
    }

    function buildPie(){
        const totals = readTotals();
        const el = document.querySelector('#vote_results_totals_pie');
        if(!el) return;

        const options = {
            series: [totals.c1, totals.c2, totals.c3, totals.c4, totals.c5, totals.invalid],
            labels: ['1. Ahmed Aiham Mohamed', '2. Ismail Zariyandhu', '3. Adam Azim', '4. Moosa Ali Jaleel', '5. Abdullah Mahzoom Majid', 'Invalid Votes'],
            chart: {
                type: 'pie',
                height: 360,
                toolbar: { show: false },
            },
            legend: { position: 'bottom' },
            colors: ['#f97316', '#16a34a', '#0ea5e9', '#a855f7', '#1e5a7a', '#94a3b8'],
            tooltip: { y: { formatter: (val) => Number(val).toLocaleString() } },
        };

        try{ pieChart && pieChart.destroy(); }catch(e){}
        pieChart = new ApexCharts(el, options);
        pieChart.render();
    }

    function refreshAll(){
        ensureApex(() => {
            buildBars();
            buildPie();
        });
    }

    document.addEventListener('livewire:initialized', refreshAll);
    window.addEventListener('vote-results-updated', refreshAll);
    document.addEventListener('livewire:navigated', refreshAll);
})();
</script>
@endpush
