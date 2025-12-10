<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ... kalau masih ada contoh Inspiring::quote dsb biarkan saja

// === Jadwal cron Shopee ===

// Refresh token tiap 5 menit
Schedule::command('shopee:refresh-tokens', ['--buffer' => 300])
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo('/home/quarkcoi/cron.log'); // atau path lain yang kamu mau

// Hapus transaksi NEW yang tidak PROCESSED tiap 5 menit
Schedule::command('shopee:delete-nonprocessed-new-orders')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo('/home/quarkcoi/DeleteTransaksiShopee.log');

// Tarik order detail tiap menit
Schedule::command('shopee:get-order-detail')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo('/home/quarkcoi/GetOrderDetailShopee.log');

