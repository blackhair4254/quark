<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\BalanceStockH;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BalanceStockController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab  = $request->query('status', 'submitted');

        $list = BalanceStockH::forChain($user->chain_link)
            ->when($tab !== 'all', fn($q) => $q->where('status', $tab))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $statuses = ['submitted','approved','rejected','all'];

        return view('wms.balance_stock.index', compact('list','tab','statuses'));
    }

    public function show(BalanceStockH $h)
    {
        $user = Auth::user();
        abort_unless($h->chain_link === $user->chain_link, 403);

        $h->load('details.produk');

        return view('wms.balance_stock.show', ['header'=>$h]);
    }

    public function approve(BalanceStockH $h)
    {
        $user = Auth::user();
        abort_unless($h->chain_link === $user->chain_link, 403);
        abort_unless($h->status === 'submitted', 403);

        DB::transaction(function () use ($h, $user) {
            // pastikan details & produk ter-load
            $h->load('details');

            foreach ($h->details as $row) {
                // stok sistem baru = stok fisik
                $newQty = (int) $row->qty_fisik;

                // update / buat record stok
                Stock::updateOrCreate(
                    [
                        'chain_link' => $h->chain_link,
                        'id_produk'  => $row->id_produk,
                    ],
                    [
                        'qty'        => $newQty,
                    ]
                );
            }

            // update status header
            $h->update([
                'status'      => 'approved',
                'approved_by' => $user->id_account,
                'approved_at' => now(),
            ]);
        });

        return back()->with('ok','Balance stock berhasil di-approve & stok sudah diadjust.');
    }


    public function reject(BalanceStockH $h, Request $request)
    {
        $user = Auth::user();
        abort_unless($h->chain_link === $user->chain_link, 403);
        abort_unless($h->status === 'submitted', 403);

        $h->update([
            'status'      => 'rejected',
            'approved_by' => $user->id_account,
            'approved_at' => now(),
        ]);

        return back()->with('ok','Balance stock ditolak.');
    }
}
