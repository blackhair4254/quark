<?php

namespace App\Http\Controllers\Oms;

use App\Http\Controllers\Controller;
use App\Models\TransaksiH;
use App\Models\TransaksiD;
use App\Models\Produk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    private function ensureSameChain(TransaksiH $h): void
    {
        abort_unless($h->chain_link === Auth::user()->chain_link, 403);
    }

    public function toProcessing(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'ready', 403);
        $transaksi->update(['status'=>'processing']);
        return back()->with('ok','Status diubah ke SEDANG DIPROSES.');
    }

    public function toShipped(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'processing', 403);
        $transaksi->update(['status'=>'shipped']);
        return back()->with('ok','Status diubah ke DIKIRIM.');
    }

    public function toDone(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'shipped', 403);
        $transaksi->update(['status'=>'done']);
        return back()->with('ok','Status diubah ke SELESAI.');
    }

    public function approveCancel(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'processing' && $transaksi->pending_action === 'cancel', 403);
        $transaksi->update(['status'=>'cancel','pending_action'=>null,'pending_payload'=>null]);
        return back()->with('ok','Pembatalan disetujui.');
    }

    public function approveEdit(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'processing' && $transaksi->pending_action === 'edit', 403);

        $payload = $transaksi->pending_payload ?? [];
        DB::transaction(function() use ($transaksi,$payload){
            // header
            $hdr = $payload['header'] ?? [];
            $transaksi->update([
                'pengirim'         => $hdr['pengirim'] ?? $transaksi->pengirim,
                'no_telp_pengirim' => $hdr['no_telp_pengirim'] ?? $transaksi->no_telp_pengirim,
                'nama_penerima'    => $hdr['nama_penerima'] ?? $transaksi->nama_penerima,
                'no_telp_penerima' => $hdr['no_telp_penerima'] ?? $transaksi->no_telp_penerima,
                'alamat_penerima'  => $hdr['alamat_penerima'] ?? $transaksi->alamat_penerima,
                'jenis_logistik'   => $hdr['jenis_logistik'] ?? $transaksi->jenis_logistik,
                'no_resi'          => $hdr['no_resi'] ?? $transaksi->no_resi,
                'pending_action'   => null,
                'pending_payload'  => null,
            ]);
            // details
            $map = $payload['details'] ?? []; // id => qty
            if (!empty($map)) {
                $transaksi->details()->delete();
                $ids = array_map('intval', array_keys($map));
                $names = Produk::whereIn('id_produk',$ids)->pluck('nama_produk','id_produk');
                foreach ($map as $id=>$qty){
                    TransaksiD::create([
                        'id_transaksi_h'=>$transaksi->id_transaksi,
                        'id_produk'=>(int)$id,
                        'nama_produk'=>$names[$id] ?? 'Produk',
                        'qty'=>(int)$qty,
                    ]);
                }
            }
        });
        return back()->with('ok','Perubahan disetujui.');
    }

    public function rejectRequest(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->pending_action !== null, 403);
        $transaksi->update(['pending_action'=>null,'pending_payload'=>null]);
        return back()->with('ok','Permintaan ditolak.');
    }
}
