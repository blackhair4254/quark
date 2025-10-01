<?php

namespace App\Http\Controllers;

use App\Models\InboundH;
use App\Models\InboundD;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InboundController extends Controller
{
    public function index(Request $r)
    {
        $chain = Auth::user()->chain_link;
        $tab = $r->query('tab', 'all'); // all|draft|sent|accept|confirm|denied

        $q = InboundH::query()
            ->where('chain_link', $chain)
            ->withCount('details as total_sku')
            ->withSum('details as total_qty', 'qty')
            ->orderByDesc('id_inbound');

        if ($tab !== 'all') {
            $q->where('status', $tab);
        }

        $items = $q->paginate(15)->withQueryString();
        return view('wms.inbound.index', compact('items','tab'));
    }

    public function create(Request $r)
    {
        $chain = Auth::user()->chain_link;
        $q     = $r->query('q');

        // pm = "id:qty,id:qty,..."
        $pm = trim((string) $r->query('pm', ''));
        $pickedPairs = collect(explode(',', $pm))
            ->filter(fn($s) => $s !== '')
            ->map(function ($pair) {
                [$id, $qty] = array_pad(explode(':', $pair, 2), 2, null);
                return ['id' => (int) $id, 'qty' => (int) $qty];
            })
            ->filter(fn($x) => $x['id'] > 0 && $x['qty'] > 0)
            ->values();

        // siapkan CASE untuk skor qty, tapi JANGAN dipakai jika kosong
        $caseScore = null;
        if ($pickedPairs->isNotEmpty()) {
            $parts = $pickedPairs->map(fn($p) => "WHEN id_produk = {$p['id']} THEN {$p['qty']}");
            // pakai CASE … END dibungkus () biar aman
            $caseScore = '(CASE ' . $parts->implode(' ') . ' ELSE 0 END)';
        }

        $produk = Produk::where('chain_link', $chain)
            ->when($q, function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('nama_produk', 'ilike', "%{$q}%")
                    ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            // urutkan picked (qty>0) ke atas hanya kalau ada pm
            ->when($caseScore !== null, fn($qr) => $qr->orderByRaw("$caseScore DESC"))
            ->orderBy('nama_produk')
            ->paginate(12)
            ->withQueryString();

        return view('wms.inbound.create', compact('produk', 'q'));
    }





    public function store(Request $r)
    {
        $chain = Auth::user()->chain_link;

        // Ambil qty dari form: qty[id_produk] => jumlah
        $raw = $r->input('qty', []);
        $rows = [];
        foreach ($raw as $idProduk => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $rows[] = ['id_produk' => (int)$idProduk, 'qty' => $qty];
            }
        }

        if (empty($rows)) {
            return back()->withErrors(['qty' => 'Pilih minimal 1 produk dengan qty > 0'])->withInput();
        }

        $dataHeader = $r->validate([
            'tanggal_inbound' => 'nullable|date',
            'no_resi'         => 'nullable|string|max:100',
            'deskripsi'       => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($rows, $dataHeader, $chain) {
            $header = InboundH::create([
                'chain_link'      => $chain,
                'no_resi'         => $dataHeader['no_resi'] ?? null,
                'status'          => 'draft',
                'deskripsi'       => $dataHeader['deskripsi'] ?? null,
                'tanggal_inbound' => isset($dataHeader['tanggal_inbound'])
                    ? Carbon::parse($dataHeader['tanggal_inbound'])
                    : Carbon::now(),
                'total_qty'       => 0,
                'total_barang'    => 0,
            ]);

            $totalQty = 0;
            foreach ($rows as $r) {
                InboundD::create([
                    'id_inbound_h' => $header->id_inbound,
                    'id_produk'    => $r['id_produk'],
                    'qty'          => $r['qty'],
                    'chain_link'   => $chain,
                ]);
                $totalQty += $r['qty'];
            }

            $header->update([
                'total_qty'    => $totalQty,
                'total_barang' => count($rows),
            ]);
        });

        return redirect()->route('wms.inbound.index')->with('ok', 'Inbound draft dibuat.');
    }

    public function show(InboundH $inbound)
    {
        // opsional detail page
        $this->authorizeInbound($inbound);
        $inbound->load(['details.produk']);
        return view('wms.inbound.show', compact('inbound'));
    }

    public function send(InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if ($inbound->status !== 'draft') {
            return back()->withErrors(['status' => 'Hanya draft yang bisa dikirim.']);
        }
        $inbound->update(['status' => 'sent']);
        return back()->with('ok', "Inbound {$inbound->id_inbound} dikirim.");
    }

    public function accept(InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if ($inbound->status !== 'sent') {
            return back()->withErrors(['status' => 'Hanya yang berstatus sent yang dapat diterima.']);
        }
        $inbound->update(['status' => 'accept']);
        return back()->with('ok', 'Inbound diterima (accept).');
    }

    public function confirm(InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if ($inbound->status !== 'accept') {
            return back()->withErrors(['status' => 'Hanya yang berstatus accept yang dapat dikonfirmasi.']);
        }

        // (opsional) Pada confirm, tambahkan stok.
        DB::transaction(function () use ($inbound) {
            $inbound->load('details.produk.stock');

            foreach ($inbound->details as $d) {
                $prod = $d->produk;
                if (!$prod) continue;

                // pastikan stock row ada
                $stock = $prod->stock()->firstOrCreate([], [
                    'chain_link' => $inbound->chain_link,
                    'qty'        => 0,
                ]);

                $stock->qty += $d->qty;
                $stock->save();
            }

            $inbound->update(['status' => 'confirm']);
        });

        return back()->with('ok', 'Inbound dikonfirmasi dan stok bertambah.');
    }

    public function cancel(InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if ($inbound->status !== 'draft') {
            return back()->withErrors(['status' => 'Hanya draft yang bisa dibatalkan.']);
        }

        DB::transaction(function () use ($inbound) {
            $inbound->details()->delete();
            $inbound->delete();
        });

        return redirect()->route('wms.inbound.index', ['tab' => 'draft'])
            ->with('ok', 'Inbound draft dibatalkan.');
    }

    private function authorizeInbound(InboundH $inbound)
    {
        abort_unless($inbound->chain_link === Auth::user()->chain_link, 403);
    }

    public function edit(Request $r, InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if (!in_array($inbound->status, ['draft','denied'], true)) {
            return back()->withErrors(['status' => 'Hanya inbound draft atau denied yang bisa diedit.']);
        }

        $chain = Auth::user()->chain_link;
        $q     = $r->query('q');

        // Ambil qty tersimpan di DB (detail inbound)
        $inbound->load('details');
        $dbMap = $inbound->details->mapWithKeys(fn($d) => [$d->id_produk => (int)$d->qty])->all();

        // Ambil perubahan sementara dari query (?pm=id:qty,...) bila ada (dari localStorage lintas halaman)
        $pm = trim((string) $r->query('pm', ''));
        $pickedPairs = collect(explode(',', $pm))
            ->filter(fn($s) => $s !== '')
            ->map(function ($pair) {
                [$id, $qty] = array_pad(explode(':', $pair, 2), 2, null);
                return ['id' => (int) $id, 'qty' => (int) $qty];
            })
            ->filter(fn($x) => $x['id'] > 0 && $x['qty'] > 0);

        // Gabungkan: prioritas perubahan sementara (pm) > DB
        $merged = $dbMap;
        foreach ($pickedPairs as $p) { $merged[$p['id']] = $p['qty']; }
        // Siapkan CASE score untuk mengangkat produk qty>0 ke atas
        $caseScore = null;
        if (!empty($merged)) {
            $parts = collect($merged)->filter(fn($qty) => $qty > 0)
                ->map(fn($qty, $id) => "WHEN id_produk = ".((int)$id)." THEN ".((int)$qty));
            if ($parts->isNotEmpty()) {
                $caseScore = '(CASE '.$parts->implode(' ').' ELSE 0 END)';
            }
        }

        $produk = Produk::where('chain_link', $chain)
            ->when($q, function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('nama_produk', 'ilike', "%{$q}%")
                    ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            ->when($caseScore !== null, fn($qr) => $qr->orderByRaw("$caseScore DESC"))
            ->orderBy('nama_produk')
            ->paginate(12)
            ->withQueryString();

        // Kirim map qty awal (dari DB) ke view agar input terisi & localStorage bisa di-seed
        $initialQty = $dbMap;

        return view('wms.inbound.edit', compact('inbound', 'produk', 'q', 'initialQty'));
    }

    public function update(Request $r, InboundH $inbound)
    {
        $this->authorizeInbound($inbound);
        if (!in_array($inbound->status, ['draft','denied'], true)) {
            return back()->withErrors(['status' => 'Hanya inbound draft atau denied yang bisa diedit.']);
        }

        $dataHeader = $r->validate([
            'tanggal_inbound' => 'nullable|date',
            'no_resi'         => 'nullable|string|max:100',
            'deskripsi'       => 'nullable|string|max:500',
        ]);

        $raw = $r->input('qty', []);
        $rows = [];
        foreach ($raw as $idProduk => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) $rows[] = ['id_produk'=>(int)$idProduk,'qty'=>$qty];
        }
        if (empty($rows)) {
            return back()->withErrors(['qty' => 'Pilih minimal 1 produk dengan qty > 0'])->withInput();
        }

        DB::transaction(function () use ($inbound, $dataHeader, $rows) {
            $inbound->update([
                'no_resi'         => $dataHeader['no_resi'] ?? null,
                'deskripsi'       => $dataHeader['deskripsi'] ?? null,
                'tanggal_inbound' => isset($dataHeader['tanggal_inbound'])
                    ? Carbon::parse($dataHeader['tanggal_inbound'])
                    : $inbound->tanggal_inbound,
                // ✅ kalau sebelumnya denied, pastikan kembali ke draft
                'status'          => 'draft',
            ]);

            $inbound->details()->delete();

            $totalQty = 0;
            foreach ($rows as $r) {
                InboundD::create([
                    'id_inbound_h' => $inbound->id_inbound,
                    'id_produk'    => $r['id_produk'],
                    'qty'          => $r['qty'],
                    'chain_link'   => $inbound->chain_link,
                ]);
                $totalQty += $r['qty'];
            }

            $inbound->update([
                'total_qty'    => $totalQty,
                'total_barang' => count($rows),
            ]);
        });

        return redirect()->route('wms.inbound.index', ['tab' => 'draft'])
            ->with('ok', "Inbound #{$inbound->id_inbound} diperbarui , status kembali menjadi DRAFT.");
    }

}
