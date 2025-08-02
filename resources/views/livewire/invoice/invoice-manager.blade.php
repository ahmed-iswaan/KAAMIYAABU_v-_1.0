<div>
    {{-- begin::Content --}}
    <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">

        {{-- begin::Toolbar --}}
        <div class="toolbar" id="kt_toolbar">
            <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
                {{-- Info --}}
                <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
                    <h1 class="text-dark fw-bold my-1 fs-2">{{ $pageTitle }}</h1>

                    <ul class="breadcrumb fw-semibold fs-base my-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="/" class="text-muted text-hover-primary">Finace</a>
                        </li>
                        <li class="breadcrumb-item text-dark">Invoice</li>
                    </ul>
                </div>
                {{-- Actions (if any) --}}
                <div class="d-flex align-items-center flex-nowrap text-nowrap py-1">
                    @if($selectedInvoices)
                    <button class="btn btn-success me-4" wire:click="makePayment">
                        Pay {{ count($selectedInvoices) }} Invoice{{ count($selectedInvoices) > 1 ? 's' : '' }}
                    </button>
                    @endif
                    <button class="btn btn-primary " wire:click="showCreateModal">
                        <i class="ki-duotone ki-plus fs-2"></i> New Invoice
                    </button>
                </div>
            </div>
        </div>
        {{-- end::Toolbar --}}

        {{-- begin::Post --}}
        <div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
            <div class="container-xxl">

                <div class="d-flex flex-column flex-lg-row">

                    {{-- begin::Sidebar --}}
                    <div class="flex-column flex-md-row-auto w-100 w-lg-250px w-xxl-275px">
                        <div class="card mb-6 mb-lg-0">
                            <div class="card-header px-6" id="kt_sidebar_header">
                                <div class="card-title m-0">
                                    <div class="d-flex align-items-center position-relative my-1">
                                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <input
                                            type="text"
                                            wire:model.live.debounce.500ms="search"
                                            class="form-control form-control-solid w-100 ps-13"
                                            placeholder="Search" />
                                    </div>
                                </div>
                            </div>
                            <div class="card-body py-7 px-5">
                                <div class="scroll-y pe-3 me-n3"
                                     data-kt-scroll="true"
                                     data-kt-scroll-height="{default: '300px', lg: 'auto'}"
                                     data-kt-scroll-dependencies="#kt_header, #kt_subheader, #kt_footer, #kt_sidebar_header"
                                     data-kt-scroll-offset="200px"
                                     style="height: 518px;">
                                    <ul class="nav nav-flush menu menu-column menu-rounded
                                             menu-title-gray-600 menu-bullet-gray-300
                                             menu-state-bg menu-state-bullet-primary
                                             fw-semibold fs-6">
                                      @foreach($invoices as $inv)
                                            <li class="menu-item pt-0 pb-1">
                                                <div class="d-flex align-items-start px-5 py-4 nav-link {{ $selectedInvoiceId === $inv->id ? 'bg-light-primary' : '' }}">
                                                    {{-- ✅ Payment checkbox --}}
                                                    @if ($inv->status === \App\Enums\InvoiceStatus::PAID) 
                                                    <div class="form-check form-check-custom form-check-success form-check-solid">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input me-3"
                                                            id="kt_check_indeterminate_1" checked
                                                            @if ($inv->status === \App\Enums\InvoiceStatus::PAID) disabled @endif
                                                             />
                                                    </div>
                                                    @else
                                                    <div class="form-check form-check-custom form-check-solid mb-5">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input me-3"
                                                            wire:model.live="selectedInvoices"
                                                            value="{{ $inv->id }}"
                                                            @if ($inv->status === \App\Enums\InvoiceStatus::PAID) disabled @endif
                                                             />
                                                    </div>
                                                    @endif


                                                    {{-- ✅ Clickable invoice info --}}
                                                    <a href="#"
                                                    wire:click.prevent="selectInvoice('{{ $inv->id }}')"
                                                    class="d-flex flex-column text-start text-gray-800 text-hover-primary flex-grow-1">
                                                        <span class="fw-bold">{{ $inv->directory->name }}</span>
                                                        <span class="fs-8 text-gray-500">{{ $inv->property->name }}</span>
                                                        <span class="fs-8 text-gray-500">Invoice #: {{ $inv->number }}</span>
                                                        <span class="fs-8 text-gray-500">
                                                        @if($inv)
                                                                @switch($inv->status)
                                                                    @case(App\Enums\InvoiceStatus::PENDING)
                                                                        <div class="badge badge-light-warning">Pending</div>
                                                                        @break

                                                                    @case(App\Enums\InvoiceStatus::PAID)
                                                                        <div class="badge badge-light-success">Paid</div>
                                                                        @break

                                                                    @case(App\Enums\InvoiceStatus::CANCELLED)
                                                                        <div class="badge badge-light-danger">Cancelled</div>
                                                                        @break

                                                                    @default
                                                                        <div class="badge badge-secondary">
                                                                            {{ ucfirst($inv->status->value) }}
                                                                        </div>
                                                                @endswitch
                                                        @endif
                                                        </span>
                                                    </a>
                                                </div>
                                            </li>
                                        @endforeach

                                  

                                           {{-- Load More button --}}
                                        @if($invoices->hasMorePages())
                                            <li class="mt-4 px-5 text-center w-100">
                                                <button wire:click="loadMore" class="btn btn-sm btn-light">Load More</button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- end::Sidebar --}}

                    {{-- begin::Details --}}
                    <div class="flex-md-row-fluid ms-lg-12">
                        <div class="card h-lg-100">
                            <div class="card-header">
                                <div class="card-title flex-column">
                                    <h3 class="fw-bold m-0">
                                    {{ $selectedInvoice 
                                        ? "# {$selectedInvoice->number}" 
                                        : 'Select an invoice' }}
                                    </h3>
                                    @if($selectedInvoice)
                                        <span class="fs-6 fw-semibold text-muted">
                                            Date: {{ $selectedInvoice->date->format('M d, Y') }}
                                            | Due Date: {{ $selectedInvoice->due_date?->format('M d, Y') ?: '—' }}
                                        </span>
                                    @endif
                                </div>
                                @php
                                    use App\Enums\InvoiceStatus;
                                @endphp

                                <div class="card-toolbar">
                                @if($selectedInvoice)
                                    @switch($selectedInvoice->status)
                                        @case(App\Enums\InvoiceStatus::PENDING)
                                            <div class="badge badge-light-warning fs-4">Pending</div>
                                            @break

                                        @case(App\Enums\InvoiceStatus::PAID)
                                            <div class="badge badge-light-success fs-4">Paid</div>
                                            @break

                                        @case(App\Enums\InvoiceStatus::CANCELLED)
                                            <div class="badge badge-light-danger fs-4">Cancelled</div>
                                            @break

                                        @default
                                            <div class="badge badge-secondary fs-4">
                                                {{ ucfirst($selectedInvoice->status->value) }}
                                            </div>
                                    @endswitch
                                @endif
                            </div>


                            </div>

                            <div class="card-body">
                                @if(! $selectedInvoice)
                                    <p class="text-center text-gray-500">Select an invoice from the sidebar.</p>
                                @else
                                    {{-- Customer / Directory --}}
                              @if($selectedInvoice->fine_detail != 'No fine accrued')
                                  <div class="alert alert-dismissible bg-light-danger d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <!--begin::Icon-->
                                    <i class="ki-duotone ki-information-5 fs-2hx text-danger me-4 mb-5 mb-sm-0">
                                      <span class="path1"></span>
                                      <span class="path2"></span>
                                      <span class="path3"></span>
                                    </i> 
                                    <!--end::Icon-->
                                  
                                    <!--begin::Content-->
                                    <div class="d-flex flex-column pe-0 pe-sm-10">
                                        <h4 class="fw-semibold">Invoice is Due</h4>
                                        <span>{{ $selectedInvoice->fine_detail }}</span>
                                    </div>
                                    <!--end::Content-->
                                
                                    <!--begin::Close-->
                                    <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                                        <i class="ki-duotone ki-cross fs-1 text-danger"><span class="path1"></span><span class="path2"></span></i>                    </button>
                                    <!--end::Close-->
                                </div>
                              @endif
                                    <div class="row mb-8">
                                        <div class="col-lg-6">
                                            <div class="symbol symbol-100px mb-7 me-9">
                                                {{-- static or dynamic logo --}}
                                                <img src="{{ asset('assets/media/logo.svg') }}" alt="image">
                                            </div>
                                            <div class="d-flex flex-column py-1">
                                                <h3 class="fw-bold fs-3 mb-1">{{ $selectedInvoice->directory->name }}</h3>
                                                <div class="text-gray-600 fw-semibold fs-4 mb-4">
                                                    {{ $selectedInvoice->property->name }}<br>
                                                    {{ $selectedInvoice->directory->city }} {{ $selectedInvoice->directory->state }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 fs-4 fw-semibold text-end text-gray-600">
                                            {{ $selectedInvoice->directory->email }}<br>
                                            Tax ID# {{ $selectedInvoice->directory->tax_id }}
                                        </div>
                                    </div>

                                    {{-- Invoice Details --}}

                   

                            {{-- Line-items --}}
                            <h2 class="fw-bold fs-3 mb-3">Billing Statement</h2>
                            <div class="table-responsive mb-10">
                                <table class="table g-2 gs-0 align-middle fw-semibold">
                                    <tbody>
                                        @foreach($selectedInvoice->lines as $line)
                                            <tr>
                                                <th class="min-w-250px fw-semibold text-gray-700 text-start">
                                                    {{ $line->description }}
                                                </th>
                                                <th class="min-w-150px fw-semibold text-muted text-start">
                                                    {{ $line->category->name ?? '—' }}
                                                </th>
                                                <th class="min-w-90px fw-bold text-end fs-4">
                                                    MVR {{ number_format($line->quantity * $line->unit_price,2) }}
                                                </th>
                                            </tr>
                                        @endforeach

                                        {{-- Subtotal --}}
                                        <tr>
                                            <th></th>
                                            <th class="text-gray-600 text-end">Subtotal</th>
                                            <th class="text-end fw-bold">
                                                MVR {{ number_format($selectedInvoice->subtotal, 2) }}
                                            </th>
                                        </tr>

                                        {{-- Discount --}}
                                        @if($selectedInvoice->discount > 0)
                                            <tr>
                                                <th></th>
                                                <th class="text-gray-600 text-end">Discount</th>
                                                <th class="fw-bold text-end text-success">
                                                    − MVR {{ number_format($selectedInvoice->discount, 2) }}
                                                </th>
                                            </tr>
                                        @endif

                                        {{-- VAT --}}
                                        @if(isset($selectedInvoice->vat_amount))
                                            <tr>
                                                <th></th>
                                                <th class="text-gray-600 text-end">VAT</th>
                                                <th class="fw-bold text-end">
                                                    MVR {{ number_format($selectedInvoice->vat_amount,2) }}
                                                </th>
                                            </tr>
                                        @endif

                                        {{-- Fine --}}
                                        @if($selectedInvoice->accrued_fine > 0)
                                            <tr>
                                                <th></th>
                                                <th class="text-danger text-end">Fine</th>
                                                <th class="fw-bold text-danger text-end">
                                                    MVR {{ number_format($selectedInvoice->accrued_fine,2) }}
                                                </th>
                                            </tr>
                                        @endif

                                        {{-- Total --}}
                                        <tr>
                                            <th></th>
                                            <th class="text-gray-600 text-end">Total</th>
                                            <th class="fw-bold text-end">
                                                MVR {{ number_format($selectedInvoice->total_amount,2) }}
                                            </th>
                                        </tr>

                                        {{-- Paid --}}
                                        <tr>
                                            <th></th>
                                            <th class="text-gray-600 text-end">Paid Amount</th>
                                            <th class="fw-bold text-end text-success">
                                                MVR {{ number_format($selectedInvoice->paid_amount,2) }}
                                            </th>
                                        </tr>

                                        {{-- Balance Due --}}
                                        <tr>
                                            <th></th>
                                            <th class="text-gray-600 text-end">Balance Due</th>
                                            <th class="fw-bold text-end text-warning">
                                                MVR {{ number_format($selectedInvoice->balance_due,2) }}
                                            </th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>


                            <hr>
                                <h3 class="fw-bold mb-2">Payments</h3>

                                <div class="d-flex flex-wrap">
                                    @forelse($selectedInvoice->payments as $payment)

                                                                                       @php
                                        $badgeClass = match(strtolower($payment->status)) {
                                            'approved' => 'success',
                                            'pending'  => 'warning',
                                            'cancelled' => 'danger',
                                            default    => 'secondary',
                                        };
                                    @endphp
										<!--begin::Col-->
										<div class="border border-dashed border-gray-300 rounded my-3 p-4 me-6">
											<span class="fs-2 fw-bold text-gray-800 lh-1">
											<span data-kt-countup="true" data-kt-countup-value="MVR {{ number_format($payment->pivot->applied_amount, 2) }}" data-kt-countup-prefix="MVR" class="counted" data-kt-initialized="1">MVR {{ number_format($payment->pivot->applied_amount, 2) }}                  <span class="badge badge-{{ $badgeClass }}">
                                        {{ ucfirst($payment->status) }}
                                    </span></span>
											</span>
											<span class="fs-6 fw-semibold text-gray-400 d-block lh-1 pt-2">{{ $payment->number }} | {{ $payment->created_at->format('M d, Y') }}</span>
										</div>
										<!--end::Col-->
                                        @empty
                                        Payment #: —
                                        @endforelse
									</div>
                            <hr>
                            {{-- Message to Customer --}}
                            @if($selectedInvoice->message_to_customer)
                                <div class="alert bg-light-info p-5 mb-10">
                                    <h5 class="fw-bold mb-2">Message to Customer</h5>
                                    <p class="mb-0 text-gray-700">{{ $selectedInvoice->message_to_customer }}</p>
                                </div>
                            @endif

                            {{-- Message on Statement --}}
                            @if($selectedInvoice->message_on_statement)
                                <div class="alert bg-light-warning p-5 mb-10">
                                    <h5 class="fw-bold mb-2">Statement Note</h5>
                                    <p class="mb-0 text-gray-700">{{ $selectedInvoice->message_on_statement }}</p>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="d-flex justify-content-end">
                                <a href="#" class="btn btn-light btn-active-light-primary fw-bold me-3">
                                    Print
                                </a>
                            </div>

                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- end::Details --}}
                </div>

            </div>
        </div>
        {{-- end::Post --}}
    </div>
    {{-- end::Content --}}

    @include('livewire.invoice.create-modal')
    @include('livewire.invoice.pay-modal')

</div>
