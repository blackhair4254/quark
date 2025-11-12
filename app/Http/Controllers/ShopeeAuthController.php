<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopeeApiv2Token;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// {"refresh_token":"48414b536c4f6e497872597767696158","access_token":"586b78775763774b624c514756704651","expire_in":14362,"request_id":"e3e3e7f3434ba8534267df60f6e5d800","merchant_id_list":[],"shop_id_list":[141189773],"supplier_id_list":[],"user_id_list":[],"error":"","message":""}
class ShopeeAuthController extends Controller
{
    /**
     * GET /shopee/get-access-token?code=...&shop_id=123   (atau pakai main_account_id=...)
     * Mengembalikan response mentah dari Shopee (text/plain).
     */
    public function getAccessToken(Request $request)
    {
        // ==== Ambil dari .env (dengan fallback bila perlu) ====
        $host        = rtrim(env('SHOPEE_HOST'));
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
    

    public function refreshAccessToken(Request $request)
    {
        // ==== Konfigurasi dari .env ====
        $host       = rtrim(env('SHOPEE_HOST'), '/');  // contoh: https://partner.test-stable.shopee.sg
        $partnerId  = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey = env('SHOPEE_PARTNER_KEY');
        $defaultShopId = (int) env('SHOPEE_SHOP_ID');

        // ==== Identitas chain untuk lookup token ====
        // bisa override via ?chain_link=... ; default pakai pola shop:{SHOP_ID}
        $shopId    = (int) $request->query('shop_id', $defaultShopId);
        $chainLink = (string) $request->query('chain_link', "shop:{$shopId}");

        // ==== Ambil record token dari DB (atau create kosong) ====
        /** @var ShopeeApiv2Token $row */
        $row = ShopeeApiv2Token::firstOrCreate(
            ['chain_link' => $chainLink],
            ['updated_at' => now()]
        );

        // ==== Refresh token: pakai prioritas input -> DB ====
        $refreshToken = (string) $request->input('refresh_token', $request->query('refresh_token', ''));
        if ($refreshToken === '') {
            $refreshToken = (string) ($row->refresh_token ?? '');
        }

        // (opsional untuk merchant) ?merchant_id=...
        $merchantId = $request->input('merchant_id', $request->query('merchant_id'));

        // Validasi param wajib
        if (!$partnerId || !$partnerKey || !$refreshToken || (!$shopId && !$merchantId)) {
            return response()->json([
                'error'   => 'missing_param',
                'message' => 'Wajib: SHOPEE_PARTNER_ID, SHOPEE_PARTNER_KEY, refresh_token (dari DB/param), dan (shop_id ATAU merchant_id).',
            ], 400);
        }

        // ==== Endpoint & signature (Public API) ====
        $apiPath   = '/api/v2/auth/access_token/get';
        $timestamp = time(); // UTC detik; window 5 menit

        // base_string = partner_id + api_path + timestamp
        $baseString = $partnerId . $apiPath . $timestamp;
        $sign       = hash_hmac('sha256', $baseString, $partnerKey);

        $url = $host . $apiPath
            . '?partner_id=' . $partnerId
            . '&sign=' . $sign
            . '&timestamp=' . $timestamp;

        // Body JSON (pilih salah satu: shop_id atau merchant_id)
        $body = [
            'partner_id'    => $partnerId,
            'refresh_token' => $refreshToken,
        ];
        if (!empty($merchantId)) {
            $body['merchant_id'] = (int) $merchantId;
        } else {
            $body['shop_id'] = (int) $shopId;
        }

        // ==== Eksekusi HTTP ====
        $resp = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(25)
                    ->post($url, $body);

        $status = $resp->status();
        $json   = $resp->json();

        // Antisipasi bila bukan JSON
        if (!is_array($json)) {
            return response()->json([
                'error'   => 'invalid_response',
                'message' => 'Shopee mengembalikan respons non-JSON',
                'raw'     => $resp->body(),
            ], $status ?: 500);
        }

        // ==== Persist ke DB ====
        // Shopee sukses: "error" = "" (string kosong). Gagal: ada kode error.
        $error      = $json['error']   ?? null;
        $message    = $json['message'] ?? null;
        $requestId  = $json['request_id'] ?? null;
        $newAccess  = $json['access_token']  ?? null;
        $newRefresh = $json['refresh_token'] ?? null;
        $expireIn   = $json['expire_in']     ?? null; // detik

        // Hitung access_expires_at bila ada expire_in
        $accessExpiresAt = null;
        if (is_numeric($expireIn)) {
            $accessExpiresAt = now()->addSeconds((int) $expireIn);
        }

        // Simpan atomik
        DB::transaction(function () use (
            $row, $newAccess, $newRefresh, $expireIn, $requestId, $error, $message, $accessExpiresAt
        ) {
            // update kolom sukses kalau ada
            if ($newAccess !== null)      $row->access_token = $newAccess;
            if ($newRefresh !== null)     $row->refresh_token = $newRefresh;
            if ($expireIn !== null)       $row->expire_in = (int) $expireIn;
            if ($accessExpiresAt !== null)$row->access_expires_at = $accessExpiresAt;

            // selalu catat jejak
            $row->request_id = $requestId;
            $row->error      = $error;
            $row->message    = $message;
            $row->updated_at = now();

            $row->save();
        });

        // ==== Balikkan info ringkas + row terbaru ====
        return response()->json([
            'http_status' => $status,
            'saved' => [
                'chain_link'         => $row->chain_link,
                'access_token'       => $row->access_token,
                'refresh_token'      => $row->refresh_token,
                'expire_in'          => $row->expire_in,
                'access_expires_at'  => optional($row->access_expires_at)->toIso8601String(),
                'request_id'         => $row->request_id,
                'error'              => $row->error,
                'message'            => $row->message,
                'updated_at'         => $row->updated_at?->toIso8601String(),
            ],
            'raw_response' => $json,
        ], $status);
    }

}
