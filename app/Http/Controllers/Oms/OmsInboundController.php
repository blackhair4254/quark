<?php

namespace App\Http\Controllers\Oms;

use App\Http\Controllers\Controller;
use App\Models\InboundH;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OmsInboundController extends Controller
{
    public function index(Request $r)
    {
        $chain = Auth::user()->chain_link;
        $tab = $r->query('tab', 'all'); // all|sent|accept|confirm|denied

        $q = InboundH::where('chain_link', $chain)->orderByDesc('id_inbound');
        if ($tab !== 'all') $q->where('status', $tab);

        $items = $q->paginate(15)->withQueryString();
        return view('oms.inbound.index', compact('items','tab'));
    }

    public function accept(InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if ($inbound->status !== 'sent') {
            return back()->withErrors(['status' => 'Hanya yang berstatus sent yang dapat diterima.']);
        }
        $inbound->update(['status' => 'accept']);
        return back()->with('ok',"Inbound #{$inbound->id_inbound} diterima.");
    }

    public function confirm(InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if ($inbound->status !== 'accept') {
            return back()->withErrors(['status' => 'Hanya yang berstatus accept yang dapat dikonfirmasi.']);
        }

        DB::transaction(function () use ($inbound) {
            $inbound->load('details.produk.stock');
            foreach ($inbound->details as $d) {
                $stock = $d->produk?->stock()->firstOrCreate([], [
                    'chain_link' => $inbound->chain_link,
                    'qty'        => 0,
                ]);
                if ($stock) { $stock->qty += $d->qty; $stock->save(); }
            }
            $inbound->update(['status'=>'confirm']);
        });

        return back()->with('ok',"Inbound #{$inbound->id_inbound} berhasil dikonfirmasi.");
    }

    public function deny(Request $r, InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if ($inbound->status !== 'accept') {
            return back()->withErrors(['status' => 'Hanya inbound berstatus accept yang dapat di-deny.']);
        }
        // opsional: simpan alasan ke deskripsi (tambahkan field sendiri bila mau khusus)
        if ($note = trim((string)$r->input('note'))) {
            $inbound->update(['status'=>'denied','deskripsi'=> $inbound->deskripsi ? $inbound->deskripsi."\nDENY: ".$note : "DENY: ".$note ]);
        } else {
            $inbound->update(['status'=>'denied']);
        }
        return back()->with('ok',"Inbound #{$inbound->id_inbound} DENIED");
    }

    private function authorizeInbound(InboundH $inbound)
    {
        abort_unless($inbound->chain_link === Auth::user()->chain_link, 403);
    }
}
