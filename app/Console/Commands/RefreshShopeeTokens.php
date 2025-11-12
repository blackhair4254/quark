<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopeeApiv2Token;
use App\Services\ShopeeAuthService;

class RefreshShopeeTokens extends Command
{
    protected $signature = 'shopee:refresh-tokens {--shop_id=} {--buffer=300}';
    // --buffer=300 detik (5 menit) sebelum kadaluarsa kita refresh

    protected $description = 'Refresh Shopee access tokens yang sudah atau hampir kadaluarsa';

    public function handle(ShopeeAuthService $svc): int
    {
        $shopIdOpt = $this->option('shop_id');
        $bufferSec = (int) $this->option('buffer');

        if ($shopIdOpt) {
            $shopIds = [(int) $shopIdOpt];
        } else {
            // Ambil semua shop_id unik yang punya token
            $shopIds = ShopeeApiv2Token::query()
                ->whereNotNull('shop_id')
                ->distinct()
                ->pluck('shop_id')
                ->map(fn($v) => (int) $v)
                ->all();
        }

        $nowPlusBuffer = now()->addSeconds($bufferSec);

        foreach ($shopIds as $shopId) {
            // cek token terbaru per shop
            $row = ShopeeApiv2Token::where('shop_id', $shopId)
                ->orderByDesc('updated_at')
                ->first();

            // jika belum ada token sama sekali → skip (atau kamu bisa paksa ambil via code)
            if (!$row) {
                $this->warn("Shop {$shopId}: belum ada token. Lewati.");
                continue;
            }

            $expiresAt = $row->access_expires_at; // bisa null
            $needRefresh = !$expiresAt || $expiresAt->lessThanOrEqualTo($nowPlusBuffer);

            if (!$needRefresh) {
                $this->line("Shop {$shopId}: masih valid sampai {$expiresAt->toIso8601String()}");
                continue;
            }

            $this->info("Shop {$shopId}: refresh token (expire at: " . ($expiresAt?->toIso8601String() ?? 'null') . ")");
            $res = $svc->refreshForShop($shopId);

            if ($res['ok'] ?? false) {
                $this->info("Shop {$shopId}: refresh OK. Expires at: ".$res['row']->access_expires_at?->toIso8601String());
            } else {
                $this->error("Shop {$shopId}: refresh GAGAL. Status {$res['status']} - ".json_encode($res['json']));
            }
        }

        return self::SUCCESS;
    }
}
