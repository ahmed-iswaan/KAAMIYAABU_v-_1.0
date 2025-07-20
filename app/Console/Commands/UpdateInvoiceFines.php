<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Enums\InvoiceStatus; // Import the InvoiceStatus enum
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Facades\DB; // Import DB facade for query debugging

class UpdateInvoiceFines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:update-fines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and persist accrued fines on all overdue and pending invoices';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting invoice fine update process...');
        // Log::info('Artisan command invoices:update-fines started.');

        $processedCount = 0;
        $errorCount = 0;

        // --- DEBUGGING SECTION ---
        // This section is now UNCOMMENTED by default to help diagnose why no invoices are found.
        // It logs the exact query, bindings, and enum/now() values.
        $debugQuery = Invoice::whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereIn('status', [InvoiceStatus::PENDING, InvoiceStatus::PARTIAL])
            ->with('lines'); // Include eager loads in debug query if relevant

        // Log::info('DEBUGGING QUERY:');
        // Log::info('SQL: ' . $debugQuery->toSql());
        // Log::info('Bindings: ' . json_encode($debugQuery->getBindings()));
        // Log::info('Now() value: ' . now()->toDateTimeString());
        // Log::info('InvoiceStatus::PENDING value: ' . InvoiceStatus::PENDING->value); // Access the raw value of the enum

        // You can also count the potential results directly for debugging:
        $count = $debugQuery->count();
        // Log::info("DEBUGGING: Found {$count} invoices matching criteria.");
        if ($count === 0) {
            $this->warn("DEBUGGING: No invoices found matching the current query criteria. Please check your due_date and status values in the database against the logged SQL and bindings.");
        }
        // --- END DEBUGGING SECTION ---


        // Query invoices that are overdue and have a 'pending' status.
        // Eager load the 'lines' relationship to ensure subtotal calculation is accurate
        // when the 'saved' event triggers during saveQuietly().
        Invoice::whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereIn('status', [InvoiceStatus::PENDING, InvoiceStatus::PARTIAL])
            ->with('lines') // Eager load the invoice lines
            ->chunkById(100, function ($invoices) use (&$processedCount, &$errorCount) {
                // If no invoices are found in a chunk, we'll just skip.
                if ($invoices->isEmpty()) {
                    // Log::info("No pending and overdue invoices found in this chunk.");
                    return;
                }

                // Iterate over each chunk of invoices
                foreach ($invoices as $invoice) {
                    // Log current values before attempting to save
                    // Log::info("Processing Invoice ID: {$invoice->id}");
                    // Log::info("  - Current DB total_amount: {$invoice->getRawOriginal('total_amount')}"); // Get raw value from DB
                    // Log::info("  - Calculated Subtotal: {$invoice->subtotal}");
                    // Log::info("  - Calculated Accrued Fine: {$invoice->accrued_fine}");
                    // Log::info("  - Calculated Total with Fine: {$invoice->total_with_fine}"); // This is the expected new total

                    try {
                        // The 'saved' hook on the Invoice model will automatically recompute
                        // 'total_amount' based on 'subtotal' and 'accrued_fine' whenever
                        // the model is saved.
                        // We use saveQuietly() to avoid triggering other model events
                        // that might not be necessary for this batch update, improving performance.
                        $invoice->save();
                        $processedCount++;
                        // After saveQuietly(), the total_amount in the model instance
                        // should reflect the updated value due to the 'saved' event.
                        // Log::info("Invoice ID: {$invoice->id} saved successfully. Total_amount in model after update: {$invoice->total_amount}");
                    } catch (\Exception $e) {
                        $errorCount++;
                        // Log the full exception for detailed debugging in storage/logs
                        // Log::error("Error saving Invoice ID: {$invoice->id}. Error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}");
                        // Also output a user-friendly error message to the console
                        $this->error("Error processing invoice {$invoice->id}: {$e->getMessage()}");
                    }
                }
            });

        // Log::info("Artisan command invoices:update-fines finished. Processed: {$processedCount}, Errors: {$errorCount}");
        // Inform the user that the operation has completed, including summary counts.
        $this->info("Invoice fines update completed. Processed {$processedCount} invoices with {$errorCount} errors at " . now());

        return 0; // Return 0 for success
    }
}
