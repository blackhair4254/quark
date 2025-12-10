<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\RefreshShopeeTokens::class,
        \App\Console\Commands\DeleteNonProcessedNewOrders::class,
        \App\Console\Commands\ShopeeGetOrderDetail::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Refresh token tiap 5 menit
        $schedule->command('shopee:refresh-tokens', ['--buffer' => 300])
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo('/home/quarkcoi/cron.log');

        // Hapus transaksi NEW yang tidak PROCESSED tiap 5 menit
        $schedule->command('shopee:delete-nonprocessed-new-orders')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo('/home/quarkcoi/DeleteTransaksiShopee.log');

        // Tarik order detail tiap menit
        $schedule->command('shopee:get-order-detail')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo('/home/quarkcoi/GetOrderDetailShopee.log');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
