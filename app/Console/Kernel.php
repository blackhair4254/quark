<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Daftar command kustom (opsional).
     * Misal: protected $commands = [\App\Console\Commands\RefreshShopeeTokens::class];
     */
    protected $commands = [
        // \App\Console\Commands\RefreshShopeeTokens::class,
        \App\Console\Commands\DeleteNonProcessedNewOrders::class,
    ];

    /**
     * Jadwal task (scheduler) kamu.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Contoh: jalankan cek refresh token tiap 5 menit
        $schedule->command('shopee:refresh-tokens --buffer=300')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/shopee_token_refresh.log'));

        $schedule->command('shopee:delete-nonprocessed-new-orders')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/shopee_delete_nonprocessed_new_orders.log'));
    }

    /**
     * Registrasi route console dan auto-discover command.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
