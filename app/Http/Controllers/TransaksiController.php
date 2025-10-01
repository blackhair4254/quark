<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Toko;
use App\Models\TransaksiH;
use App\Models\TransaksiD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{

    private array $tabMap = [
        'all'            => null,
        'new'            => 'new',         // Belum diproses // Belum Diproses (draft import)
        'ready'          => 'ready',       // Siap diproses
        'processing'     => 'processing',  // Sedang diproses
        'shipped'        => 'shipped',     // Dikirim
        'done'           => 'done',        // Selesai
        'cancel'         => 'cancel',      // Batal
    ];

    private array $logistics = [
        'Agen Shopee','Anteraja COD','Anteraja Economy','Anteraja Reguler','Anteraja Sameday',
        'Bluebird','Gojek Instant','Gojek Sameday','Grab Instant','Grab Sameday','Grab Car',
        'ID EXPRESS Reguler','ID EXPRESS Truck','ID EXPRESS Sameday','Indopaket','J&T Cargo',
        'JNE REG','JNE OKE','JNE JTR','JNE COD','JNE YES','J&T REG','Lalamove','Lazada','Paxel',
        'POS','SPX Standard','SPX Hemat','SPX COD','SPX instant','SPX Sameday','SPX POINT',
        'SICEPAT REG','SICEPAT HALU','SICEPAT GOKIL'
    ];

    private function ensureSameChain(TransaksiH $h): void
    {
        abort_unless($h->chain_link === Auth::user()->chain_link, 403);
    }

    public function show(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);

        $details = DB::table('transaksi_d as d')
            ->leftJoin('produk as p','p.id_produk','=','d.id_produk')
            ->selectRaw('d.id_produk, d.nama_produk, d.qty, COALESCE(NULLIF(p.harga_jual, \'\')::numeric, 0) as harga')
            ->where('d.id_transaksi_h', $transaksi->id_transaksi)
            ->orderBy('d.nama_produk')
            ->get()
            ->map(function ($row) {
                $row->subtotal = (float)$row->harga * (int)$row->qty;
                return $row;
            });

        $totalNilai = (float) $details->sum('subtotal');

        $toko = Toko::where('chain_link', Auth::user()->chain_link)->first();

        return view('wms.transaksi.show', [
            'trx'        => $transaksi,
            'details'    => $details,
            'totalNilai' => $totalNilai,
            'toko'       => $toko,
        ]);
    }


    public function index(Request $r)
    {
        $chain = Auth::user()->chain_link;
        $tab   = $r->query('tab','all'); 

        $q = DB::table('transaksi_h as h')
            ->where('h.chain_link', $chain)
            ->leftJoin('transaksi_d as d','d.id_transaksi_h','=','h.id_transaksi')
            ->leftJoin('produk as p', function($j){
                $j->on('p.id_produk','=','d.id_produk');
            })
            ->select([
                'h.id_transaksi','h.tanggal','h.invoice','h.pengirim','h.jenis_logistik',
                'h.no_resi','h.nama_penerima','h.status',
                DB::raw('COALESCE(SUM(d.qty),0) as total_qty'),
                DB::raw('COUNT(DISTINCT d.id_produk) as total_sku'),
                DB::raw("COALESCE(SUM(COALESCE(NULLIF(p.harga_jual,'')::numeric, 0) * d.qty), 0) as total_nilai")
            ])
            ->groupBy('h.id_transaksi','h.tanggal','h.invoice','h.pengirim','h.jenis_logistik','h.no_resi','h.nama_penerima','h.status')
            ->orderByDesc('h.id_transaksi');

        if ($this->tabMap[$tab] ?? null) {
            $q->where('h.status', $this->tabMap[$tab]);
        }

        $items = $q->paginate(12)->withQueryString();

        $tabs = [
            'all'        => 'Semua Transaksi',
            'new'        => 'Belum Diproses',
            'ready'      => 'Siap Diproses',
            'processing' => 'Sedang Diproses',
            'shipped'    => 'Dikirim',
            'done'       => 'Selesai',
            'cancel'     => 'Batal',
        ];

        return view('wms.transaksi.index', compact('items','tab','tabs'));
    }

    public function create(Request $r)
    {
        $chain = Auth::user()->chain_link;
        $q     = $r->query('q');
        $pm    = trim((string) $r->query('pm', ''));

        $pickedPairs = collect(explode(',', $pm))
            ->filter(fn($s)=>$s!=='')
            ->map(fn($pair)=>['id'=>(int)explode(':',$pair,2)[0],'qty'=>(int)(explode(':',$pair,2)[1]??0)])
            ->filter(fn($x)=>$x['id']>0 && $x['qty']>0)
            ->values();

        $caseScore = $pickedPairs->isNotEmpty()
            ? '(CASE '.$pickedPairs->map(fn($p)=>"WHEN id_produk = {$p['id']} THEN {$p['qty']}")->implode(' ').' ELSE 0 END)'
            : null;

        $produk = Produk::where('chain_link',$chain)
            ->when($q, fn($qr)=>$qr->where(fn($w)=>$w->where('nama_produk','ilike',"%{$q}%")->orWhere('sku','ilike',"%{$q}%")))
            ->when($caseScore !== null, fn($qr)=>$qr->orderByRaw("$caseScore DESC"))
            ->orderBy('nama_produk')
            ->paginate(12)->withQueryString();

        $logistics = $this->logistics;
        return view('wms.transaksi.create', compact('produk','q','logistics'));
    }


    public function store(Request $r)
    {
        $chain = Auth::user()->chain_link;

        $data = $r->validate([
            'invoice' => [
                'required','string','max:100',
                Rule::unique('transaksi_h','invoice')
                    ->where(fn($q)=>$q->where('chain_link',$chain)),
            ],
            'tanggal'          => 'required|date',
            'pengirim'         => 'required|string|max:100',
            'no_telp_pengirim' => 'required|string|max:32',
            'nama_penerima'    => 'required|string|max:120',
            'no_telp_penerima' => 'required|string|max:32',
            'alamat_penerima'  => 'required|string|max:1000',
            'jenis_logistik'   => 'nullable|string|max:100',
            'no_resi'          => 'nullable|string|max:100',
        ]);

        
        $rows = [];
        foreach ((array)$r->input('qty', []) as $idProduk => $qty) {
            $qty = (int)$qty;
            if ($qty > 0) $rows[(int)$idProduk] = $qty;
        }
        if (empty($rows)) {
            return back()->withErrors(['qty' => 'Pilih minimal 1 produk dengan qty > 0'])->withInput();
        }

        try {
            DB::transaction(function () use ($chain, $data, $rows) {
                
                $stocks = DB::table('stock')
                    ->where('chain_link', $chain)
                    ->whereIn('id_produk', array_keys($rows))
                    ->lockForUpdate()
                    ->get(['id_produk','qty']);

                $stocksById = $stocks->keyBy('id_produk');

                
                $produkMeta = Produk::where('chain_link', $chain)
                    ->whereIn('id_produk', array_keys($rows))
                    ->get(['id_produk','nama_produk','sku'])
                    ->keyBy('id_produk');

                if ($produkMeta->count() !== count($rows)) {
                    return back()->withErrors(['qty' => 'Ada produk tidak ditemukan / bukan dalam chain ini.'])->withInput();
                }

                
                $issues = [];
                foreach ($rows as $id => $need) {
                    if (!isset($stocksById[$id])) {
                        $m = $produkMeta[$id];
                        $issues[] = ($m->sku ? "{$m->sku} - " : '')."{$m->nama_produk} (baris stok belum diinisialisasi)";
                        continue;
                    }
                    $avail = (int)$stocksById[$id]->qty;
                    if ($avail < $need) {
                        $m = $produkMeta[$id];
                        $issues[] = ($m->sku ? "{$m->sku} - " : '')."{$m->nama_produk} (butuh {$need}, stok {$avail})";
                    }
                }
                if ($issues) {
                    
                    $bullets = array_map(fn($s) => "• ".$s, $issues);
                    $this->failValidation($bullets);   
                }

                
                $h = TransaksiH::create([
                    'chain_link'        => $chain,
                    'status'            => 'ready',
                    'invoice'           => $data['invoice'],
                    'pengirim'          => $data['pengirim'],
                    'no_telp_pengirim'  => $data['no_telp_pengirim'],
                    'jenis_logistik'    => $data['jenis_logistik'] ?? '',
                    'no_resi'           => $data['no_resi'] ?? '',
                    'nama_penerima'     => $data['nama_penerima'],
                    'no_telp_penerima'  => $data['no_telp_penerima'],
                    'alamat_penerima'   => $data['alamat_penerima'],
                    'tanggal'           => Carbon::parse($data['tanggal'])->toDateString(),
                ]);

                
                foreach ($rows as $id => $qty) {
                    $m = $produkMeta[$id];
                    TransaksiD::create([
                        'id_transaksi_h' => $h->id_transaksi,
                        'id_produk'      => $id,
                        'nama_produk'    => $m->nama_produk ?? 'Produk',
                        'qty'            => $qty,
                    ]);
                }

                
                foreach ($rows as $id => $qty) {
                    $avail = (int)$stocksById[$id]->qty;
                    $newQty = $avail - $qty;
                    DB::table('stock')
                        ->where('chain_link', $chain)
                        ->where('id_produk', $id)
                        ->update(['qty' => $newQty, 'updated_at' => now()]);
                }
            });
        } 
        catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } 
        catch (\Throwable $e) {
            return back()->with('err','Gagal menyimpan. Pastikan nomor invoice unik & stok cukup.')
                        ->withInput();
        }


        return redirect()->route('wms.transaksi.index')
            ->with('ok','Transaksi berhasil dibuat');
    }



    public function edit(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'ready', 403, 'Hanya transaksi siap diproses yang bisa diedit langsung.');
        
        
        $prefill = $transaksi->details()->pluck('qty','id_produk')->toArray();
        $q = request('q');
        
        $produk = Produk::where('chain_link', Auth::user()->chain_link)
            ->when($q, fn($qr)=>$qr->where(fn($w)=>$w->where('nama_produk','ilike',"%$q%")->orWhere('sku','ilike',"%$q%")))
            ->orderBy('nama_produk')->paginate(12)->withQueryString();

        $logistics = $this->logistics;

        return view('wms.transaksi.edit', compact('transaksi','produk','q','prefill','logistics'));
    }

    public function update(Request $r, TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'ready', 403);

        $data = $r->validate([
            'invoice' => [
                'required','string','max:100',
                Rule::unique('transaksi_h','invoice')
                    ->where(fn($q)=>$q->where('chain_link',$transaksi->chain_link))
                    ->ignore($transaksi->id_transaksi, 'id_transaksi'),
            ],
            'pengirim'         => 'required|string|max:100',
            'no_telp_pengirim' => 'required|string|max:32',
            'nama_penerima'    => 'required|string|max:120',
            'no_telp_penerima' => 'required|string|max:32',
            'alamat_penerima'  => 'required|string|max:1000',
            'jenis_logistik'   => 'nullable|string|max:100',
            'no_resi'          => 'nullable|string|max:100',
        ]);

        $new = [];
        foreach ((array)$r->input('qty',[]) as $id=>$qty) {
            $qty=(int)$qty; if($qty>0){ $new[(int)$id]=$qty; }
        }
        if(empty($new)) return back()->withErrors(['qty'=>'Pilih minimal 1 produk'])->withInput();

        try {
            DB::transaction(function() use ($transaksi,$data,$new){
                $old = $transaksi->details()->get()->keyBy('id_produk')->map->qty->toArray();
                $ids = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));

                $meta = Produk::where('chain_link', $transaksi->chain_link)
                ->whereIn('id_produk', $ids)
                ->get(['id_produk','nama_produk','sku'])
                ->keyBy('id_produk');

                
                $stocks = DB::table('stock')
                    ->where('chain_link', $transaksi->chain_link)
                    ->whereIn('id_produk', $ids)
                    ->lockForUpdate()
                    ->get(['id_produk','qty'])
                    ->keyBy('id_produk');

                
                $short=[];
                foreach ($ids as $id) {
                    $deltaPlus = ($new[$id] ?? 0) - ($old[$id] ?? 0); 
                    if ($deltaPlus > 0) {
                        $avail = (int)($stocks[$id]->qty ?? 0);
                        if ($avail < $deltaPlus) {
                            $need = $deltaPlus - $avail;
                            $m    = $meta[$id] ?? null;
                            $label = $m
                                ? (($m->sku ? "{$m->sku} - " : '').($m->nama_produk ?? "Produk #{$id}"))
                                : "Produk #{$id}";
                            $short[] = "{$label} butuh tambahan {$need}pcs, stok sekarang {$avail}";
                        }
                    }
                }
                if ($short) {
                    $bullets = array_map(fn($s) => "• ".$s, $short);
                    $this->failValidation(array_merge(
                        ['Stok tidak cukup untuk perubahan:'], $bullets
                    ));
                }

                
                $transaksi->update([
                    'invoice'          => $data['invoice'],
                    'pengirim'         => $data['pengirim'],
                    'no_telp_pengirim' => $data['no_telp_pengirim'],
                    'nama_penerima'    => $data['nama_penerima'],
                    'no_telp_penerima' => $data['no_telp_penerima'],
                    'alamat_penerima'  => $data['alamat_penerima'],
                    'jenis_logistik'   => $data['jenis_logistik'] ?? '',
                    'no_resi'          => $data['no_resi'] ?? '',
                ]);

                
                foreach ($ids as $id) {
                    $delta = ($new[$id] ?? 0) - ($old[$id] ?? 0); 
                    if ($delta !== 0) {
                        $avail = (int)($stocks[$id]->qty ?? 0);
                        $newQty = $avail - $delta; 
                        if ($newQty < 0) {
                            return back()->withErrors(['qty'=>'Terjadi balapan stok, coba lagi.'])->withInput();
                        }
                        DB::table('stock')
                            ->where('chain_link', $transaksi->chain_link)
                            ->where('id_produk', $id)
                            ->update(['qty'=>$newQty, 'updated_at'=>now()]);
                    }
                }

                
                $transaksi->details()->delete();
                $names = Produk::whereIn('id_produk', array_keys($new))->pluck('nama_produk','id_produk');
                foreach($new as $id=>$qty){
                    TransaksiD::create([
                        'id_transaksi_h'=>$transaksi->id_transaksi,
                        'id_produk'=>$id,
                        'nama_produk'=>$names[$id] ?? 'Produk',
                        'qty'=>$qty,
                    ]);
                }
            });
        } 
        catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } 
        catch (\Throwable $e) {
            return back()->with('err','Gagal memperbarui transaksi.')->withInput();
        }

        return redirect()->route('wms.transaksi.show',$transaksi)->with('ok','Transaksi Diupdate');
    }


    
    public function cancel(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'ready', 403, 'Hanya READY yang bisa dibatalkan.');


        DB::transaction(function() use ($transaksi){
            $details = $transaksi->details()->get(['id_produk','qty']);
            $ids = $details->pluck('id_produk')->all();

            
            $stocks = DB::table('stock')
                ->where('chain_link',$transaksi->chain_link)
                ->whereIn('id_produk',$ids)
                ->lockForUpdate()
                ->get(['id_produk','qty'])
                ->keyBy('id_produk');

            
            foreach ($details as $d) {
                $avail = (int)($stocks[$d->id_produk]->qty ?? 0);
                $newQty = $avail + (int)$d->qty;

                DB::table('stock')->updateOrInsert(
                    ['chain_link'=>$transaksi->chain_link, 'id_produk'=>$d->id_produk],
                    ['qty'=>$newQty, 'updated_at'=>now(), 'created_at'=>$stocks->has($d->id_produk)? $stocks[$d->id_produk]->created_at ?? now() : now()]
                );
            }

            $transaksi->update(['status'=>'cancel']);
        });

        return back()->with('ok','Transaksi dibatalkan');
    }
    
    public function requestCancel(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'processing', 403);
        $transaksi->update(['pending_action'=>'cancel','pending_payload'=>null]);
        return back()->with('ok','Permintaan pembatalan dikirim ke OMS.');
    }
    public function requestEdit(Request $r, TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        abort_unless($transaksi->status === 'processing', 403);

        $data = $r->validate([
            'pengirim'         => 'required|string|max:100',
            'no_telp_pengirim' => 'required|string|max:32',
            'nama_penerima'    => 'required|string|max:120',
            'no_telp_penerima' => 'required|string|max:32',
            'alamat_penerima'  => 'required|string|max:1000',
            'jenis_logistik'   => 'nullable|string|max:100',
            'no_resi'          => 'nullable|string|max:100',
        ]);
        $rows=[];
        foreach ($r->input('qty',[]) as $id=>$qty) {
            $qty=(int)$qty; if($qty>0){ $rows[(int)$id]=$qty; }
        }
        if(empty($rows)) return back()->withErrors(['qty'=>'Pilih minimal 1 produk'])->withInput();

        $payload = [
            'header'=>$data,
            'details'=>$rows, 
        ];
        $transaksi->update(['pending_action'=>'edit','pending_payload'=>$payload]);

        return redirect()->route('wms.transaksi.show',$transaksi)->with('ok','Permintaan perubahan dikirim ke OMS.');
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

        private function failValidation(array|string $messages, string $field = 'qty'): never
    {
        $list = is_array($messages) ? $messages : [$messages];
        throw ValidationException::withMessages([$field => $list]);
    }

}
