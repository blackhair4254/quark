<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ShopeeApiv2Token;
use App\Models\TransaksiH;
use App\Models\TransaksiD;
use App\Models\ProdukMarketplaceMap;

class ShopeeGetOrderDetail extends Command
{
    protected $signature = 'shopee:get-order-detail
                            {--shop_id= : Shop ID Shopee (falls back to .env SHOPEE_SHOP_ID)}
                            {--chain_link= : chain_link to associate transactions (fallback: token.chain_link)}
                            {--order_status=PROCESSED : default order_status for get_order_list}
                            {--time_range_days=15 : how many days back when no last import}';

    protected $description = 'Ambil order detail dari Shopee (get_order_list + get_order_detail) dan simpan ke transaksi_h/d.';

    public function handle(): int
    {
        $host        = rtrim(env('SHOPEE_HOST'), '/');
        $partnerId   = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey  = env('SHOPEE_PARTNER_KEY');
        $shopId      = (int) $this->option('shop_id') ?: (int) env('SHOPEE_SHOP_ID');

        if (!$partnerId || !$partnerKey || !$shopId) {
            $this->error('Missing SHOPEE_PARTNER_ID / SHOPEE_PARTNER_KEY / shop_id');
            return self::FAILURE;
        }

        $tokenRow = ShopeeApiv2Token::where('shop_id', $shopId)
            ->orderByDesc('updated_at')
            ->first();

        if (!$tokenRow || empty($tokenRow->access_token)) {
            $this->error("No access_token found for shop_id={$shopId}");
            return self::FAILURE;
        }

        $accessToken = (string) $tokenRow->access_token;
        $chainLink = $this->option('chain_link') ?: ($tokenRow->chain_link ?? null);

        if (!$chainLink) {
            $this->error('chain_link not provided and token.chain_link is null. Provide --chain_link.');
            return self::FAILURE;
        }

        $apiPathList   = '/api/v2/order/get_order_list';
        $apiPathDetail = '/api/v2/order/get_order_detail';

        // ====== TIME RANGE (sama dengan controller) ======
        $lastImported = TransaksiH::where('chain_link', $chainLink)
            ->whereNotNull('shopee_create_time')
            ->max('shopee_create_time');

        $now = Carbon::now();
        $maxPast = $now->copy()->subDays((int)$this->option('time_range_days'));

        if ($lastImported) {
            $lastImportedCarbon = Carbon::parse($lastImported);
            $timeFromCarbon = $lastImportedCarbon->greaterThan($maxPast) ? $lastImportedCarbon : $maxPast;
        } else {
            $timeFromCarbon = $maxPast;
        }

        $timeFrom = $timeFromCarbon->timestamp;
        $timeTo   = $now->timestamp;

        // ====== get_order_list ======
        $timestampList = time();
        $baseStringList = $partnerId . $apiPathList . $timestampList . $accessToken . $shopId;
        $signList       = hash_hmac('sha256', $baseStringList, $partnerKey);

        $orderStatus = $this->option('order_status') ?? 'PROCESSED';
        $paramsList = [
            'partner_id'       => $partnerId,
            'timestamp'        => $timestampList,
            'shop_id'          => $shopId,
            'access_token'     => $accessToken,
            'sign'             => $signList,
            'time_range_field' => 'create_time',
            'time_from'        => $timeFrom,
            'time_to'          => $timeTo,
            'page_size'        => 100,
            'order_status'     => $orderStatus,
        ];

        $this->info("Calling get_order_list (shop_id={$shopId})");
        try {
            $respList = Http::timeout(30)->get($host . $apiPathList, $paramsList);
        } catch (\Exception $e) {
            $this->error('get_order_list http error: '.$e->getMessage());
            return self::FAILURE;
        }

        $jsonList = $respList->json();
        if (!is_array($jsonList) || !empty($jsonList['error'])) {
            $this->error('get_order_list error or invalid response: '.json_encode($jsonList));
            return self::FAILURE;
        }

        $orderList = Arr::get($jsonList, 'response.order_list', []);
        if (empty($orderList)) {
            $this->info('No orders returned from get_order_list.');
            return self::SUCCESS;
        }

        $orderSns = collect($orderList)->pluck('order_sn')->filter()->values()->all();
        if (empty($orderSns)) {
            $this->info('No valid order_sn from list.');
            return self::SUCCESS;
        }

        // ====== OPTIONAL FIELDS (SAMA DENGAN CONTROLLER) ======
        $defaultFields = implode(',', [
            'buyer_user_id','buyer_username','estimated_shipping_fee','recipient_address',
            'actual_shipping_fee','goods_to_declare','note','note_update_time','item_list',
            'pay_time','dropshipper','dropshipper_phone','split_up','buyer_cancel_reason',
            'cancel_by','cancel_reason','actual_shipping_fee_confirmed','buyer_cpf_id',
            'fulfillment_flag','pickup_done_time','package_list','shipping_carrier',
            'payment_method','total_amount','invoice_data','order_chargeable_weight_gram',
            'return_request_due_date','edt','payment_info'
        ]);
        $requestedFields = $defaultFields;

        // ====== get_order_detail (chunk) ======
        $chunks = array_chunk($orderSns, 50);
        $mergedOrders = [];

        foreach ($chunks as $chunk) {
            $orderSnListChunk = implode(',', $chunk);
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
                'response_optional_fields' => $requestedFields, // *** tambahan supaya field lengkap
            ];

            try {
                $respDetail = Http::timeout(30)->get($host . $apiPathDetail, $paramsDetail);
            } catch (\Exception $e) {
                $this->error('get_order_detail http error: '.$e->getMessage());
                continue;
            }

            $jsonDetail = $respDetail->json();
            if (!is_array($jsonDetail) || !empty($jsonDetail['error'])) {
                $this->error('get_order_detail returned error: '.json_encode($jsonDetail));
                continue;
            }

            $orderListChunk = Arr::get($jsonDetail, 'response.order_list', []);
            if (is_array($orderListChunk) && !empty($orderListChunk)) {
                $mergedOrders = array_merge($mergedOrders, $orderListChunk);
            }
        }

        if (empty($mergedOrders)) {
            $this->info('No order details retrieved.');
            return self::SUCCESS;
        }

        // dedup by order_sn
        $mergedBySn = [];
        foreach ($mergedOrders as $ord) {
            if (isset($ord['order_sn'])) {
                $mergedBySn[$ord['order_sn']] = $ord;
            }
        }
        $mergedOrders = array_values($mergedBySn);

        // ====== SIMPAN KE DB (DISAMAKAN DENGAN CONTROLLER) ======
        DB::transaction(function () use ($mergedOrders, $chainLink, $shopId) {
            foreach ($mergedOrders as $ord) {
                $orderSn = $ord['order_sn'] ?? null;
                if (!$orderSn) {
                    continue;
                }

                /** @var TransaksiH $header */
                $header = TransaksiH::firstOrNew([
                    'chain_link' => $chainLink,
                    'invoice'    => $orderSn,
                ]);

                $isNew = !$header->exists;

                // status selalu new
                $header->status = 'new';

                // ==== Field dasar (sama persis dengan controller) ====
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

                // ==== Field khusus Shopee (copy dari controller) ====
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
                $header->shopee_raw               = $ord;

                $header->save();

                // ====== DETAIL ======
                $idTransaksi = $header->id_transaksi;

                TransaksiD::where('id_transaksi_h', $idTransaksi)->delete();

                $items = $ord['item_list'] ?? [];
                foreach ($items as $item) {
                    $produkId = $this->mapShopeeItemToProdukId($item, $chainLink, $shopId);

                    TransaksiD::create([
                        'id_transaksi_h'                  => $idTransaksi,
                        'id_produk'                       => $produkId,
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

        $this->info('Done: imported '.count($mergedOrders).' orders into transaksi_h/d for chain_link='.$chainLink);
        return self::SUCCESS;
    }

    /**
     * Sama persis dengan protected mapShopeeItemToProdukId di controller,
     * tapi dipindah ke Command.
     */
    protected function mapShopeeItemToProdukId(array $item, string $chainLink, int $shopId): ?int
    {
        $itemId  = $item['item_id']  ?? null;
        $modelId = $item['model_id'] ?? null;

        if (!$itemId) {
            return null;
        }

        $modelId = (!empty($modelId) && $modelId != 0) ? (int) $modelId : null;

        $query = ProdukMarketplaceMap::where('chain_link', $chainLink)
            ->where('marketplace', 'shopee')
            ->where(function ($q) use ($shopId) {
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
}
