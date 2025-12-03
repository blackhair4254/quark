<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\ProdukMarketplaceMap;
use Illuminate\Http\Request;
use App\Models\ShopeeApiv2Token;
use App\Models\TransaksiD;
use App\Models\TransaksiH;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

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

    protected function mapShopeeItemToProdukId(array $item, string $chainLink, int $shopId): ?int
    {
        $itemId  = $item['item_id']  ?? null;
        $modelId = $item['model_id'] ?? null;

        if (!$itemId) {
            return null;
        }

        // Kalau model_id == 0 atau null -> treat sebagai non-varian (mapping by item_id saja)
        $modelId = (!empty($modelId) && $modelId != 0) ? (int) $modelId : null;

        $query = ProdukMarketplaceMap::where('chain_link', $chainLink)
            ->where('marketplace', 'shopee')
            ->where(function ($q) use ($shopId) {
                // mapping bisa general (shop_id null) atau spesifik shop
                $q->whereNull('shop_id')
                ->orWhere('shop_id', $shopId);
            })
            ->where('marketplace_item_id', $itemId);

        if ($modelId) {
            $query->where('marketplace_model_id', $modelId);
        } else {
            $query->whereNull('marketplace_model_id');
        }

        $map = $query->first();

        return $map?->id_produk;
    }


    public function getOrderDetail(Request $request)
    {
        $host       = rtrim(env('SHOPEE_HOST'), '/');
        $partnerId  = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey = env('SHOPEE_PARTNER_KEY');

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

        // Chain link (untuk transaksi_h)
        $chainLink = Auth::user()->chain_link;

        // 1) Cek apakah client kirim order_sn_list manual
        $orderSnListRaw = trim((string) $request->query('order_sn_list', ''));

        $listMeta = null;
        $orderSns = [];

        if ($orderSnListRaw === '') {
            // ========== Ambil TIME_FROM dinamis ==========

            // Cek last shopee_create_time untuk chain_link ini
            $lastImported = TransaksiH::where('chain_link', $chainLink)
                ->whereNotNull('shopee_create_time')
                ->max('shopee_create_time'); // datetime atau null

            $now = Carbon::now();
            $maxPast = $now->copy()->subDays(15);

            if ($lastImported) {
                $lastImportedCarbon = Carbon::parse($lastImported);
                // time_from = max(lastImported, now - 15 hari)
                $timeFromCarbon = $lastImportedCarbon->greaterThan($maxPast)
                    ? $lastImportedCarbon
                    : $maxPast;
            } else {
                // belum pernah tarik dari Shopee -> default 15 hari ke belakang
                $timeFromCarbon = $maxPast;
            }

            $timeFrom = $timeFromCarbon->timestamp;
            $timeTo   = $now->timestamp;

            // ========== Panggil get_order_list ==========

            $apiPathList   = '/api/v2/order/get_order_list';
            $timestampList = time();
            $baseStringList = $partnerId . $apiPathList . $timestampList . $accessToken . $shopId;
            $signList       = hash_hmac('sha256', $baseStringList, $partnerKey);

            // default order_status: READY_TO_SHIP (bisa override via query)
            $orderStatus = $request->query('order_status', 'PROCESSED');

            $timeRangeField            = $request->query('time_range_field', 'create_time');
            $pageSize                  = (int) $request->query('page_size', 100);
            $cursor                    = $request->query('cursor', '');
            $requestOrderStatusPending = $request->query('request_order_status_pending', null);
            $responseOptionalFieldsList = $request->query('list_response_optional_fields', null);
            $logisticsChannelId        = $request->query('logistics_channel_id', null);
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
                'order_status'     => $orderStatus,
            ];

            if ($cursor !== '')                        $paramsList['cursor'] = $cursor;
            if (!is_null($requestOrderStatusPending))  $paramsList['request_order_status_pending'] = $requestOrderStatusPending;
            if (!is_null($responseOptionalFieldsList)) $paramsList['response_optional_fields']      = $responseOptionalFieldsList;
            if (!is_null($logisticsChannelId))         $paramsList['logistics_channel_id']          = (int) $logisticsChannelId;

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
                    'error'        => 'no_orders',
                    'message'      => 'Tidak ada order yang ditemukan dari get_order_list.',
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
                    'error'        => 'no_order_sn',
                    'message'      => 'get_order_list tidak mengembalikan order_sn yang valid.',
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

            $apiPathDetail    = '/api/v2/order/get_order_detail';
            $timestampDetail  = time();
            $baseStringDetail = $partnerId . $apiPathDetail . $timestampDetail . $accessToken . $shopId;
            $signDetail       = hash_hmac('sha256', $baseStringDetail, $partnerKey);

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
                    'type'    => 'http_error',
                    'message' => 'Gagal memanggil get_order_detail: ' . $e->getMessage(),
                    'chunk'   => $chunk,
                ];
                continue;
            }

            $statusDetail = $respDetail->status();
            $jsonDetail   = $respDetail->json();

            $rawResponses[] = [
                'http_status' => $statusDetail,
                'params'      => $paramsDetail,
                'raw'         => $jsonDetail,
            ];

            if (!is_array($jsonDetail)) {
                $errors[] = [
                    'type'    => 'invalid_response',
                    'message' => 'Respons get_order_detail bukan JSON.',
                    'params'  => $paramsDetail,
                    'raw'     => $respDetail->body(),
                ];
                continue;
            }

            if (!empty($jsonDetail['error'])) {
                $errors[] = [
                    'type'    => 'shopee_error',
                    'error'   => $jsonDetail['error'],
                    'message' => $jsonDetail['message'] ?? null,
                    'params'  => $paramsDetail,
                    'raw'     => $jsonDetail,
                ];
            }

            $orderListChunk = Arr::get($jsonDetail, 'response.order_list', []);
            if (is_array($orderListChunk) && !empty($orderListChunk)) {
                $mergedOrders = array_merge($mergedOrders, $orderListChunk);
            }
        }

        // Deduplicate by order_sn
        $mergedOrdersBySn = [];
        foreach ($mergedOrders as $ord) {
            if (isset($ord['order_sn'])) {
                $mergedOrdersBySn[$ord['order_sn']] = $ord;
            } else {
                $mergedOrdersBySn[uniqid('o_')] = $ord;
            }
        }
        $mergedOrders = array_values($mergedOrdersBySn);

        // ========== SIMPAN KE DB: transaksi_h & transaksi_d ==========
        DB::transaction(function () use ($mergedOrders, $chainLink, $shopId) {
            foreach ($mergedOrders as $ord) {

                $orderSn = $ord['order_sn'] ?? null;
                if (!$orderSn) {
                    continue;
                }

                // header: unique by (chain_link, invoice=order_sn)
                /** @var TransaksiH $header */
                $header = TransaksiH::firstOrNew([
                    'chain_link' => $chainLink,
                    'invoice'    => $orderSn,
                ]);

                $isNew = !$header->exists;

                // Set status transaksi dari Shopee
                $header->status = 'new'; // sesuai permintaan

                // Field dasar (hanya override kalau baru, biar nggak ganggu edit manual)
                if ($isNew) {
                    $header->pengirim          = $header->pengirim          ?? ('Shopee Shop ' . $shopId);
                    $header->no_telp_pengirim  = $header->no_telp_pengirim  ?? '';
                    $header->jenis_logistik    = $header->jenis_logistik    ?? ($ord['shipping_carrier'] ?? '');
                    $header->no_resi           = $header->no_resi           ?? (Arr::get($ord, 'package_list.0.package_number', ''));

                    $header->nama_penerima     = $header->nama_penerima     ?? Arr::get($ord, 'recipient_address.name', '');
                    $header->no_telp_penerima  = $header->no_telp_penerima  ?? Arr::get($ord, 'recipient_address.phone', '');
                    $header->alamat_penerima   = $header->alamat_penerima   ?? Arr::get($ord, 'recipient_address.full_address', '');

                    $createTs = $ord['create_time'] ?? null;
                    $header->tanggal = $header->tanggal
                        ?? ($createTs ? Carbon::createFromTimestamp($createTs)->toDateString() : now()->toDateString());
                }

                // Map field Shopee ke kolom khusus
                $header->shopee_order_sn      = $orderSn;
                $header->shopee_region        = $ord['region']  ?? null;
                $header->shopee_currency      = $ord['currency'] ?? null;
                $header->shopee_cod           = $ord['cod'] ?? null;
                $header->shopee_order_status  = $ord['order_status'] ?? null;

                $header->shopee_buyer_user_id   = $ord['buyer_user_id'] ?? null;
                $header->shopee_buyer_username  = $ord['buyer_username'] ?? null;
                $header->shopee_shipping_carrier = $ord['shipping_carrier'] ?? null;
                $header->shopee_payment_method   = $ord['payment_method'] ?? null;

                $header->shopee_total_amount            = $ord['total_amount'] ?? null;
                $header->shopee_estimated_shipping_fee  = $ord['estimated_shipping_fee'] ?? null;
                $header->shopee_actual_shipping_fee     = $ord['actual_shipping_fee'] ?? null;
                $header->shopee_reverse_shipping_fee    = $ord['reverse_shipping_fee'] ?? null;
                $header->shopee_days_to_ship            = $ord['days_to_ship'] ?? null;
                $header->shopee_order_chargeable_weight_gram = $ord['order_chargeable_weight_gram'] ?? null;

                $header->shopee_create_time = isset($ord['create_time'])
                    ? Carbon::createFromTimestamp($ord['create_time'])
                    : null;
                $header->shopee_update_time = isset($ord['update_time'])
                    ? Carbon::createFromTimestamp($ord['update_time'])
                    : null;
                $header->shopee_pay_time = isset($ord['pay_time'])
                    ? Carbon::createFromTimestamp($ord['pay_time'])
                    : null;
                $header->shopee_ship_by_date = isset($ord['ship_by_date'])
                    ? Carbon::createFromTimestamp($ord['ship_by_date'])
                    : null;
                $header->shopee_return_request_due_date = isset($ord['return_request_due_date'])
                    ? Carbon::createFromTimestamp($ord['return_request_due_date'])
                    : null;

                $header->shopee_is_buyer_shop_collection = $ord['is_buyer_shop_collection'] ?? null;
                $header->shopee_goods_to_declare         = $ord['goods_to_declare'] ?? null;

                $header->shopee_fulfillment_flag   = $ord['fulfillment_flag'] ?? null;
                $header->shopee_message_to_seller  = $ord['message_to_seller'] ?? null;
                $header->shopee_note               = $ord['note'] ?? null;
                $header->shopee_note_update_time   = isset($ord['note_update_time']) && $ord['note_update_time']
                    ? Carbon::createFromTimestamp($ord['note_update_time'])
                    : null;

                $header->shopee_pending_terms    = $ord['pending_terms'] ?? null;
                $header->shopee_recipient_address = $ord['recipient_address'] ?? null;
                $header->shopee_package_list      = $ord['package_list'] ?? null;
                $header->shopee_invoice_data      = $ord['invoice_data'] ?? null;
                $header->shopee_payment_info      = $ord['payment_info'] ?? null;
                $header->shopee_raw               = $ord; // entire order object

                $header->save();

                // ========== DETAIL ==========
                $idTransaksi = $header->id_transaksi;

                // Optional: hapus detail lama utk order ini (kalau mau sync penuh)
                TransaksiD::where('id_transaksi_h', $idTransaksi)->delete();

                $items = $ord['item_list'] ?? [];
                foreach ($items as $item) {
                    $produkId = $this->mapShopeeItemToProdukId($item, $chainLink, $shopId);

                    TransaksiD::create([
                        'id_transaksi_h'                  => $idTransaksi,
                        'id_produk'                       => $produkId, // boleh null, migration kamu sudah nullable
                        'nama_produk'                     => $item['item_name'] ?? ($item['model_name'] ?? 'Produk Shopee'),
                        'qty'                             => $item['model_quantity_purchased'] ?? ($item['quantity'] ?? 1),

                        'shopee_item_id'                  => $item['item_id'] ?? null,
                        'shopee_order_item_id'            => $item['order_item_id'] ?? null,
                        'shopee_model_id'                 => $item['model_id'] ?? null,
                        'shopee_item_sku'                 => $item['item_sku'] ?? null,
                        'shopee_model_sku'                => $item['model_sku'] ?? null,
                        'shopee_item_name'                => $item['item_name'] ?? null,
                        'shopee_model_name'               => $item['model_name'] ?? null,
                        'shopee_model_original_price'     => $item['model_original_price'] ?? null,
                        'shopee_model_discounted_price'   => $item['model_discounted_price'] ?? null,
                        'shopee_weight'                   => $item['weight'] ?? null,
                        'shopee_add_on_deal'              => $item['add_on_deal'] ?? null,
                        'shopee_add_on_deal_id'           => $item['add_on_deal_id'] ?? null,
                        'shopee_main_item'                => $item['main_item'] ?? null,
                        'shopee_promotion_type'           => $item['promotion_type'] ?? null,
                        'shopee_promotion_id'             => $item['promotion_id'] ?? null,
                        'shopee_promotion_group_id'       => $item['promotion_group_id'] ?? null,
                        'shopee_image_url'                => Arr::get($item, 'image_info.image_url'),
                        'shopee_product_location_id'      => $item['product_location_id'] ?? null,
                        'shopee_item_raw'                 => $item,
                    ]);
                }


            }
        });

        $statusReturn = 200;
        if (!empty($errors) && empty($mergedOrders)) {
            $statusReturn = $rawResponses[0]['http_status'] ?? 500;
        }

        return response()->json([
            'http_status'      => $statusReturn,
            'requested_list'   => $listMeta,
            'requested_detail' => [
                'fields_requested' => $requestedFields,
                'per_chunk_count'  => array_map('count', $chunks),
            ],
            'raw_responses'    => $rawResponses,
            'errors'           => $errors,
            'merged_response'  => [
                'order_list' => $mergedOrders,
                'count'      => count($mergedOrders),
            ],
        ], $statusReturn);
    }

        /**
     * Halaman mapping produk marketplace <-> produk internal
     * GET /wms/mapping-produk
     */
    public function mappingProdukIndex(Request $request)
    {
        $chainLink  = Auth::user()->chain_link;
        $marketplace = 'shopee';

        $shopId = (int) $request->query('shop_id', (int) env('SHOPEE_SHOP_ID'));

        $itemIdRaw  = $request->query('marketplace_item_id');
        $modelIdRaw = $request->query('marketplace_model_id');

        $itemId = $itemIdRaw !== null && $itemIdRaw !== '' ? (int) $itemIdRaw : null;
        $modelId = (!empty($modelIdRaw) && $modelIdRaw != 0) ? (int) $modelIdRaw : null;

        $shopeeItemName  = null;
        $shopeeModelName = null;
        if ($itemId) {
            $row = DB::table('transaksi_d as d')
                ->join('transaksi_h as h', 'h.id_transaksi', '=', 'd.id_transaksi_h')
                ->where('h.chain_link', $chainLink)
                ->where('d.shopee_item_id', $itemId)
                ->when($modelId, function ($q) use ($modelId) {
                    $q->where('d.shopee_model_id', $modelId);
                }, function ($q) {
                    $q->whereNull('d.shopee_model_id');
                })
                ->orderByDesc('h.id_transaksi')
                ->select('d.shopee_item_name', 'd.shopee_model_name')
                ->first();

            if ($row) {
                $shopeeItemName  = $row->shopee_item_name;
                $shopeeModelName = $row->shopee_model_name;
            }
        }
        // daftar mapping (lama)
        $mappings = ProdukMarketplaceMap::with('produk')
            ->where('chain_link', $chainLink)
            ->where('marketplace', $marketplace)
            ->when($shopId, function ($q) use ($shopId) {
                $q->where(function ($qq) use ($shopId) {
                    $qq->whereNull('shop_id')
                    ->orWhere('shop_id', $shopId);
                });
            })
            ->orderByDesc('id')
            ->paginate(50);

        // daftar produk internal (baru) + search
        $q = trim((string) $request->query('q', ''));
        $produkQuery = Produk::query()
            ->where('chain_link', $chainLink);

        if ($q !== '') {
            $produkQuery->where(function ($qq) use ($q) {
                $qq->where('nama_produk', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $produkPage = $produkQuery
            ->orderBy('nama_produk')
            ->paginate(20)
            ->withQueryString();

        // mapping aktif untuk kombinasi item_id + model_id (kalau ada)
        $activeMap = null;
        if ($itemId) {
            $activeMap = ProdukMarketplaceMap::where('chain_link', $chainLink)
                ->where('marketplace', $marketplace)
                ->when($shopId, function ($q) use ($shopId) {
                    $q->where(function ($qq) use ($shopId) {
                        $qq->whereNull('shop_id')
                        ->orWhere('shop_id', $shopId);
                    });
                })
                ->where('marketplace_item_id', $itemId)
                ->when($modelId, function ($q) use ($modelId) {
                    $q->where('marketplace_model_id', $modelId);
                }, function ($q) {
                    $q->whereNull('marketplace_model_id');
                })
                ->first();
        }

        return view('wms.mapping_produk.index', compact(
            'mappings',
            'shopId',
            'marketplace',
            'produkPage',
            'q',
            'itemId',
            'modelId',
            'shopeeItemName',
            'shopeeModelName',
            'activeMap',
        ));
    }


    /**
     * POST tambah / update mapping
     * POST /wms/mapping-produk
     */
    public function mappingProdukStore(Request $request)
    {
        $chainLink  = Auth::user()->chain_link;
        $marketplace = 'shopee';

        $validated = $request->validate([
            'shop_id'              => 'nullable|integer',
            'marketplace_item_id'  => 'required|numeric',
            'marketplace_model_id' => 'nullable|numeric',
            'id_produk'            => 'required|exists:produk,id_produk',
        ], [
            'marketplace_item_id.required' => 'Item ID Shopee wajib diisi.',
            'id_produk.required'           => 'Produk internal wajib dipilih.',
        ]);

        $shopId     = (int) ($validated['shop_id'] ?? env('SHOPEE_SHOP_ID'));
        $itemId     = (int) $validated['marketplace_item_id'];
        $modelIdRaw = $validated['marketplace_model_id'] ?? null;
        $modelId    = (!empty($modelIdRaw) && $modelIdRaw != 0) ? (int) $modelIdRaw : null;
        $idProduk   = (int) $validated['id_produk'];

        // cek apakah sudah ada mapping untuk item/model ini
        $existing = ProdukMarketplaceMap::where('chain_link', $chainLink)
            ->where('marketplace', $marketplace)
            ->where('shop_id', $shopId)
            ->where('marketplace_item_id', $itemId)
            ->when($modelId, function ($q) use ($modelId) {
                $q->where('marketplace_model_id', $modelId);
            }, function ($q) {
                $q->whereNull('marketplace_model_id');
            })
            ->first();

        if ($existing) {
            if ($existing->id_produk === $idProduk) {
                return back()->with('status', 'Mapping sudah ada, tidak ada perubahan.');
            }

            // update mapping ke produk baru
            $existing->id_produk = $idProduk;
            $existing->save();

            return back()->with('status', 'Mapping produk berhasil diperbarui.');
        }

        // buat mapping baru
        ProdukMarketplaceMap::create([
            'chain_link'           => $chainLink,
            'marketplace'          => $marketplace,
            'shop_id'              => $shopId,
            'marketplace_item_id'  => $itemId,
            'marketplace_model_id' => $modelId,
            'id_produk'            => $idProduk,
        ]);

        return back()->with('status', 'Mapping produk berhasil dibuat.');
    }



    /**
     * DELETE /wms/mapping-produk/{id}
     */
    public function mappingProdukDestroy($id)
    {
        $chainLink = Auth::user()->chain_link;

        $map = ProdukMarketplaceMap::where('id', $id)
            ->where('chain_link', $chainLink)
            ->first();

        if (!$map) {
            return back()->withErrors(['mapping' => 'Data mapping tidak ditemukan atau bukan milik chain_link ini.']);
        }

        $map->delete();

        return back()->with('status', 'Mapping produk berhasil dihapus.');
    }

    /**
     * AJAX search produk internal
     * GET /wms/mapping-produk/search-produk?q=...
     */
    public function mappingProdukSearchProduk(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $produk = Produk::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nama_produk', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderBy('nama_produk')
            ->limit(20)
            ->get(['id_produk', 'nama_produk', 'sku']);

        return response()->json($produk);
    }


}
