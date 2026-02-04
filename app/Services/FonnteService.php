<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FonnteService
{
    public static function send($target, $message)
    {
        $verifySsl = env('FONNTE_VERIFY_SSL', true);
        try {
            $response = Http::withOptions(['verify' => $verifySsl])
                ->withHeaders([
                    'Authorization' => env('FONNTE_TOKEN'),
                ])->post('https://api.fonnte.com/send', [
                        'target' => $target,
                        'message' => $message,
                        'countryCode' => '62',
                    ]);

            return $response->json();

        } catch (\Exception $e) {
            return ['status' => false, 'reason' => $e->getMessage()];
        }
    }
}