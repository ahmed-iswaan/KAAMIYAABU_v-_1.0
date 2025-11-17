@section('title','Form Responses')
<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
            <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2 mb-4 mb-sm-0">
                <h1 class="text-dark fw-bold my-1 fs-2">Responses: {{ $form->title }}</h1>
                <ul class="breadcrumb fw-semibold fs-base my-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('forms.index') }}" class="text-muted text-hover-primary">Forms</a></li>
                    <li class="breadcrumb-item text-dark">Responses</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('forms.edit',$form->id) }}" class="btn btn-light-primary btn-sm">Edit Form</a>
            </div>
        </div>
    </div>
    <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
        <div class="container-xxl">
            <div class="row g-6">
                <div class="col-xl-7">
                    <div class="card shadow-sm">
                        <div class="card-header border-0 pt-6 pb-0 d-flex justify-content-between align-items-center">
                            <h3 class="card-title fw-bold mb-0">Question Option Distribution</h3>
                            <span class="badge badge-light-info">{{ $totalSubmissions }} Submissions</span>
                        </div>
                        <div class="card-body pt-5">
                            @forelse($questionStats as $qs)
                                <div class="mb-7">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-semibold text-gray-800">{{ $qs['question_text'] }}</div>
                                        <span class="text-muted fs-8">Answered: {{ $qs['total_answered'] }}</span>
                                    </div>
                                    <div class="d-flex flex-column gap-3">
                                        @php $total = max(1, $qs['total_answered']); @endphp
                                        @foreach($qs['options'] as $opt)
                                            @php $pct = round(($opt['count'] / $total) * 100); @endphp
                                            <div class="d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fs-8 text-gray-600">{{ $opt['label'] }}</span>
                                                    <span class="fw-bold text-gray-700">{{ $opt['count'] }} <span class="text-muted fs-8">({{ $pct }}%)</span></span>
                                                </div>
                                                <div class="progress h-6px bg-light-primary mt-1">
                                                    <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @if(!$loop->last)<div class="border-top my-5"></div>@endif
                            @empty
                                <div class="text-muted fs-8">No option-type questions available.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="card shadow-sm h-100">
                        <div class="card-header border-0 pt-6 pb-0">
                            <h3 class="card-title fw-bold mb-0">Submission Directories</h3>
                        </div>
                        <div class="card-body pt-5">
                            <div class="table-responsive" style="max-height:560px; overflow:auto;">
                                <table class="table align-middle table-row-dashed fs-7">
                                    <thead>
                                        <tr class="text-gray-600 fw-semibold">
                                            <th>Directory</th>
                                            <th>ID Card</th>
                                            <th>Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($submissions as $s)
                                            <tr>
                                                <td>{{ $s['directory_name'] ?? '—' }}</td>
                                                <td class="text-muted">{{ $s['id_card_number'] ?? '—' }}</td>
                                                <td class="text-muted" title="{{ $s['submitted_at'] }}">{{ $s['submitted_at']->diffForHumans() }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-10">No submissions yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
