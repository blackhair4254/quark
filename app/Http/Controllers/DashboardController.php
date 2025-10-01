<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\TransaksiD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(Request $r)
    {
        $chain = Auth::user()->chain_link ?? null;

        $bulan = $r->input('bulan', now()->format('Y-m'));
        $start = Carbon::parse($bulan.'-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $q = $r->input('q');

        // cari produk pertama yang cocok
        $produk = Produk::where('chain_link', $chain)
            ->when($q, function ($qr) use ($q) {
                // PostgreSQL: ilike untuk case-insensitive
                $qr->where(function($q2) use ($q){
                    $q2->where('nama_produk', 'ilike', "%{$q}%")
                       ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            ->first();

        $produkNama = $produk->nama_produk ?? null;

        $rows = TransaksiD::selectRaw('transaksi_h.tanggal::date AS tanggal, SUM(transaksi_d.qty) AS qty')
            ->join('transaksi_h', 'transaksi_h.id_transaksi', '=', 'transaksi_d.id_transaksi_h')
            ->where('transaksi_h.chain_link', $chain)
            ->whereBetween('transaksi_h.tanggal', [$start, $end])
            ->when($produk, fn($q) => $q->where('transaksi_d.id_produk', $produk->id_produk))
            ->groupBy('transaksi_h.tanggal')
            ->orderBy('transaksi_h.tanggal')
            ->get();

        $totalQty = (int) ($rows->sum('qty') ?? 0);
        $harian = $rows->map(fn($r) => ['tanggal' => $r->tanggal, 'qty' => (int)$r->qty])->toArray();

        return view('dashboard', compact('totalQty', 'produkNama', 'harian'));
    }

}
