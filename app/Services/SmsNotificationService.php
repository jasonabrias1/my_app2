<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsNotificationService
{
    private const API_URL = 'https://www.iprogsms.com/api/v1/sms_messages';

    /** @return array{success: bool, response: string} */
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

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post(self::API_URL, [
                    'api_token' => config('services.iprog_sms.token'),
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
            ? (string) $body['message']
            : $response->body();

        return ['success' => false, 'response' => $failure];
    }

    /** @return array{success: bool, response: string} */
    public function notifyRequestCompleted(string $requestorName, string $mobileNumber, string $documentType): array
    {
        $message = "Hi {$requestorName}, your {$documentType} at E-Serbisyo Homestead is complete and ready for pick up. Please bring a valid ID.\n\n"
            .'If someone else will claim it on your behalf, they must bring an authorization letter, a photocopy of your valid ID, and a photocopy of their valid ID.';

        return $this->send($mobileNumber, $message);
    }
}
