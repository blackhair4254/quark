<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    public function index(Request $r)
    {
        $chain = Auth::user()->chain_link;
        $q = $r->input('q');

        $items = Produk::with('stock')
            ->where('chain_link', $chain)
            ->when($q, function ($qr) use ($q) {
                // PostgreSQL: ilike (case-insensitive)
                $qr->where(function($s) use ($q) {
                    $s->where('nama_produk', 'ilike', "%{$q}%")
                      ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('nama_produk')
            ->paginate(12)
            ->withQueryString();

        return view('wms.produk.index', compact('items', 'q'));
    }

    public function create()
    {
        return view('wms.produk.create');
    }

    public function store(Request $r)
    {
        $chain = Auth::user()->chain_link;

        $data = $r->validate([
            'nama_produk' => 'required|string|max:255',
            'sku'         => 'nullable|string|max:100|unique:produk,sku,NULL,id_produk,chain_link,'.$chain,
            'category'    => 'required|string|max:100',
            'deskripsi'   => 'required|string',
            'berat'       => 'required|numeric|min:0',
            'foto'        => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
            'harga_beli'  => 'required|numeric|min:0',
            'harga_jual'  => 'required|numeric|min:0',
        ]);

        // Upload foto
        $path = $r->file('foto')->store('products', 'public');

        $p = Produk::create([
            'chain_link'    => $chain,
            'nama_produk'   => $data['nama_produk'],
            'sku'           => $data['sku'] ?? null,
            'category'      => $data['category'],
            'deskripsi'     => $data['deskripsi'],
            'berat'         => $data['berat'],
            'dimensi_barang'=> null,
            'foto'          => $path,
            'harga_beli'    => $data['harga_beli'],
            'harga_jual'    => $data['harga_jual'],
        ]);

        // Pastikan stok ada
        $p->stock()->firstOrCreate([], ['chain_link' => $chain, 'qty' => 0]);

        return redirect()->route('wms.produk.index')->with('ok', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk)
    {
        // keamanan multi-tenant
        abort_unless($produk->chain_link === Auth::user()->chain_link, 403);
        return view('wms.produk.edit', compact('produk'));
    }

    public function update(Request $r, Produk $produk)
    {
        abort_unless($produk->chain_link === Auth::user()->chain_link, 403);
        $chain = Auth::user()->chain_link;

        $data = $r->validate([
            'nama_produk' => 'required|string|max:255',
            'sku'         => 'nullable|string|max:100|unique:produk,sku,'.$produk->id_produk.',id_produk,chain_link,'.$chain,
            'category'    => 'required|string|max:100',
            'deskripsi'   => 'required|string',
            'berat'       => 'required|numeric|min:0',
            'foto'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'harga_beli'  => 'required|numeric|min:0',
            'harga_jual'  => 'required|numeric|min:0',
        ]);

        $update = [
            'nama_produk'   => $data['nama_produk'],
            'sku'           => $data['sku'] ?? null,
            'category'      => $data['category'],
            'deskripsi'     => $data['deskripsi'],
            'berat'         => $data['berat'],
            'harga_beli'    => $data['harga_beli'],
            'harga_jual'    => $data['harga_jual'],
        ];

        if ($r->hasFile('foto')) {
            // hapus foto lama (jika ada)
            if ($produk->foto && Storage::disk('public')->exists($produk->foto)) {
                Storage::disk('public')->delete($produk->foto);
            }
            $update['foto'] = $r->file('foto')->store('products', 'public');
        }

        $produk->update($update);

        // pastikan stok ada
        $produk->stock()->firstOrCreate([], ['chain_link' => $chain, 'qty' => 0]);

        return redirect()->route('wms.produk.index')->with('ok', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        abort_unless($produk->chain_link === Auth::user()->chain_link, 403);

        // hapus foto
        if ($produk->foto && Storage::disk('public')->exists($produk->foto)) {
            Storage::disk('public')->delete($produk->foto);
        }

        $produk->delete();
        return back()->with('ok', 'Produk dihapus.');
    }
    public function bulkDestroy(Request $r)
    {
        $chain = auth()->user()->chain_link;

        $data = $r->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        // Ambil produk milik chain yang dipilih
        $produkList = Produk::with('stock')
            ->where('chain_link', $chain)
            ->whereIn('id_produk', $data['ids'])
            ->get();

        if ($produkList->isEmpty()) {
            return back()->with('ok', 'Tidak ada produk yang cocok untuk dihapus.');
        }

        DB::transaction(function () use ($produkList) {
            foreach ($produkList as $p) {
                // hapus foto di storage
                if ($p->foto && Storage::disk('public')->exists($p->foto)) {
                    Storage::disk('public')->delete($p->foto);
                }
                // hapus stok terkait (jaga2 jika FK tidak cascade)
                if ($p->stock) {
                    $p->stock()->delete();
                }
                $p->delete();
            }
        });

        return back()->with('ok', "{$produkList->count()} produk berhasil dihapus.");
    }
}
