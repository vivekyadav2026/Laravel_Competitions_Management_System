<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Normalize Indian mobile number to 10 digits.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-digits
        $digits = preg_replace('/\D+/', '', $phone);

        // If starts with 91 and has 12 digits, strip 91
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        // If starts with 0 and has 11 digits, strip 0
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Validate 10-digit number starting with 6, 7, 8, or 9
        if (preg_match('/^[6-9]\d{9}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    /**
     * Send OTP SMS using HSP SMS Gateway.
     *
     * @param string $mobile 10-digit mobile number
     * @param string $otp 4 or 6 digit OTP
     * @return array ['success' => bool, 'message' => string, 'response' => mixed]
     */
    public static function sendOtp(string $mobile, string $otp): array
    {
        $cleanPhone = self::normalizePhone($mobile);

        if (!$cleanPhone) {
            return [
                'success' => false,
                'message' => 'Invalid mobile number. Please enter a valid 10-digit Indian mobile number.',
            ];
        }

        $apiUrl = config('services.hspsms.api_url', 'http://sms.hspsms.com/sendSMS');
        $username = config('services.hspsms.username', 'fsclub');
        $apiKey = config('services.hspsms.apikey', 'f85eb1ba-485e-44c6-9002-8f7948abb803');
        $senderName = config('services.hspsms.sendername', 'DPCORN');
        $smsType = config('services.hspsms.smstype', 'TRANS');
        $template = config('services.hspsms.message_template', '{otp} is Your One Time Password(OTP) for DEEP CORNER.');

        // Build message from template
        $message = str_replace('{otp}', $otp, $template);

        try {
            $response = Http::timeout(12)->get($apiUrl, [
                'username' => $username,
                'message' => $message,
                'sendername' => $senderName,
                'smstype' => $smsType,
                'numbers' => $cleanPhone,
                'apikey' => $apiKey,
            ]);

            $body = $response->body();
            Log::info("HSP SMS Gateway Dispatch for {$cleanPhone}: Status={$response->status()}, Body={$body}");

            $json = $response->json();
            $isSuccess = false;
            $msgId = null;

            if (is_array($json)) {
                foreach ($json as $item) {
                    if (is_array($item)) {
                        if (isset($item['responseCode']) && stripos($item['responseCode'], 'Success') !== false) {
                            $isSuccess = true;
                        }
                        if (isset($item['msgid'])) {
                            $msgId = $item['msgid'];
                        }
                    }
                }
            } elseif (stripos($body, 'Success') !== false) {
                $isSuccess = true;
            }

            if ($response->successful() && $isSuccess) {
                return [
                    'success' => true,
                    'message' => 'OTP dispatched successfully.',
                    'msgid' => $msgId,
                    'phone' => $cleanPhone,
                ];
            }

            Log::warning("HSP SMS response did not indicate success: {$body}");
            return [
                'success' => false,
                'message' => 'SMS gateway returned an error. Please try again in a moment.',
                'raw' => $body,
            ];
        } catch (Exception $e) {
            Log::error("HSP SMS Gateway Exception for {$cleanPhone}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to send SMS right now. Please check your internet connection or try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
