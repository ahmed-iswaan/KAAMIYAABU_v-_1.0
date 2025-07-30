<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceSchedule;
use App\Models\Invoice;
use Carbon\Carbon;
use App\Models\EventLog;
use App\Models\PendingTelegramNotification;
use App\Services\DhiraaguSmsService;

class GenerateScheduledInvoices extends Command
{
    protected $signature = 'invoices:generate-scheduled';
    protected $description = 'Generate invoices based on invoice_schedules';

    public function handle()
    {
        $today = Carbon::today();

        $schedules = InvoiceSchedule::where('is_active', true)
            ->whereDate('next_invoice_date', '<=', $today)
            ->get();

        foreach ($schedules as $schedule) {
            $invoice = Invoice::create([
                'property_id'      => $schedule->property_id,
                'directories_id'   => $schedule->directories_id,
                'date'             => now(),
                'due_date'         => now()->addDays((int) $schedule->due_days),
                'status'           => \App\Enums\InvoiceStatus::PENDING,
                'invoice_tag'      => $schedule->invoice_tag,
                'ref_id' => $schedule->ref_id,
                'invoice_type'     => \App\Enums\InvoiceType::STANDARD,
                'fine_rate'        => $schedule->fine_rate,
                'fine_interval'    => $schedule->fine_interval,
                'fine_grace_period'=> $schedule->fine_grace_period,
            ]);

            // Create invoice lines from JSON
            foreach ($schedule->lines as $line) {
                $invoice->lines()->create([
                    'description' => $line['description'],
                    'quantity'    => $line['quantity'],
                    'unit_price'  => $line['unit_price'],
                ]);
            }
            $invoice->refresh(); 

            $mainDirectory = $invoice->directory;
            $brand = config('app.short_label');
            // Format the SMS content
            $smsText = "Dear valued customer,\n" .
                    "Your invoice for {$invoice->invoice_tag} is ready.\n\n" .
                    "Invoice No: {$invoice->number}\n" .
                    "Amount: MVR " . number_format($invoice->total_amount, 2) . "\n" .
                    "Due on: " . optional($invoice->due_date)->format('d M Y') . "\n\n" .
                    "- {$brand}";

            // Send SMS to main directory if phone is available
            if (!empty($mainDirectory->phone)) {
                app(DhiraaguSmsService::class)->queue($mainDirectory->phone, $smsText);
            }

            // Send SMS to active linked directories with phone numbers
            $linkedDirectories = $mainDirectory->linkedDirectories()
                ->where('status', 'active')
                ->get()
                ->pluck('linkedDirectory')
                ->filter(fn($dir) => !empty($dir->phone));

            foreach ($linkedDirectories as $linkedDir) {
                app(DhiraaguSmsService::class)->queue($linkedDir->phone, $smsText);
            }
            // Update schedule
            $schedule->generated_count += 1;

            if ($schedule->total_cycles && $schedule->generated_count >= $schedule->total_cycles) {
                $schedule->is_active = false;
            } else {
                $schedule->next_invoice_date = match ($schedule->recurrence) {
                    'daily'   => Carbon::parse($schedule->next_invoice_date)->addDay(),
                    'weekly'  => Carbon::parse($schedule->next_invoice_date)->addWeek(),
                    'monthly' => Carbon::parse($schedule->next_invoice_date)->addMonth(),
                    default   => null,
                };
            }

            $schedule->save();
            
            $envLabel = app()->environment('production') ? '🟢 Production' : '🧪 Development';

            $msg = "<b>📢 Invoice Created</b>\n" .
                "<i>{$envLabel} Environment</i>\n" .
                "──────────────────────────────\n" .
                "<b>🧾 Invoice No:</b> {$invoice->number}\n" .
                "<b>🏢 Property:</b> " . ($invoice->property->name ?? 'N/A') . "\n" .
                "<b>👤 Customer:</b> " . ($invoice->directory->name ?? 'N/A') . "\n" .
                "<b>📅 Due Date:</b> " . ($invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A') . "\n" .
                "<b>💰 Amount:</b> " . number_format($invoice->total_amount, 2) . " MVR\n" .
                "<b>📌 Status:</b> " . ($invoice->status ? $invoice->status->value : 'N/A') . "\n" .
                "──────────────────────────────\n" .
                "<b>👨‍💻 Created By:</b> System\n" .
                "<b>🕒 Created At:</b> " . now()->format('d M Y H:i');

                PendingTelegramNotification::create([
                    'chat_id' => env('TELEGRAM_GROUP_INVOICE'),
                    'message_thread_id' => env('TELEGRAM_TOPIC_INVOICE'),
                    'message' => $msg,
                ]);

                EventLog::create([
                    'user_id' => null,
                    'event_tab' => 'Invoices',
                    'event_entry_id' => $invoice->id,
                    'event_type' => 'Invoice Created',
                    'description' => 'Invoice created from schedule.',
                    'event_data' => [
                        'invoice_id' => $invoice->id,
                        'schedule_id' => $schedule->id,
                        'amount' => $invoice->total_amount,
                        'property' => optional($invoice->property)->name,
                        'directory' => optional($invoice->directory)->name,
                        'invoice_tag' => $invoice->invoice_tag,
                        'ref_id' => $invoice->ref_id,
                    ],
                    'ip_address' => null,
                ]);

            $this->info("Generated invoice {$invoice->id} for schedule {$schedule->id}");
        }
    }
}

