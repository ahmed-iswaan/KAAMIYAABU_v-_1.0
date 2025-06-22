<?php
// app/Services/DhiraaguSmsService.php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use App\Jobs\SendDhiraaguSmsJob;
use Exception;

class DhiraaguSmsService
{
    protected Client $http;
    protected string $userid;
    protected string $password;
    protected string $url;

    public function __construct(Client $http = null)
    {
        // pull credentials & URL from config/services.php
        $cfg = Config::get('services.dhiraagu_sms');
        $this->userid   = $cfg['userid']   ?? '';
        $this->password = $cfg['password'] ?? '';
        $this->url      = $cfg['url']      ?? 'https://bulkmessage.dhiraagu.com.mv/jsp/receiveSMS.jsp';

        if (! $this->userid || ! $this->password) {
            throw new Exception('Dhiraagu SMS credentials not configured.');
        }

        // allow injection for easier testing
        $this->http = $http ?? new Client;
    }

    /**
     * Send immediately (blocking).
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception if Dhiraagu responds with error
     */
    public function send(string $to, string $message): bool
    {
        // normalize Maldivian numbers
        if (in_array(strlen($to), [7, 10], true)) {
            if (strlen($to) === 7) {
                $to = '960'.$to;
            }
            if (strlen($to) === 10 && substr($to, 0, 3) !== '960') {
                throw new Exception("Invalid recipient format: {$to}");
            }
        } else {
            throw new Exception("Invalid recipient length: {$to}");
        }

        $response = $this->http->request('GET', $this->url, [
            'query' => [
                'userid'   => $this->userid,
                'password' => $this->password,
                'to'       => $to,
                'text'     => $message,
            ],
            'timeout' => 5,
        ]);

        $body   = trim((string) $response->getBody());
        $status = explode(':', $body, 2)[0];

        if (strcasecmp($status, 'Failed') === 0) {
            throw new Exception("Dhiraagu error response: {$body}");
        }

        return true;
    }

    /**
     * Queue the SMS for later sending.
     */
    public function queue(string $to, string $message): void
    {
        SendDhiraaguSmsJob::dispatch($to, $message);
    }
}
