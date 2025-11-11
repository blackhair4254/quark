<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShopeeAuthController extends Controller
{
    /**
     * GET /shopee/get-access-token?code=...&shop_id=123   (atau pakai main_account_id=...)
     * Mengembalikan response mentah dari Shopee (text/plain).
     */
    public function getAccessToken(Request $request)
    {
        // ==== Ambil dari .env (dengan fallback bila perlu) ====
        $host        = rtrim(env('SHOPEE_HOST', 'https://partner.sandbox.test-stable.shopee.sg'), '/');
        $partnerId   = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey  = env('SHOPEE_PARTNER_KEY');
        $defaultCode = env('SHOPEE_CODE');      // bisa diisi dari authorization redirect
        $defaultShop = env('SHOPEE_SHOP_ID');   // shop_id yang telah meng-authorize

        // ==== Param yang bisa dioverride lewat query ====
        $code   = (string) ($request->query('code', $defaultCode));
        $shopId = (int) ($request->query('shop_id', $defaultShop));

        if (!$partnerId || !$partnerKey || !$code || !$shopId) {
            return response()->json([
                'error'   => 'missing_param',
                'message' => 'Pastikan SHOPEE_PARTNER_ID, SHOPEE_PARTNER_KEY, SHOPEE_CODE, dan SHOPEE_SHOP_ID sudah terisi atau kirim via query ?code=...&shop_id=...'
            ], 400);
        }

        // ==== Siapkan common params ====
        $apiPath   = '/api/v2/auth/token/get';
        $timestamp = time(); // detik UTC; harus < 5 menit dari waktu server Shopee

        // base_string = partner_id + api_path + timestamp (Public API)
        $baseString = $partnerId . $apiPath . $timestamp;
        $sign       = hash_hmac('sha256', $baseString, $partnerKey); // hex lower-case

        // URL lengkap (common params di query)
        $url = $host . $apiPath
             . '?partner_id=' . $partnerId
             . '&sign=' . $sign
             . '&timestamp=' . $timestamp;

        // Body JSON sesuai dokumen
        $body = [
            'shop_id'    => $shopId,
            'code'       => $code,
            'partner_id' => $partnerId,
        ];

        // ==== Eksekusi ====
        $resp = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(20)
            ->post($url, $body);

        // Kembalikan response dari Shopee (apa adanya)
        return response($resp->body(), $resp->status())
                ->header('Content-Type', 'application/json; charset=utf-8');
    }
}
