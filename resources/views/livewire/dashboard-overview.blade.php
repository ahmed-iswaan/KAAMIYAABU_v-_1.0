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
                        <div class="fs-4 fw-semibold text-gray-400 mb-7">M. Mulah Population</div>

                        <!-- Chart & Legend stacked -->
                        <div class="d-flex flex-column">
                            <!-- Chart occupies full width -->
                            <div class="w-100 mb-5" style="height: 320px; position: relative;">
                                <canvas id="kt_age_gender_chart" class="w-100 h-100"></canvas>
                            </div>

                            <!-- Legend below chart -->
                            <div class="d-flex flex-wrap justify-content-center mb-4">
                                <div class="d-flex align-items-center me-6">
                                    <span class="bullet bg-primary me-2" style="width:12px;height:12px;border-radius:50%;"></span>
                                    <span class="text-gray-600 me-1">Male</span>
                                    <span class="fw-bold text-gray-800">{{ $maleCount }}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                     <span class="bullet me-2" style="width:12px;height:12px;border-radius:50%; background-color:#FF69B4;"></span>
                                    <span class="text-gray-600 me-1">Female</span>
                                    <span class="fw-bold text-gray-800">{{ $femaleCount }}</span>
                                </div>
                            </div>

                            <!-- Age groups legend -->
                            <div class="d-flex flex-wrap justify-content-center">
                                @foreach($ageMaleCounts as $label => $count)
                                    <div class="d-flex align-items-center me-6 mb-3">
                                        <span class="bullet bg-primary me-2" style="width:8px;height:8px;border-radius:50%;"></span>
                                        <span class="text-gray-600 me-1">M {{ $label }}:</span>
                                        <span class="fw-bold text-gray-800">{{ $count }}</span>
                                    </div>
                                @endforeach
                                @foreach($ageFemaleCounts as $label => $count)
                                    <div class="d-flex align-items-center me-6 mb-3">
                                         <span class="bullet me-2" style="width:8px;height:8px;border-radius:50%; background-color:#FF69B4;"></span>
                                        <span class="text-gray-600 me-1">F {{ $label }}:</span>
                                        <span class="fw-bold text-gray-800">{{ $count }}</span>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('kt_age_gender_chart').getContext('2d');

        // Create gradient fills
        const maleGradient = ctx.createLinearGradient(0, 0, 0, 320);
        maleGradient.addColorStop(0, 'rgba(0,163,255,0.8)');
        maleGradient.addColorStop(1, 'rgba(0,163,255,0.2)');

        const femaleGradient = ctx.createLinearGradient(0, 0, 0, 320);
        femaleGradient.addColorStop(0, 'rgba(255,105,180,0.8)'); // hot pink
        femaleGradient.addColorStop(1, 'rgba(255,105,180,0.2)');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_keys($ageMaleCounts)),
                datasets: [
                    {
                        label: 'Male',
                        data: @json(array_values($ageMaleCounts)),
                        backgroundColor: maleGradient,
                        borderRadius: 8,
                        maxBarThickness: 40,
                    },
                    {
                        label: 'Female',
                        data: @json(array_values($ageFemaleCounts)),
                        backgroundColor: femaleGradient,
                        borderRadius: 8,
                        maxBarThickness: 40,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6c757d', font: { size: 14, family: 'Poppins, sans-serif' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(108,117,125,0.2)',
                            borderDash: [5, 5]
                        },
                        ticks: { stepSize: 50, color: '#6c757d', font: { size: 14, family: 'Poppins, sans-serif' } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#fff',
                        titleColor: '#333',
                        bodyColor: '#555',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        boxPadding: 4,
                        bodySpacing: 6
                    }
                }
            }
        });
    });
</script>
@endpush
