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

        $host = rtrim(env('SHOPEE_HOST'), '/');
        $apiPathList   = '/api/v2/order/get_order_list';
        $apiPathDetail = '/api/v2/order/get_order_detail';

        // deduce time range
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

        // chunk & call get_order_detail
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
            if (isset($ord['order_sn'])) $mergedBySn[$ord['order_sn']] = $ord;
        }
        $mergedOrders = array_values($mergedBySn);

        // simpan ke DB (sama seperti controller logic)
        DB::transaction(function () use ($mergedOrders, $chainLink, $shopId) {
            foreach ($mergedOrders as $ord) {
                $orderSn = $ord['order_sn'] ?? null;
                if (!$orderSn) continue;

                $header = TransaksiH::firstOrNew([
                    'chain_link' => $chainLink,
                    'invoice'    => $orderSn,
                ]);

                $isNew = !$header->exists;
                $header->status = 'new';

                if ($isNew) {
                    $header->pengirim = $header->pengirim ?? ('Shopee Shop ' . $shopId);
                    $header->no_telp_pengirim = $header->no_telp_pengirim ?? '';
                    $header->jenis_logistik = $header->jenis_logistik ?? ($ord['shipping_carrier'] ?? '');
                    $header->no_resi = $header->no_resi ?? Arr::get($ord, 'package_list.0.package_number', '');
                    $header->nama_penerima = $header->nama_penerima ?? Arr::get($ord, 'recipient_address.name', '');
                    $header->no_telp_penerima = $header->no_telp_penerima ?? Arr::get($ord, 'recipient_address.phone', '');
                    $header->alamat_penerima = $header->alamat_penerima ?? Arr::get($ord, 'recipient_address.full_address', '');
                    $createTs = $ord['create_time'] ?? null;
                    $header->tanggal = $header->tanggal ?? ($createTs ? Carbon::createFromTimestamp($createTs)->toDateString() : now()->toDateString());
                }

                // map fields (ringkas, sama seperti controller)
                $header->shopee_order_sn = $orderSn;
                $header->shopee_order_status = $ord['order_status'] ?? null;
                $header->shopee_raw = $ord;
                $header->shopee_create_time = isset($ord['create_time']) ? Carbon::createFromTimestamp($ord['create_time']) : null;
                $header->shopee_update_time = isset($ord['update_time']) ? Carbon::createFromTimestamp($ord['update_time']) : null;
                $header->save();

                $idTransaksi = $header->id_transaksi;
                TransaksiD::where('id_transaksi_h', $idTransaksi)->delete();

                $items = $ord['item_list'] ?? [];
                foreach ($items as $item) {
                    TransaksiD::create([
                        'id_transaksi_h' => $idTransaksi,
                        'id_produk' => null,
                        'nama_produk' => $item['item_name'] ?? ($item['model_name'] ?? 'Produk Shopee'),
                        'qty' => $item['model_quantity_purchased'] ?? ($item['quantity'] ?? 1),
                        'shopee_item_id' => $item['item_id'] ?? null,
                        'shopee_model_id' => $item['model_id'] ?? null,
                        'shopee_item_sku' => $item['item_sku'] ?? null,
                        'shopee_model_sku' => $item['model_sku'] ?? null,
                        'shopee_item_raw' => $item,
                    ]);
                }
            }
        });

        $this->info('Done: imported '.count($mergedOrders).' orders into transaksi_h/d for chain_link='.$chainLink);
        return self::SUCCESS;
    }
}
