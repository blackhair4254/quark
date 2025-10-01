<?php

namespace App\Http\Controllers\Oms;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function index(Request $r)
    {
        [$items, $q] = $this->query($r);
        return view('oms.stock.index', compact('items','q'));
    }

    private function query(Request $r): array
    {
        $chain = Auth::user()->chain_link;
        $q     = trim((string)$r->query('q', ''));

        $items = Produk::with('stock')
            ->where('chain_link', $chain)
            ->when($q, function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('nama_produk', 'ilike', "%{$q}%")
                      ->orWhere('sku', 'ilike', "%{$q}%")
                      ->orWhere('category', 'ilike', "%{$q}%"); // kalau field-nya 'category'
                });
            })
            ->orderBy('nama_produk')
            ->paginate(20)
            ->withQueryString();

        return [$items, $q];
    }
}



