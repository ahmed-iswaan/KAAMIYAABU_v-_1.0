<?php

namespace App\Livewire\Payment;

use App\Models\Payment;
use Livewire\Component;
use Livewire\WithPagination;

class Paymentmanager extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $pageTitle = 'Payment Manager';
     public $selectedPayment;

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $payments = Payment::with('directory')
            ->when($this->search, fn ($q) =>
                $q->where('number', 'like', "%{$this->search}%")
                  ->orWhere('ref', 'like', "%{$this->search}%")
                  ->orWhereHas('directory', fn ($q2) =>
                      $q2->where('name', 'like', "%{$this->search}%")
                  )
            )
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.payment.paymentmanager', [
            'payments' => $payments,
            'pageTitle' => 'Payment Manager',
        ])->layout('layouts.master');
    }

    public function viewPayment($paymentId)
    {
        $this->selectedPayment = Payment::with(['directory', 'invoices'])->findOrFail($paymentId);

        $this->dispatch('showPaymentModal');
    }
}
