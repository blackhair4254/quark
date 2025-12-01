<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopeeApiv2Token;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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
        $host         = rtrim(env('SHOPEE_HOST'), '/');
        $partnerId    = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey   = env('SHOPEE_PARTNER_KEY');
        $envShopId    = (int) env('SHOPEE_SHOP_ID');

        // ==== Target shop ====
        // Prioritas: query ?shop_id -> env SHOPEE_SHOP_ID
        $shopId = (int) $request->query('shop_id', $envShopId);

        // (opsional untuk merchant) ?merchant_id=...
        $merchantId = $request->input('merchant_id', $request->query('merchant_id'));

        // ==== Ambil token record dari DB ====
        // 1) coba by shop_id (kolom yang baru kamu tambahkan)
        /** @var \App\Models\ShopeeApiv2Token|null $row */
        $row = ShopeeApiv2Token::where('shop_id', $shopId)
            ->orderByDesc('updated_at')
            ->first();

        // 2) kalau belum ada, coba by chain_link (fallback lama)
        if (!$row) {
            $chainLink = (string) $request->query('chain_link', "shop:{$shopId}");
            $row = \App\Models\ShopeeApiv2Token::where('chain_link', $chainLink)
                ->orderByDesc('updated_at')
                ->first();
        }

        // ==== Refresh token: prioritas input -> DB ====
        $refreshToken = (string) $request->input('refresh_token', $request->query('refresh_token', ''));
        if ($refreshToken === '') {
            $refreshToken = (string) optional($row)->refresh_token;
        }

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

        if (!is_array($json)) {
            return response()->json([
                'error'   => 'invalid_response',
                'message' => 'Shopee mengembalikan respons non-JSON',
                'raw'     => $resp->body(),
            ], $status ?: 500);
        }

        // ==== Ambil field respons ====
        $error      = $json['error']       ?? null;
        $message    = $json['message']     ?? null;
        $requestId  = $json['request_id']  ?? null;
        $newAccess  = $json['access_token']  ?? null;
        $newRefresh = $json['refresh_token'] ?? null;
        $expireIn   = $json['expire_in']     ?? null; // detik

        $accessExpiresAt = is_numeric($expireIn) ? now()->addSeconds((int) $expireIn) : null;

        // ==== Siapkan row jika belum ada sama sekali ====
        if (!$row) {
            $row = new \App\Models\ShopeeApiv2Token();
            $row->chain_link = "shop:{$shopId}";
            $row->shop_id    = $shopId;
        }
        // (opsional) simpan partner_id & shop_id jika kolom tersedia
        if (Schema::hasColumn('shopee_apiv2_tokens', 'partner_id')) {
            $row->partner_id = $partnerId;
        }
        if (Schema::hasColumn('shopee_apiv2_tokens', 'shop_id')) {
            $row->shop_id = $shopId;
        }


        // ==== Persist ke DB ====
        DB::transaction(function () use ($row, $newAccess, $newRefresh, $expireIn, $requestId, $error, $message, $accessExpiresAt) {
            if ($newAccess !== null)       $row->access_token       = $newAccess;
            if ($newRefresh !== null)      $row->refresh_token      = $newRefresh;
            if ($expireIn !== null)        $row->expire_in          = (int) $expireIn;
            if ($accessExpiresAt !== null) $row->access_expires_at  = $accessExpiresAt;

            $row->request_id = $requestId;
            $row->error      = $error;
            $row->message    = $message;
            $row->updated_at = now();
            $row->save();
        });

        // ==== Balikkan ringkasan ====
        return response()->json([
            'http_status' => $status,
            'saved' => [
                'chain_link'         => $row->chain_link,
                'shop_id'            => $row->shop_id,
                'partner_id'         => $row->partner_id ?? null,
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
    public function getOrderDetail(Request $request)
    {
        $host       = rtrim(env('SHOPEE_HOST'), '/');
        $partnerId  = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey = env('SHOPEE_PARTNER_KEY');

        // shop_id prioritas dari query, kalau kosong ambil dari env
        $shopId = (int) $request->query('shop_id', (int) env('SHOPEE_SHOP_ID'));

        if (!$partnerId || !$partnerKey || !$shopId) {
            return response()->json([
                'error'   => 'missing_param',
                'message' => 'Wajib: SHOPEE_PARTNER_ID, SHOPEE_PARTNER_KEY, dan shop_id.',
            ], 400);
        }

        // ambil access_token dari DB
        $tokenRow = ShopeeApiv2Token::where('shop_id', $shopId)
            ->orderByDesc('updated_at')
            ->first();

        if (!$tokenRow || empty($tokenRow->access_token)) {
            return response()->json([
                'error'   => 'no_access_token',
                'message' => "Tidak menemukan access_token untuk shop_id={$shopId} di tabel shopee_apiv2_tokens.",
            ], 404);
        }
        $accessToken = (string) $tokenRow->access_token;

        // 1) Cek apakah client kirim order_sn_list manual
        $orderSnListRaw = trim((string) $request->query('order_sn_list', ''));

        $listMeta = null;
        $orderSns = [];

        if ($orderSnListRaw === '') {
            // ambil dulu dari get_order_list (sama seperti getOrderList)
            $apiPathList = '/api/v2/order/get_order_list';
            $timestampList = time();
            $baseStringList = $partnerId . $apiPathList . $timestampList . $accessToken . $shopId;
            $signList = hash_hmac('sha256', $baseStringList, $partnerKey);

            $timeTo   = Carbon::now()->timestamp;
            $timeFrom = Carbon::now()->subDays(15)->timestamp;

            $timeRangeField           = $request->query('time_range_field', 'create_time');
            $pageSize                 = (int) $request->query('page_size', 100);
            $cursor                   = $request->query('cursor', '');
            $orderStatus              = $request->query('order_status', 'PROCESSED');
            $requestOrderStatusPending = $request->query('request_order_status_pending', null);
            $responseOptionalFieldsList = $request->query('list_response_optional_fields', null);
            $logisticsChannelId       = $request->query('logistics_channel_id', null);

            $paramsList = [
                'partner_id'       => $partnerId,
                'timestamp'        => $timestampList,
                'shop_id'          => $shopId,
                'access_token'     => $accessToken,
                'sign'             => $signList,
                'time_range_field' => $timeRangeField,
                'time_from'        => $timeFrom,
                'time_to'          => $timeTo,
                'page_size'        => $pageSize,
            ];

            if ($cursor !== '')                    $paramsList['cursor'] = $cursor;
            if (!is_null($orderStatus))            $paramsList['order_status'] = $orderStatus;
            if (!is_null($requestOrderStatusPending)) $paramsList['request_order_status_pending'] = $requestOrderStatusPending;
            if (!is_null($responseOptionalFieldsList)) $paramsList['response_optional_fields'] = $responseOptionalFieldsList;
            if (!is_null($logisticsChannelId))     $paramsList['logistics_channel_id'] = (int) $logisticsChannelId;

            try {
                $respList = Http::timeout(30)->get($host . $apiPathList, $paramsList);
            } catch (\Exception $e) {
                return response()->json([
                    'error'   => 'http_error',
                    'message' => 'Gagal memanggil get_order_list: ' . $e->getMessage(),
                ], 500);
            }

            $statusList = $respList->status();
            $jsonList   = $respList->json();

            if (!is_array($jsonList)) {
                return response()->json([
                    'error'   => 'invalid_response',
                    'message' => 'Respons get_order_list bukan JSON.',
                    'raw'     => $respList->body(),
                ], $statusList ?: 500);
            }

            if (!empty($jsonList['error'])) {
                return response()->json([
                    'error'        => $jsonList['error'],
                    'message'      => $jsonList['message'] ?? 'Shopee error pada get_order_list.',
                    'raw_response' => $jsonList,
                ], $statusList ?: 500);
            }

            $orderList = Arr::get($jsonList, 'response.order_list', []);
            if (empty($orderList)) {
                return response()->json([
                    'error'   => 'no_orders',
                    'message' => 'Tidak ada order yang ditemukan dari get_order_list.',
                    'raw_response' => $jsonList,
                ], 404);
            }

            $orderSns = collect($orderList)
                ->pluck('order_sn')
                ->filter()
                ->values()
                ->all();

            if (empty($orderSns)) {
                return response()->json([
                    'error'   => 'no_order_sn',
                    'message' => 'get_order_list tidak mengembalikan order_sn yang valid.',
                    'raw_response' => $jsonList,
                ], 404);
            }

            $listMeta = [
                'url'          => $host . $apiPathList . '?' . http_build_query($paramsList),
                'params'       => $paramsList,
                'raw_response' => $jsonList,
            ];
        } else {
            // gunakan order_sn_list dari query (split + trim)
            $orderSns = array_filter(array_map('trim', explode(',', $orderSnListRaw)));
        }

        if (empty($orderSns)) {
            return response()->json([
                'error'   => 'no_order_sn',
                'message' => 'Tidak ada order_sn untuk diproses.',
            ], 400);
        }

        // Shopee limit: max 50 order_sn per get_order_detail request
        $chunks = array_chunk($orderSns, 50);

        // Jika caller mengirim response_optional_fields lewat query, gunakan itu.
        // Kalau tidak, pakai daftar fields lengkap (recommended).
        $defaultFields = implode(',', [
            'buyer_user_id','buyer_username','estimated_shipping_fee','recipient_address',
            'actual_shipping_fee','goods_to_declare','note','note_update_time','item_list',
            'pay_time','dropshipper','dropshipper_phone','split_up','buyer_cancel_reason',
            'cancel_by','cancel_reason','actual_shipping_fee_confirmed','buyer_cpf_id',
            'fulfillment_flag','pickup_done_time','package_list','shipping_carrier',
            'payment_method','total_amount','invoice_data','order_chargeable_weight_gram',
            'return_request_due_date','edt','payment_info'
        ]);

        $requestedFields = $request->query('response_optional_fields', $defaultFields);
        $requestOrderStatusPending = $request->has('request_order_status_pending')
            ? filter_var($request->query('request_order_status_pending'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $mergedOrders = [];
        $rawResponses = [];
        $errors = [];

        foreach ($chunks as $chunk) {
            $orderSnListChunk = implode(',', $chunk);

            $apiPathDetail = '/api/v2/order/get_order_detail';
            $timestampDetail = time();
            $baseStringDetail = $partnerId . $apiPathDetail . $timestampDetail . $accessToken . $shopId;
            $signDetail = hash_hmac('sha256', $baseStringDetail, $partnerKey);

            $paramsDetail = [
                'partner_id'    => $partnerId,
                'timestamp'     => $timestampDetail,
                'shop_id'       => $shopId,
                'access_token'  => $accessToken,
                'sign'          => $signDetail,
                'order_sn_list' => $orderSnListChunk,
            ];

            if (!is_null($requestOrderStatusPending)) {
                $paramsDetail['request_order_status_pending'] = $requestOrderStatusPending;
            }
            if (!empty($requestedFields)) {
                $paramsDetail['response_optional_fields'] = $requestedFields;
            }

            try {
                $respDetail = Http::timeout(30)->get($host . $apiPathDetail, $paramsDetail);
            } catch (\Exception $e) {
                $errors[] = [
                    'type' => 'http_error',
                    'message' => 'Gagal memanggil get_order_detail: ' . $e->getMessage(),
                    'chunk' => $chunk,
                ];
                continue;
            }

            $statusDetail = $respDetail->status();
            $jsonDetail = $respDetail->json();

            $rawResponses[] = [
                'http_status' => $statusDetail,
                'params'      => $paramsDetail,
                'raw'         => $jsonDetail,
            ];

            if (!is_array($jsonDetail)) {
                $errors[] = [
                    'type' => 'invalid_response',
                    'message' => 'Respons get_order_detail bukan JSON.',
                    'params'  => $paramsDetail,
                    'raw'     => $respDetail->body(),
                ];
                continue;
            }

            if (!empty($jsonDetail['error'])) {
                $errors[] = [
                    'type' => 'shopee_error',
                    'error' => $jsonDetail['error'],
                    'message' => $jsonDetail['message'] ?? null,
                    'params'  => $paramsDetail,
                    'raw'     => $jsonDetail,
                ];
                // tetap coba ambil order_list meskipun error field ada (kadang Shopee set error tapi return partial)
            }

            $orderListChunk = Arr::get($jsonDetail, 'response.order_list', []);
            if (is_array($orderListChunk) && !empty($orderListChunk)) {
                // gabungkan
                $mergedOrders = array_merge($mergedOrders, $orderListChunk);
            }
        }

        // Deduplicate by order_sn (jika ada duplikat), keep last
        $mergedOrdersBySn = [];
        foreach ($mergedOrders as $ord) {
            if (isset($ord['order_sn'])) {
                $mergedOrdersBySn[$ord['order_sn']] = $ord;
            } else {
                // jika tidak ada order_sn (aneh), push dengan uniq key
                $mergedOrdersBySn[uniqid('o_')] = $ord;
            }
        }
        $mergedOrders = array_values($mergedOrdersBySn);

        $statusReturn = 200;
        if (!empty($errors) && empty($mergedOrders)) {
            // jika semua batch error dan tidak ada hasil -> kembalikan error code dari first raw response kalau ada
            $statusReturn = $rawResponses[0]['http_status'] ?? 500;
        }

        return response()->json([
            'http_status'      => $statusReturn,
            'requested_list'   => $listMeta, // bisa null kalau order_sn_list dikirim manual
            'requested_detail' => [
                // catatan: kalau chunk>1, ini hanya salah satu; raw_responses menyimpan detail tiap panggilan
                'fields_requested' => $requestedFields,
                'per_chunk_count'  => array_map('count', $chunks),
            ],
            'raw_responses'    => $rawResponses,
            'errors'           => $errors,
            'merged_response'  => [
                'order_list' => $mergedOrders,
                'count' => count($mergedOrders),
            ],
        ], $statusReturn);
    }

}
