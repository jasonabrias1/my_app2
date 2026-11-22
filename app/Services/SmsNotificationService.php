<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsNotificationService
{
    private const API_URL = 'https://www.iprogsms.com/api/v1/sms_messages';


    private function token(): string
    {
        $token = config('services.iprog_sms.token')
            ?: config('services.iprog.token')
            ?: env('IPROG_API_TOKEN');

        return trim((string) $token, " \t\n\r\0\x0B\"'");
    }


    public function send(string $number, string $message): array
    {
        $number = preg_replace('/[^0-9]/', '', trim($number)) ?? '';

        if (str_starts_with($number, '0')) {
            $number = '63' . substr($number, 1);
        } elseif (str_starts_with($number, '9') && strlen($number) === 10) {
            $number = '63' . $number;
        }

        if (strlen($number) !== 12) {
            return ['success' => false, 'response' => 'Invalid mobile number format.'];
        }

        $token = $this->token();

        if ($token === '') {
            return [
                'success' => false,
                'response' => 'SMS token is not configured. Set IPROG_API_TOKEN and run php artisan config:clear.',
            ];
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(5)
                ->timeout(10)
                ->retry(2, 300, throw: false)
                ->post(self::API_URL, [
                    'api_token' => $token,
                    'phone_number' => $number,
                    'message' => $message,
                ]);
        } catch (\Throwable $exception) {
            report($exception);

            return ['success' => false, 'response' => 'SMS provider request failed.'];
        }

        $body = $response->json();
        if (is_array($body) && isset($body['status']) && (string) $body['status'] === '200') {
            return ['success' => true, 'response' => $response->body()];
        }

        $failure = is_array($body) && isset($body['message'])
            ? (string) (is_array($body['message']) ? json_encode($body['message']) : $body['message'])
            : $response->body();

        return ['success' => false, 'response' => $failure];
    }


    public function notifyRequestCompleted(string $requestorName, string $mobileNumber, string $documentType): array
    {
        $message = "Hi {$requestorName}, your {$documentType} at E-Serbisyo Homestead is ready for pick up. "
            .'Please bring a valid ID. If someone else will claim it, bring an authorization letter and a copy of both IDs.';

        return $this->send($mobileNumber, $message);
    }
}