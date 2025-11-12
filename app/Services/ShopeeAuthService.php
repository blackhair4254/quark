<?php

namespace App\Services;

use App\Models\ShopeeApiv2Token;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopeeAuthService
{
    public function refreshForShop(int $shopId, ?string $overrideRefreshToken = null): array
    {
        $host       = rtrim(env('SHOPEE_HOST'), '/');
        $partnerId  = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey = env('SHOPEE_PARTNER_KEY');

        // Ambil record token
        $row = ShopeeApiv2Token::where('shop_id', $shopId)
            ->orderByDesc('updated_at')
            ->first();

        if (!$row) {
            // fallback chain_link lama
            $row = ShopeeApiv2Token::where('chain_link', "shop:{$shopId}")
                ->orderByDesc('updated_at')
                ->first();
        }

        $refreshToken = $overrideRefreshToken ?: ($row->refresh_token ?? null);

        if (!$partnerId || !$partnerKey || !$refreshToken) {
            return ['ok' => false, 'status' => 400, 'json' => [
                'error' => 'missing_param',
                'message' => 'partner_id/partner_key/refresh_token tidak lengkap',
            ]];
        }

        $apiPath   = '/api/v2/auth/access_token/get';
        $timestamp = time();
        $baseString = $partnerId . $apiPath . $timestamp;
        $sign       = hash_hmac('sha256', $baseString, $partnerKey);

        $url = $host . $apiPath
            . '?partner_id=' . $partnerId
            . '&sign=' . $sign
            . '&timestamp=' . $timestamp;

        $body = [
            'partner_id'    => $partnerId,
            'shop_id'       => $shopId,
            'refresh_token' => $refreshToken,
        ];

        $resp   = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(25)->post($url, $body);
        $status = $resp->status();
        $json   = $resp->json();

        if (!is_array($json)) {
            return ['ok' => false, 'status' => $status ?: 500, 'json' => [
                'error' => 'invalid_response',
                'message' => 'Shopee mengembalikan non-JSON',
                'raw' => $resp->body(),
            ]];
        }

        // Ambil field
        $error      = $json['error']        ?? null;
        $message    = $json['message']      ?? null;
        $requestId  = $json['request_id']   ?? null;
        $newAccess  = $json['access_token'] ?? null;
        $newRefresh = $json['refresh_token']?? null;
        $expireIn   = $json['expire_in']    ?? null;

        $accessExpiresAt = is_numeric($expireIn) ? now()->addSeconds((int) $expireIn) : null;

        // Pastikan row ada
        if (!$row) {
            $row = new ShopeeApiv2Token();
            $row->chain_link = "shop:{$shopId}";
            $row->shop_id    = $shopId;
        }
        if (Schema::hasColumn('shopee_apiv2_tokens', 'partner_id')) {
            $row->partner_id = $partnerId;
        }
        if (Schema::hasColumn('shopee_apiv2_tokens', 'shop_id')) {
            $row->shop_id = $shopId;
        }

        DB::transaction(function () use ($row, $newAccess, $newRefresh, $expireIn, $requestId, $error, $message, $accessExpiresAt) {
            if ($newAccess !== null)       $row->access_token      = $newAccess;
            if ($newRefresh !== null)      $row->refresh_token     = $newRefresh;
            if ($expireIn !== null)        $row->expire_in         = (int) $expireIn;
            if ($accessExpiresAt !== null) $row->access_expires_at = $accessExpiresAt;

            $row->request_id = $requestId;
            $row->error      = $error;
            $row->message    = $message;
            $row->updated_at = now();
            $row->save();
        });

        return ['ok' => ($error ?? '') === '', 'status' => $status, 'json' => $json, 'row' => $row];
    }
}
