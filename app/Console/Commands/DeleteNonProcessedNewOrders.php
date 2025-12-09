<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopeeApiv2Token;
use App\Models\TransaksiH;
use App\Models\TransaksiD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class DeleteNonProcessedNewOrders extends Command
{
    protected $signature = 'shopee:delete-nonprocessed-new-orders {--treat_not_found_as_delete=1}';

    protected $description = 'Hapus transaksi Shopee berstatus new yang order_status di Shopee bukan PROCESSED';

    public function handle(): int
    {
        $host       = rtrim(env('SHOPEE_HOST'), '/');
        $partnerId  = (int) env('SHOPEE_PARTNER_ID');
        $partnerKey = env('SHOPEE_PARTNER_KEY');
        $shopId     = (int) env('SHOPEE_SHOP_ID');

        if (!$partnerId || !$partnerKey || !$shopId) {
            $this->error('Env SHOPEE_HOST / SHOPEE_PARTNER_ID / SHOPEE_PARTNER_KEY / SHOPEE_SHOP_ID belum lengkap.');
            return self::FAILURE;
        }

        // ambil access_token terbaru
        $tokenRow = ShopeeApiv2Token::where('shop_id', $shopId)
            ->orderByDesc('updated_at')
            ->first();

        if (!$tokenRow || empty($tokenRow->access_token)) {
            $this->error("Tidak ada access_token untuk shop_id={$shopId}.");
            return self::FAILURE;
        }

        $accessToken = (string) $tokenRow->access_token;

        // Ambil semua chain_link yang punya transaksi status 'new'
        $chainLinks = TransaksiH::where('status', 'new')
            ->whereNotNull('invoice')
            ->distinct()
            ->pluck('chain_link');

        if ($chainLinks->isEmpty()) {
            $this->info('Tidak ada transaksi berstatus new di sistem.');
            return self::SUCCESS;
        }

        $totalChecked  = 0;
        $totalToDelete = 0;
        $totalDeleted  = 0;

        $treatNotFoundAsDelete = (bool)$this->option('treat_not_found_as_delete');

        foreach ($chainLinks as $chainLink) {
            $this->info("Proses chain_link: {$chainLink}");

            // Invoice untuk chain_link ini
            $orderSns = TransaksiH::where('status', 'new')
                ->where('chain_link', $chainLink)
                ->pluck('invoice')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $countForChain = count($orderSns);
            $totalChecked += $countForChain;

            if (empty($orderSns)) {
                $this->line("- Tidak ada invoice untuk chain_link ini.");
                continue;
            }

            // Shopee limit 50 per request
            $chunks = array_chunk($orderSns, 50);

            $apiPathDetail = '/api/v2/order/get_order_detail';
            $mergedOrders  = [];
            $mergedBySn    = [];

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
                    $this->error("- HTTP error get_order_detail untuk chunk: " . $e->getMessage());
                    continue;
                }

                $jsonDetail = $respDetail->json();
                if (!is_array($jsonDetail)) {
                    $this->error("- Respons bukan JSON. Lewati chunk.");
                    continue;
                }

                $orderListChunk = Arr::get($jsonDetail, 'response.order_list', []);
                if (is_array($orderListChunk) && !empty($orderListChunk)) {
                    foreach ($orderListChunk as $ord) {
                        if (isset($ord['order_sn'])) {
                            $mergedBySn[$ord['order_sn']] = $ord;
                        }
                    }
                }
            }

            $toDeleteInvoices = [];

            foreach ($orderSns as $sn) {
                if (isset($mergedBySn[$sn])) {
                    $ord         = $mergedBySn[$sn];
                    $orderStatus = $ord['order_status'] ?? null;

                    if (!is_string($orderStatus) || strtoupper($orderStatus) !== 'PROCESSED') {
                        $toDeleteInvoices[] = $sn;
                    }
                } else {
                    // order_sn tidak ditemukan di Shopee
                    if ($treatNotFoundAsDelete) {
                        $toDeleteInvoices[] = $sn;
                    }
                }
            }

            $toDeleteInvoices = array_values(array_unique($toDeleteInvoices));
            $totalToDelete   += count($toDeleteInvoices);

            if (empty($toDeleteInvoices)) {
                $this->line("- Tidak ada yang perlu dihapus untuk chain_link ini.");
                continue;
            }

            DB::transaction(function () use (&$totalDeleted, $chainLink, $toDeleteInvoices) {
                $headers = TransaksiH::where('chain_link', $chainLink)
                    ->where('status', 'new')
                    ->whereIn('invoice', $toDeleteInvoices)
                    ->get(['id_transaksi', 'invoice']);

                if ($headers->isEmpty()) {
                    return;
                }

                $ids = $headers->pluck('id_transaksi')->all();

                TransaksiD::whereIn('id_transaksi_h', $ids)->delete();
                TransaksiH::whereIn('id_transaksi', $ids)->delete();

                $deleted = count($ids);
                $totalDeleted += $deleted;
            });

            $this->info("- Chain_link {$chainLink}: akan dihapus " . count($toDeleteInvoices) . " invoice.");
        }

        $this->info("Selesai. Dicek: {$totalChecked}, kandidat hapus: {$totalToDelete}, terhapus: {$totalDeleted}");

        return self::SUCCESS;
    }
}
