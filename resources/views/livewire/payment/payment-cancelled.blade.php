<td class="text-end">
    @if($payment->status !== 'Cancelled')
        <button 
            class="btn btn-danger btn-sm" 
            wire:click="cancelPayment('{{ $payment->id }}')"
            onclick="return confirm('Are you sure you want to cancel this payment?');">
            Cancel
        </button>
    @else
        <span class="badge bg-danger">Cancelled</span>
    @endif
</td>
