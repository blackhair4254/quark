<?php

use App\Http\Controllers\Auth\OmsLoginController;
use App\Http\Controllers\Auth\WmsLoginController;
use App\Http\Controllers\InboundController;
use App\Http\Controllers\Oms\BalanceStockController as OmsBalanceStockController;
use App\Http\Controllers\Oms\OmsInboundController;
use App\Http\Controllers\Oms\StockController as OmsStockController;
use App\Http\Controllers\Oms\TransaksiController as OmsTransaksiController;
use App\Http\Controllers\OmsStaffController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ShopeeAuthController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\Wms\BalanceStockController;
use App\Models\Produk;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

// START WMS

Route::get('/wms/login', [WmsLoginController::class, 'show'])->name('wms.login');
Route::post('/wms/login', [WmsLoginController::class, 'login'])->name('wms.login.post');
Route::post('/wms/logout', [WmsLoginController::class, 'logout'])->name('wms.logout');
Route::get('/shopee/get-access-token', [ShopeeAuthController::class, 'getAccessToken']);
// Route::match(['GET','POST'], '/shopee/refresh-access-token', [ShopeeAuthController::class, 'refreshAccessToken']);





Route::middleware(['auth','role:wms'])->prefix('wms')->group(function () {

    Route::view('dashboard', 'wms.dashboard')->name('wms.dashboard');

    // START Produk
    Route::delete('produk/bulk-destroy', [ProdukController::class, 'bulkDestroy'])
        ->name('wms.produk.bulk-destroy');
        
    Route::resource('produk', ProdukController::class)
        ->names('wms.produk'); 
    //END Produk


    // START Inbound
    Route::resource('inbound', InboundController::class)
        ->only(['index','create','store','show'])
        ->names('wms.inbound');
        // Aksi status
    Route::get('/inbound/{inbound}', [OmsInboundController::class, 'show'])
            ->name('inbound.show');
    Route::post('inbound/{inbound}/send',    [InboundController::class, 'send'])->name('wms.inbound.send');      
    Route::post('inbound/{inbound}/accept',  [InboundController::class, 'accept'])->name('wms.inbound.accept');  
    Route::post('inbound/{inbound}/confirm', [InboundController::class, 'confirm'])->name('wms.inbound.confirm');
    Route::delete('inbound/{inbound}/cancel',[InboundController::class, 'cancel'])->name('wms.inbound.cancel');  
    Route::get('inbound/{inbound}/edit',  [InboundController::class, 'edit'])->name('wms.inbound.edit');
    Route::put('inbound/{inbound}',       [InboundController::class, 'update'])->name('wms.inbound.update');
    // END Inbound

    // START ACCOUNT OMS
    Route::get('oms-staff',            [OmsStaffController::class, 'index'])->name('wms.oms-staff.index');
    Route::get('oms-staff/create',     [OmsStaffController::class, 'create'])->name('wms.oms-staff.create');
    Route::post('oms-staff',           [OmsStaffController::class, 'store'])->name('wms.oms-staff.store');
    Route::delete('oms-staff/{account}',[OmsStaffController::class, 'destroy'])->name('wms.oms-staff.destroy');
    // END ACCOUNT OMS

    // START STOCK
    Route::get('stock', [StockController::class, 'index'])->name('wms.stock.index');
    // END STOCK

    // START TRANSAKSI
    Route::resource('transaksi',TransaksiController::class)
        ->only(['index','create','store'])
        ->names('wms.transaksi'); 
    Route::get('transaksi/{transaksi}',        [TransaksiController::class,'show'])->name('wms.transaksi.show');
    Route::get('transaksi/{transaksi}/edit',   [TransaksiController::class,'edit'])->name('wms.transaksi.edit');   
    Route::put('transaksi/{transaksi}',        [TransaksiController::class,'update'])->name('wms.transaksi.update');
    Route::post('transaksi/{transaksi}/cancel',         [TransaksiController::class,'cancel'])->name('wms.transaksi.cancel');                 
    Route::post('transaksi/{transaksi}/to-ready', [TransaksiController::class,'toReady'])->name('wms.transaksi.to-ready');
    Route::post('transaksi/{transaksi}/request-cancel', [TransaksiController::class,'requestCancel'])->name('wms.transaksi.request-cancel'); 
    Route::post('transaksi/{transaksi}/request-edit',   [TransaksiController::class,'requestEdit'])->name('wms.transaksi.request-edit');     
    Route::post('transaksi/{transaksi}/to-shipped', [TransaksiController::class,'toShipped'])->name('wms.transaksi.to-shipped'); 
    Route::post('transaksi/{transaksi}/to-done',    [TransaksiController::class,'toDone'])->name('wms.transaksi.to-done');       
    // END TRANSAKSI

    // START BALANCE STOCK
    Route::get('balance-stock',          [BalanceStockController::class, 'index'])->name('wms.balance_stock.index');
    Route::get('balance-stock/{h}',      [BalanceStockController::class, 'show'])->name('wms.balance_stock.show');
    Route::post('balance-stock/{h}/approve', [BalanceStockController::class, 'approve'])->name('wms.balance_stock.approve');
    Route::post('balance-stock/{h}/reject',  [BalanceStockController::class, 'reject'])->name('wms.balance_stock.reject');
    // END BALANCE STOCK

    // START TOKO
    Route::get('toko',  [TokoController::class, 'edit'])->name('wms.toko.edit');
    Route::put('toko',  [TokoController::class, 'update'])->name('wms.toko.update');
    Route::delete('toko/logo', [TokoController::class, 'destroyLogo'])->name('wms.toko.logo.destroy');
    // END TOKO


    // SHOPEE API
    Route::get('/shopee/get-order-list', [ShopeeAuthController::class, 'getOrderList']);
    Route::get('/shopee/get-order-detail', [ShopeeAuthController::class, 'getOrderDetail']);
    Route::get('/mapping-produk', [ShopeeAuthController::class, 'mappingProdukIndex'])->name('mapping_produk.index');
    Route::post('/mapping-produk', [ShopeeAuthController::class, 'mappingProdukStore'])->name('mapping_produk.store');
    Route::delete('/mapping-produk/{id}', [ShopeeAuthController::class, 'mappingProdukDestroy'])->name('mapping_produk.destroy');
    Route::get('/mapping-produk/search-produk', [ShopeeAuthController::class, 'mappingProdukSearchProduk'])->name('mapping_produk.search_produk');
    // END SHOPEE API
});

// END WMS


// START OMS
Route::get('/oms/login',  [OmsLoginController::class, 'show'])->name('oms.login');
Route::post('/oms/login', [OmsLoginController::class, 'login'])->name('oms.login.post');
Route::post('/oms/logout', [OmsLoginController::class, 'logout'])->name('oms.logout');
Route::redirect('/oms', '/oms/login');

Route::middleware(['auth','role:oms'])->prefix('oms')->group(function () {
    
    // START INBOUND
    Route::get('inbound', [OmsInboundController::class, 'index'])->name('oms.inbound.index');
    Route::post('inbound/{inbound}/accept',  [OmsInboundController::class, 'accept'])
        ->name('oms.inbound.accept');
    Route::post('inbound/{inbound}/confirm', [OmsInboundController::class, 'confirm'])
        ->name('oms.inbound.confirm');
    Route::post('inbound/{inbound}/deny',  [OmsInboundController::class,'deny'])->name('oms.inbound.deny');
    // END INBOUND

    // START STOCK
    Route::get('stock', [OmsStockController::class, 'index'])->name('oms.stock.index');
    // END STOCK

    // START TRANSAKSI
   // List & detail (read-only untuk 'new')
    Route::get('transaksi', [OmsTransaksiController::class, 'index'])->name('oms.transaksi.index');
    Route::get('transaksi/{transaksi}', [OmsTransaksiController::class, 'show'])->name('oms.transaksi.show');
    // aksi status (sudah ada di controllermu but re-route under oms)
    Route::post('transaksi/{transaksi}/to-processing', [OmsTransaksiController::class,'toProcessing'])->name('oms.transaksi.to-processing');
    Route::post('transaksi/{transaksi}/to-shipped',    [OmsTransaksiController::class,'toShipped'])->name('oms.transaksi.to-shipped');
    Route::post('transaksi/{transaksi}/to-done',       [OmsTransaksiController::class,'toDone'])->name('oms.transaksi.to-done');
    Route::post('transaksi/{transaksi}/to-cancel',       [OmsTransaksiController::class,'toCancel'])->name('oms.transaksi.to-cancel');
    // Cetak resi single & massal
    Route::get('transaksi/{transaksi}/print-resi', [OmsTransaksiController::class, 'printResi'])->name('oms.transaksi.print-resi');
    Route::post('transaksi/print-resi-mass', [OmsTransaksiController::class, 'printResiMass'])->name('oms.transaksi.print-resi-mass');
    // END TRANSAKSI

    // START BALANCE STOCK
    Route::get('balance-stock',            [OmsBalanceStockController::class, 'index'])->name('oms.balance_stock.index');
    Route::get('balance-stock/create',     [OmsBalanceStockController::class, 'create'])->name('oms.balance_stock.create');
    Route::post('balance-stock',           [OmsBalanceStockController::class, 'store'])->name('oms.balance_stock.store');
    Route::get('balance-stock/{h}',        [OmsBalanceStockController::class, 'show'])->name('oms.balance_stock.show');
    Route::get('balance-stock/{h}/edit',   [OmsBalanceStockController::class, 'edit'])->name('oms.balance_stock.edit');
    Route::put('balance-stock/{h}',        [OmsBalanceStockController::class, 'update'])->name('oms.balance_stock.update');   
    // END BALANCE STOCK

});
