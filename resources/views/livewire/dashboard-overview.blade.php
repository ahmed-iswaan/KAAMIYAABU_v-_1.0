@section('title', 'Dashboard Overview')

<div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
    <div class="container-xxl">
        <div class="row gx-6 gx-xl-9">
            <!-- Full-width card -->
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body p-9">
                        <!-- Total -->
                        <div class="fs-2hx fw-bold">{{ $totalPopulation }}</div>
                        <div class="fs-4 fw-semibold text-gray-400 mb-7">Population by Island</div>

                        <!-- Chart & Legend stacked -->
                        <div class="d-flex flex-column">
                            <!-- Chart occupies full width -->
                            <div class="w-100 mb-5" style="height: 420px; position: relative;">
                                <canvas id="kt_island_population_chart" class="w-100 h-100"></canvas>
                            </div>

                            <!-- Legend below chart -->
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

                            <!-- Island totals legend (optional condensed list) -->
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
                        <!-- end Chart & Legend -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('kt_island_population_chart').getContext('2d');
        // Debug: log chart data
        const islandLabels = @json($islandLabels);
        const islandMaleCounts = @json($islandMaleCounts);
        const islandFemaleCounts = @json($islandFemaleCounts);
        console.log('islandLabels:', islandLabels);
        console.log('islandMaleCounts:', islandMaleCounts);
        console.log('islandFemaleCounts:', islandFemaleCounts);
        // Defensive: check if arrays are valid and canvas is visible
        if (!ctx) {
            console.error('Chart canvas not found or not visible.');
            return;
        }
        if (!islandLabels.length) {
            console.warn('No islandLabels data for chart.');
        }
        if (!islandMaleCounts.length && !islandFemaleCounts.length) {
            console.warn('No data for chart bars.');
        }
        // Create gradient fills OUTSIDE dataset
        const maleGradient = ctx.createLinearGradient(0, 0, 0, 420);
        maleGradient.addColorStop(0, 'rgba(0,163,255,0.8)');
        maleGradient.addColorStop(1, 'rgba(0,163,255,0.2)');
        const femaleGradient = ctx.createLinearGradient(0, 0, 0, 420);
        femaleGradient.addColorStop(0, 'rgba(255,105,180,0.8)');
        femaleGradient.addColorStop(1, 'rgba(255,105,180,0.2)');
        try {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: islandLabels,
                    datasets: [
                        {
                            label: 'Male',
                            data: islandMaleCounts,
                            backgroundColor: maleGradient,
                            borderRadius: 6,
                            maxBarThickness: 42,
                            stack: 'gender'
                        },
                        {
                            label: 'Female',
                            data: islandFemaleCounts,
                            backgroundColor: femaleGradient,
                            borderRadius: 6,
                            maxBarThickness: 42,
                            stack: 'gender'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: { color: '#6c757d', font: { size: 12, family: 'Poppins, sans-serif' } }
                        },
                        y: {
                            beginAtZero: true,
                            stacked: true,
                            grid: { color: 'rgba(108,117,125,0.15)', borderDash: [4,4] },
                            ticks: { color: '#6c757d', font: { size: 12, family: 'Poppins, sans-serif' } }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#222',
                            bodyColor: '#444',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            callbacks: {
                                footer: (items) => {
                                    let sum = 0; items.forEach(i => sum += i.parsed.y || 0);
                                    return 'Total: ' + sum;
                                }
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Chart.js error:', e);
        }
    });
</script>
@endpush
