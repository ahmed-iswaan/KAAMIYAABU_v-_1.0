<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WasteCollectionSchedule;
use App\Models\WasteCollectionTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\EventLog;
use App\Models\PendingTelegramNotification;


class GenerateWasteCollectionTasks extends Command
{
    protected $signature = 'waste:generate-tasks';
    protected $description = 'Generate Waste Collection Tasks from active schedules';

    public function handle()
    {
        $today = Carbon::today();

        $schedules = WasteCollectionSchedule::where('is_active', true)
            ->whereDate('next_collection_date', '<=', $today)
            ->get();

        foreach ($schedules as $schedule) {
            try {
                // Create new WasteCollectionTask
                $task = WasteCollectionTask::create([
                    'id'              => Str::uuid(),
                    'property_id'     => $schedule->property_id,
                    'directories_id'  => $schedule->directories_id,
                    'driver_id'       => $schedule->driver_id,
                    'vehicle_id'      => $schedule->vehicle_id,
                    'waste_management_register_id' => $schedule->waste_management_register_id,
                    'status'          => 'pending',
                    'scheduled_at'    => now(),
                    'waste_data'      => $schedule->waste_data,
                    'note'            => $schedule->note,
                ]);

                // Update schedule
                $schedule->generated_count += 1;

                // Stop schedule if it reaches its limit
                if ($schedule->total_cycles && $schedule->generated_count >= $schedule->total_cycles) {
                    $schedule->is_active = false;
                } else {
                    $schedule->next_collection_date = match ($schedule->recurrence) {
                        'daily'   => Carbon::parse($schedule->next_collection_date)->addDay(),
                        'weekly'  => Carbon::parse($schedule->next_collection_date)->addWeek(),
                        'monthly' => Carbon::parse($schedule->next_collection_date)->addMonth(),
                        default   => null,
                    };
                }

                $schedule->save();
                $this->info("✅ Task created from schedule: {$schedule->id}");

                                // Event log
                EventLog::create([
                    'user_id' => null,
                    'event_tab' => 'WasteCollectionTask',
                    'event_entry_id' => $schedule->id,
                    'event_type' => 'Waste Task Created',
                    'description' => 'Task generated successfully from schedule.',
                    'event_data' => [
                        'property_id'     => $schedule->property_id,
                        'directories_id'  => $schedule->directories_id,
                        'schedule_id' => $schedule->id,
                        'task_id' => $task->id,
                        'recurrence' => $schedule->recurrence,
                        'next_date' => $schedule->next_collection_date,
                        'waste_management_register_id' => $schedule->waste_management_register_id,
                    ],
                    'ip_address' => request()?->ip() ?? 'SYSTEM',
                ]);

                $register = $schedule->register;
                $property = $schedule->property;
                $directory = $schedule->directory;

                // Telegram success message
                    $msg =
                        "<b>🗑️ Waste Collection Task Generated</b>\n\n" .
                        "<b>Number #:</b> " . ($register->register_number ?? '-') . "\n" .
                        "<b>Property:</b> " . ($property->name ?? '-') . "\n" .
                        "<b>Owner:</b> " . ($directory->name ?? '-') . "\n" .
                        "<b>Scheduled At:</b> " . ($task->scheduled_at?->format('d M Y') ?? '-') . "\n" .
                        "<b>Next Collection:</b> " . ($schedule->next_collection_date?->format('d M Y') ?? '-') . "\n" .
                        "<b>Status:</b> Task created successfully.";


                PendingTelegramNotification::create([
                    'chat_id' => env('TELEGRAM_GROUP_WASTE_COLLECTION'),
                    'message_thread_id' => env('TELEGRAM_TOPIC_WASTE_COLLECTION'),
                    'message' => $msg,
                ]);

            } catch (\Exception $e) {
                \Log::error("❌ Failed to generate task for schedule {$schedule->id}: {$e->getMessage()}");
                $this->error("Error generating task for schedule {$schedule->id}");
            }
        }

        $this->info('✅ Waste collection task generation complete.');
        return Command::SUCCESS;
    }
}
