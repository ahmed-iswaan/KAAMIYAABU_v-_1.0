<?php

namespace App\Livewire\Payment;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\EventLog;
use App\Enums\InvoiceStatus;
use Illuminate\Support\Facades\DB;

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
        $this->dispatch('TableUpdated'); 
    }

    public function cancelPaymentConfirmed($data)
    {
        $paymentId = $data['id'];
        $reason    = $data['reason'] ?? 'No reason provided';

        
        if (!$paymentId) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => 'No payment ID provided.',
                'icon' => 'error'
            ]);
            return;
        }

        $this->cancelPayment($paymentId, $reason); // Call your cancel logic
    }

    public function cancelPayment(string $paymentId,string $reason)
    {
        $payment = Payment::with('invoices')->findOrFail($paymentId);

        if ($payment->status === 'Cancelled') {
            $this->dispatch('swal', [
                'title' => 'Already Cancelled',
                'text' => 'This payment is already cancelled.',
                'icon' => 'info',
            ]);
            return;
        }

        DB::transaction(function () use ($payment, $reason) {
            // Revert applied amounts from invoices
            foreach ($payment->invoices as $invoice) {
                $appliedAmount = $invoice->pivot->applied_amount;

                $invoice->paid_amount -= $appliedAmount;
                if ($invoice->paid_amount <= 0) {
                    $invoice->paid_amount = 0;
                    $invoice->status = InvoiceStatus::PENDING;
                } else {
                    $invoice->status = InvoiceStatus::PARTIAL;
                }
                $invoice->save();
            }

            // Restore credit balance (if used/overpaid)
            $directory = $payment->directory;
            if ($directory) {
                $directory->credit_balance = 
                    ($directory->credit_balance + $payment->credit_used) - $payment->overpaid_amount;
                $directory->credit_balance = max(0, $directory->credit_balance);
                $directory->save();
            }

            // Update payment status
           $payment->update([
            'status'      => 'Cancelled',
            'cancel_note' => $reason
        ]);


            // Log event
            EventLog::create([
                'user_id'        => auth()->id(),
                'event_tab'      => 'Payments',
                'event_entry_id' => $payment->id,
                'event_type'     => 'Payment Cancelled',
                'description'    => "Payment of {$payment->amount} MVR cancelled.",
                'event_data'     => [
                    'payment_id' => $payment->id,
                    'amount'     => $payment->amount,
                    'payer_id'   => $payment->directories_id,
                ],
                'ip_address'     => request()->ip(),
            ]);
        });

        $this->dispatch('swal', [
            'title' => 'Payment Cancelled',
            'text' => 'Payment has been successfully cancelled and rolled back.',
            'icon' => 'success',
            'confirmButtonText' => 'Ok!',
            'confirmButton'=> 'btn btn-primary'
        ]);

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
