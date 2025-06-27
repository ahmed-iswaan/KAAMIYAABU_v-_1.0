<?php

namespace App\Services;

use GuzzleHttp\Client;

class TelegramService
{
    protected Client $http;
    protected string $botToken;
    protected string $baseUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->baseUrl  = "https://api.telegram.org/bot{$this->botToken}/";
        $this->http     = new Client();
    }

    /**
     * Send a simple text message to a given chat ID or username.
     *
     * @param  string|int  $chatId   Telegram chat ID (e.g. 123456789) or channel username (e.g. "@mychannel")
     * @param  string      $message  The text you want to send
     * @return array
     */
    public function sendMessage($chatId,$message_thread_id, string $message): array
    {
        $endpoint = $this->baseUrl . 'sendMessage';

        $response = $this->http->post($endpoint, [
            'json' => [
                'chat_id'    => $chatId,
                'message_thread_id'    => $message_thread_id,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
