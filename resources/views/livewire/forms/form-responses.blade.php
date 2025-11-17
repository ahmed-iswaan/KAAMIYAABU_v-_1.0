@section('title','Form Responses')
@push('styles')
<style>
    @font-face {font-family:'Faruma';src:url('/fonts/faruma.woff2') format('woff2'),url('/fonts/faruma.woff') format('woff');font-weight:400;font-style:normal;font-display:swap;unicode-range:U+0780-07B1;}
    [lang="dv"], .dv-text {font-family:'Faruma','Segoe UI','Tahoma',sans-serif !important; letter-spacing:.8px; word-spacing:3px; line-height:2;}
    .card.card-analytics{border:1px solid #f1f3f9;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#fcfcfd 100%);}    
    .question-block{background:#ffffff;border-radius:10px;padding:0 0 4px 0;}
    .question-block:not(:last-child){margin-bottom:2.25rem;}
    .question-separator{height:1px;background:#eee;margin:2.5rem 0;}
    .progress{background:#eef3f9;}
    .progress-bar{transition:width .6s ease;}
    .progress-bar.glow{box-shadow:0 0 0 1px rgba(255,255,255,.4) inset,0 0 0 1px rgba(0,0,0,.05),0 2px 6px -1px rgba(0,0,0,.08);}
    .option-row:hover .progress-bar{filter:brightness(1.05);}    
    .option-label{max-width:70%;}
    @media (max-width: 991.98px){
        .option-label{max-width:60%;}
    }
    @media (max-width: 767.98px){
        .row.g-6 > [class*='col-']{margin-bottom:1.75rem;}
        .option-label{max-width:100%;}
        .table.submission-table thead{display:none;}
        .table.submission-table tbody tr{display:block;padding:.85rem 1rem;border:1px solid #f1f3f9;border-radius:10px;margin-bottom:.85rem;background:#fff;}
        .table.submission-table tbody td{display:flex;justify-content:space-between;padding:.35rem 0 !important;}
        .table.submission-table tbody td:before{content:attr(data-label);font-weight:600;color:#5e6278;}
    }
    .table-responsive{position:relative;}
    .table.submission-table thead th{position:sticky;top:0;background:#fafbfc;z-index:5;}
    .badge-soft{background:#f1edff;color:#7239ea;}
    .respondent-toggle{cursor:pointer; font-size:11px;}
    .respondent-toggle-btn{font-size:11px; line-height:1; padding:4px 12px; border-radius:30px; background:#ffffff; border:1px solid #e2e6ee; display:inline-flex; align-items:center; gap:6px; transition:.25s; box-shadow:0 1px 2px rgba(0,0,0,.04);}    
    .respondent-toggle-btn:hover{background:#f5f8fa;}
    .respondent-toggle-btn .arrow{transition:.3s transform; display:inline-block;}
    .respondent-toggle-btn[aria-expanded="true"] .arrow{transform:rotate(90deg);}  
    .respondent-list{border:1px solid #e9edf3; background:linear-gradient(180deg,#ffffff 0%, #f9fbfd 100%); border-radius:12px; padding:.65rem .9rem .75rem; margin-top:.45rem; box-shadow:0 2px 6px -2px rgba(0,0,0,.08);}    
    .respondent-table thead tr{background:#f3f6fa;}  
    .respondent-table thead th{font-weight:600; font-size:10px; text-transform:uppercase; letter-spacing:.5px;}
    .respondent-table tbody td{padding:.35rem .5rem !important; font-size:11px;}
    .respondent-id-pill{background:#eef3f9; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600;}
    .option-row{padding:.55rem .75rem .7rem; border-radius:10px; border:1px solid #f1f3f6; background:#ffffff;}
    .option-row:not(:last-child){margin-bottom:.75rem;}
    .option-row:hover{border-color:#d9dde3; box-shadow:0 2px 8px -4px rgba(0,0,0,.06);}    
</style>
@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        // Allow only one open respondent list at a time
        document.querySelectorAll('.toggle-respondents').forEach(btn => {
            btn.addEventListener('click', function(){
                const targetId = this.getAttribute('data-target');
                const target = document.getElementById(targetId);
                if(!target) return;
                const expanded = this.getAttribute('aria-expanded') === 'true';
                // Close others
                document.querySelectorAll('.respondent-toggle-btn[aria-expanded="true"]').forEach(openBtn => {
                    if(openBtn!==this){
                        openBtn.setAttribute('aria-expanded','false');
                        const otherTarget = document.getElementById(openBtn.getAttribute('data-target'));
                        if(otherTarget){ otherTarget.style.display='none'; }
                        openBtn.querySelector('.toggle-text').textContent='Show';
                    }
                });
                if(expanded){
                    target.style.display='none';
                    this.setAttribute('aria-expanded','false');
                    this.querySelector('.toggle-text').textContent='Show';
                } else {
                    target.style.display='block';
                    this.setAttribute('aria-expanded','true');
                    this.querySelector('.toggle-text').textContent='Hide';
                }
            });
        });
    });
</script>
@endpush
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
                    <div class="card card-analytics shadow-sm h-100 d-flex flex-column">
                        <div class="card-header border-0 pt-6 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex flex-column">
                                <h3 class="card-title fw-bold mb-1">Question Option Distribution</h3>
                                <span class="text-muted fs-8">Insight per option (only option-type questions)</span>
                            </div>
                            <span class="badge badge-soft fw-semibold px-4 py-2">{{ $totalSubmissions }} Submissions</span>
                        </div>
                        <div class="card-body pt-5 flex-grow-1">
                            @php $colorClasses=['bg-primary','bg-success','bg-info','bg-warning','bg-danger','bg-dark']; @endphp
                            @forelse($questionStats as $qs)
                                <div class="question-block">
                                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                        <div class="fw-semibold text-gray-800 {{ $form->language==='dv' ? 'text-end w-100 dv-text' : '' }} fs-6" {{ $form->language==='dv' ? 'lang=dv' : '' }}>
                                            {{ $loop->iteration }}. {{ $qs['question_text'] }}
                                        </div>
                                        <span class="text-muted fs-8">Answered: {{ $qs['total_answered'] }}</span>
                                    </div>
                                    <div class="d-flex flex-column gap-4">
                                        @php $total = max(1, $qs['total_answered']); @endphp
                                        @foreach($qs['options'] as $opt)
                                            @php $pct = round(($opt['count'] / $total) * 100); $barClass = $colorClasses[$loop->index % count($colorClasses)]; $listId = 'resp-'.$qs['question_id'].'-'.$loop->index; @endphp
                                            <div class="d-flex flex-column option-row">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fs-8 text-gray-700 option-label fw-semibold {{ $form->language==='dv' ? 'text-end w-100 dv-text' : '' }}" {{ $form->language==='dv' ? 'lang=dv' : '' }}>{{ $opt['label'] }}</span>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-bold text-gray-800">{{ $opt['count'] }} <span class="text-muted fs-8">({{ $pct }}%)</span></span>
                                                        @if($opt['count']>0 && !empty($opt['respondents']))
                                                            <button type="button" class="respondent-toggle-btn toggle-respondents" data-target="{{ $listId }}" aria-expanded="false">
                                                                <span class="arrow">▶</span>
                                                                <span class="toggle-text">Show</span>
                                                                <span class="badge badge-light border fw-semibold" style="font-size:9px;">{{ count($opt['respondents']) }}</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="progress h-8px mb-2" title="{{ $opt['count'] }} / {{ $total }} ({{ $pct }}%)">
                                                    <div class="progress-bar {{ $barClass }} glow" style="width: {{ $pct }}%"></div>
                                                </div>
                                                @if(!empty($opt['respondents']))
                                                    <div class="respondent-list" id="{{ $listId }}" style="display:none;">
                                                        <table class="table respondent-table table-sm craft-table mb-0">
                                                            <thead>
                                                                <tr class="text-gray-600">
                                                                    <th class="border-0 ps-2">Directory</th>
                                                                    <th class="border-0">ID Card</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($opt['respondents'] as $r)
                                                                    <tr class="border-dashed border-bottom">
                                                                        <td class="ps-2 {{ $form->language==='dv' ? 'dv-text text-end' : '' }}" {{ $form->language==='dv' ? 'lang=dv' : '' }}>{{ $r['directory_name'] }}</td>
                                                                        <td><span class="respondent-id-pill">{{ $r['id_card_number'] }}</span></td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if($qs['total_answered']==0)
                                            <div class="text-muted fs-8">No answers yet for this question.</div>
                                        @endif
                                    </div>
                                </div>
                                @if(!$loop->last)<div class="question-separator"></div>@endif
                            @empty
                                <div class="text-muted fs-8">No option-type questions available.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="card card-analytics shadow-sm h-100 d-flex flex-column">
                        <div class="card-header border-0 pt-6 pb-0 d-flex justify-content-between align-items-center flex-wrap">
                            <h3 class="card-title fw-bold mb-1">Submission Directories</h3>
                            <span class="text-muted fs-8">Latest first</span>
                        </div>
                        <div class="card-body pt-5 flex-grow-1 d-flex flex-column">
                            <div class="table-responsive flex-grow-1" style="max-height:560px; overflow:auto;">
                                <table class="table submission-table align-middle table-row-dashed fs-7 mb-0">
                                    <thead>
                                        <tr class="text-gray-600 fw-semibold">
                                            <th class="min-w-150px">Directory</th>
                                            <th class="min-w-90px">ID Card</th>
                                            <th class="min-w-120px">Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($submissions as $s)
                                            <tr>
                                                <td data-label="Directory" class="{{ $form->language==='dv' ? 'text-end dv-text' : '' }}" {{ $form->language==='dv' ? 'lang=dv' : '' }}>{{ $s['directory_name'] ?? '—' }}</td>
                                                <td data-label="ID Card" class="text-muted">{{ $s['id_card_number'] ?? '—' }}</td>
                                                <td data-label="Submitted" class="text-muted" title="{{ $s['submitted_at'] }}">{{ $s['submitted_at']->diffForHumans() }}</td>
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
