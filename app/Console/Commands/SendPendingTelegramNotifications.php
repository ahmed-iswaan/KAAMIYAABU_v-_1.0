<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingTelegramNotification;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendPendingTelegramNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all pending Telegram notifications from the database table.';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): int
    {
        $this->info('Starting to send pending Telegram notifications...');

        // Fetch unsent notifications
        $pendingRecords = PendingTelegramNotification::where('is_sent', false)->get();

        if ($pendingRecords->isEmpty()) {
            $this->info('No pending Telegram notifications found.');
            return 0;
        }

        foreach ($pendingRecords as $notification) {
            try {
                $response = $telegramService->sendMessage(
                    $notification->chat_id,
                    $notification->message_thread_id,
                    $notification->message
                );

                if (isset($response['ok']) && $response['ok'] === true) {
                    // Mark as sent
                    $notification->update([
                        'is_sent'     => true,
                        'attempted_at'=> Carbon::now(),
                    ]);

                    $this->info("✅ Sent notification ID {$notification->id} to chat {$notification->chat_id} to message thread {$notification->message_thread_id}");
                } else {
                    // Log if Telegram returned “ok”: false
                    Log::error('Telegram API returned an error', [
                        'notification_id' => $notification->id,
                        'chat_id'         => $notification->chat_id,
                        'message_thread_id'         => $notification->message_thread_id,
                        'response'        => $response,
                    ]);

                    // Update attempted timestamp anyway
                    $notification->update([
                        'attempted_at' => Carbon::now(),
                    ]);
                }
            } catch (\Exception $e) {
                // Catch any exceptions (e.g. network errors)
                Log::error('Exception when sending Telegram notification', [
                    'notification_id' => $notification->id,
                    'chat_id'         => $notification->chat_id,
                    'message_thread_id'         => $notification->message_thread_id,
                    'error_message'   => $e->getMessage(),
                    'stack_trace'     => $e->getTraceAsString(),
                ]);

                // Update attempted timestamp so we don’t retry infinitely
                $notification->update([
                    'attempted_at' => Carbon::now(),
                ]);
            }
        }

        $this->info('Finished processing pending Telegram notifications.');
        return 0;
    }
}
