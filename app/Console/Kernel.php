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
        \App\Console\Commands\ShopeeGetOrderDetail::class,
    ];

    /**
     * Jadwal task (scheduler) kamu.
     */
    protected function schedule(Schedule $schedule): void
    {
        
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
