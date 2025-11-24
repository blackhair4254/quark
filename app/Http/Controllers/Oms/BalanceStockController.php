<?php

namespace App\Http\Controllers\Oms;

use App\Http\Controllers\Controller;
use App\Models\BalanceStockH;
use App\Models\BalanceStockD;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BalanceStockController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $tab   = $request->query('status', 'submitted');

        $list = BalanceStockH::with('creator')
                ->forChain($user->chain_link)
                ->when($tab !== 'all', fn($q) => $q->where('status', $tab))
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString();

        $statuses = ['all','submitted','approved','rejected'];

        return view('oms.balance_stock.index', compact('list','tab','statuses'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $q    = $request->query('q');

        $produk = Produk::where('chain_link', $user->chain_link)
            ->when($q, function($qBuilder) use ($q) {
                $qBuilder->where(function($x) use ($q) {
                    $x->where('nama_produk','ILIKE',"%{$q}%")
                      ->orWhere('sku','ILIKE',"%{$q}%");
                });
            })
            ->orderBy('nama_produk')
            ->paginate(20)
            ->withQueryString();

        return view('oms.balance_stock.form', [
            'mode'   => 'create',
            'header' => null,
            'details'=> [],
            'produk' => $produk,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $this->validateRequest($request);
        DB::beginTransaction();
        try {
            $kode = 'ADJ-' . now()->format('ymd-His') . '-' . rand(100,999);

            $header = BalanceStockH::create([
                'kode_adjustment' => $kode,
                'chain_link'      => $user->chain_link,
                'gudang'          => $data['gudang'],    // boleh null
                'status'          => 'submitted',
                'created_by'      => $user->id_account,
            ]);

            $this->syncDetails($header, $data['items']);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->route('oms.balance_stock.show', $header)
            ->with('ok', 'Balance stock berhasil diajukan.');
    }

    public function update(BalanceStockH $h, Request $request)
    {
        $this->authorizeHeaderOms($h);

        if ($h->status !== 'submitted') {
            abort(403, 'Hanya data dengan status SUBMITTED yang bisa diedit.');
        }

        $data = $this->validateRequest($request);

        DB::beginTransaction();
        try {
            $h->update([
                'gudang' => $data['gudang'], // boleh null
            ]);

            $this->syncDetails($h, $data['items']);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->route('oms.balance_stock.show', $h)
            ->with('ok','Balance stock berhasil diupdate.');
    }


    public function show(BalanceStockH $h)
    {
        $this->authorizeHeaderOms($h);

        $h->load(['details.produk', 'creator', 'approver']);

        return view('oms.balance_stock.show', ['header'=>$h, 'mode'=>'show']);
    }

    public function edit(BalanceStockH $h, Request $request)
    {
        $this->authorizeHeaderOms($h);

        if ($h->status !== 'submitted') {
            abort(403, 'Hanya data dengan status SUBMITTED yang bisa diedit.');
        }

        $user = Auth::user();
        $q    = $request->query('q');

        // load semua detail dulu
        $h->load('details.produk');

        // ambil id_produk yg memang ada di detail balance ini
        $idProdukDetail = $h->details->pluck('id_produk');

        // HANYA produk yang ada di detail yang ditampilkan saat edit
        $produkQuery = Produk::where('chain_link', $user->chain_link)
            ->whereIn('id_produk', $idProdukDetail);

        if ($q) {
            $produkQuery->where(function($x) use ($q) {
                $x->where('nama_produk','ILIKE',"%{$q}%")
                ->orWhere('sku','ILIKE',"%{$q}%");
            });
        }

        // tanpa paginate -> semua baris ada di satu halaman
        $produk = $produkQuery->orderBy('nama_produk')->get();

        return view('oms.balance_stock.form', [
            'mode'   => 'edit',
            'header' => $h,
            'details'=> $h->details,
            'produk' => $produk,
        ]);
    }


    // ===== Helper =====

    protected function validateRequest(Request $request): array
    {
        // Ambil raw items
        $rawItems = $request->input('items', []);

        if (!is_array($rawItems) || empty($rawItems)) {
            throw ValidationException::withMessages([
                'items' => ['Isi stok fisik minimal 1 produk.'],
            ]);
        }

        $filtered = [];

        foreach ($rawItems as $key => $row) {
            // kalau qty_fisik tidak ada / null / string kosong -> abaikan
            if (
                !array_key_exists('qty_fisik', $row) ||
                $row['qty_fisik'] === '' ||
                $row['qty_fisik'] === null
            ) {
                continue;
            }

            $qtySystem = (int)($row['qty_system'] ?? 0);
            $qtyFisik  = (int)$row['qty_fisik'];

            // kalau sama, tidak perlu disimpan
            if ($qtyFisik === $qtySystem) {
                continue;
            }

            $row['qty_system'] = $qtySystem;
            $row['qty_fisik']  = $qtyFisik;

            $filtered[$key] = $row;
        }

        if (empty($filtered)) {
            throw ValidationException::withMessages([
                'items' => ['Minimal satu produk harus memiliki selisih stok.'],
            ]);
        }

        // Validasi setelah difilter
        $validator = Validator::make(
            ['items' => $filtered],
            [
                'items'                     => ['required','array'],
                'items.*.id_produk'         => ['required','integer','exists:produk,id_produk'],
                'items.*.qty_system'        => ['required','integer','min:0'],
                'items.*.qty_fisik'         => ['required','integer','min:0'],
                'items.*.keterangan'        => ['nullable','string'],
            ]
        );

        $validated = $validator->validate();

        // Gudang boleh null
        $validated['gudang'] = $request->input('gudang');

        return $validated;
    }


    protected function syncDetails(BalanceStockH $header, array $items): void
    {
        // hapus dulu semuanya lalu insert lagi
        $header->details()->delete();

        foreach ($items as $row) {
            $qtySystem = (int) $row['qty_system'];
            $qtyFisik  = (int) $row['qty_fisik'];
            $selisih   = $qtyFisik - $qtySystem;

            $tipe = 'sama';
            if ($selisih > 0) $tipe = 'lebih';
            elseif ($selisih < 0) $tipe = 'kurang';

            BalanceStockD::create([
                'id_adjustment' => $header->id_adjustment,
                'id_produk'     => $row['id_produk'],
                'qty_system'    => $qtySystem,
                'qty_fisik'     => $qtyFisik,
                'selisih'       => $selisih,
                'tipe_selisih'  => $tipe,
                'keterangan'    => $row['keterangan'] ?? null,
            ]);
        }
    }

    protected function authorizeHeaderOms(BalanceStockH $h): void
    {
        $user = Auth::user();
        abort_unless($h->chain_link === $user->chain_link, 403);
        abort_unless($h->created_by == $user->id_account, 403);
    }
}
