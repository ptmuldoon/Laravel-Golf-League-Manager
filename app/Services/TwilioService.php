<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected Client $client;
    protected string $fromNumber;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');

        if (!$sid || !$token || !$this->fromNumber) {
            throw new \Exception('Twilio credentials not configured. Set TWILIO_SID, TWILIO_AUTH_TOKEN, and TWILIO_FROM_NUMBER in .env');
        }

        $this->client = new Client($sid, $token);
    }

    /**
     * Send a single SMS message.
     */
    public function sendSms(string $to, string $body): string
    {
        $message = $this->client->messages->create(
            $to,
            [
                'from' => $this->fromNumber,
                'body' => $body,
            ]
        );

        return $message->sid;
    }

    /**
     * Send SMS to multiple recipients.
     * Returns array with 'sent' count and 'failed' array of [number => error].
     */
    public function sendBulkSms(array $recipients, string $body): array
    {
        $sent = 0;
        $failed = [];

        foreach ($recipients as $number) {
            try {
                $this->sendSms($number, $body);
                $sent++;
            } catch (\Exception $e) {
                $failed[$number] = $e->getMessage();
                Log::warning("SMS failed to {$number}: " . $e->getMessage());
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Format a phone number to E.164 format for US numbers.
     * Strips non-digits, prepends +1 if needed.
     * Returns null if the number is clearly invalid.
     */
    public static function formatPhoneNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }

        if (strlen($digits) >= 11) {
            return '+' . $digits;
        }

        return null;
    }
}
