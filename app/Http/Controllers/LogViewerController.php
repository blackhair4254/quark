<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogViewerController extends Controller
{

    protected array $files = [
        'cron'                   => 'cron.log',
        'delete-transaksi-shopee'=> 'DeleteTransaksiShopee.log',
        'get-order-detail-shopee'=> 'GetOrderDetailShopee.log',
    ];

    public function index()
    {
        return view('wms.logs.index', [
            'files' => $this->files,
        ]);
    }


    public function show(string $key)
    {
        if (!isset($this->files[$key])) {
            abort(404);
        }

        $logRoot = dirname(base_path()); 

        $fileName = $this->files[$key];
        $fullPath = $logRoot . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($fullPath)) {
            $content = "File log tidak ditemukan: {$fullPath}";
        } else {
            $content = file_get_contents($fullPath);
        }

        return view('wms.logs.show', [
            'key'      => $key,
            'filename' => $fileName,
            'fullPath' => $fullPath,
            'content'  => $content,
        ]);
    }
}
