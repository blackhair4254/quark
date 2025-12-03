<?php
namespace App\Http\Controllers\Oms;

use App\Http\Controllers\Controller;
use App\Models\TransaksiH;
use App\Models\TransaksiD;
use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class TransaksiController extends Controller
{
    private array $tabMap = [
        'all'            => null,
        'new'            => 'new',         // Belum diproses
        'ready'          => 'ready',       // Siap diproses
        'processing'     => 'processing',  // Sedang diproses
        'shipped'        => 'shipped',     // Dikirim
        'done'           => 'done',        // Selesai
        'cancel'         => 'cancel',      // Batal
    ];

    private function ensureSameChain(TransaksiH $h): void
    {
        abort_unless($h->chain_link === Auth::user()->chain_link, 403);
    }

    /**
     * Index: daftar transaksi (tab filter)
     * - default: tampilkan tab 'new' saja untuk OMS staff; tapi tetap support ?tab=ready dll
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'new');
        if (!array_key_exists($tab, $this->tabMap)) {
            $tab = 'new';
        }
        $status = $this->tabMap[$tab]; // null => all

        $chain = Auth::user()->chain_link;
        $search = trim((string) $request->query('q', ''));
        $logFilter = trim((string) $request->query('log', ''));

        // --- query utama list ---
        $query = TransaksiH::query()
            ->where('chain_link', $chain)
            ->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($logFilter !== '') {
            $query->where('jenis_logistik', $logFilter);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('invoice', 'ilike', $like)
                ->orWhere('nama_penerima', 'ilike', $like)
                ->orWhere('alamat_penerima', 'ilike', $like)
                ->orWhere('no_resi', 'ilike', $like);
            });
        }

        $perPage = 20;
        $list = $query->paginate($perPage)->withQueryString();

        // --- opsi ekspedisi dinamis per tab + search ---
        $logisticsOptions = TransaksiH::query()
            ->where('chain_link', $chain)
            ->when($status !== null, function ($qq) use ($status) {
                $qq->where('status', $status);
            })
            ->when($search !== '', function ($qq) use ($search) {
                $like = '%' . $search . '%';
                $qq->where(function ($q2) use ($like) {
                    $q2->where('invoice', 'ilike', $like)
                    ->orWhere('nama_penerima', 'ilike', $like)
                    ->orWhere('alamat_penerima', 'ilike', $like)
                    ->orWhere('no_resi', 'ilike', $like);
                });
            })
            ->whereNotNull('jenis_logistik')
            ->where('jenis_logistik', '!=', '')
            ->distinct()
            ->orderBy('jenis_logistik')
            ->pluck('jenis_logistik')
            ->toArray();

        return view('oms.transaksi.index', [
            'tab'              => $tab,
            'list'             => $list,
            'tabs'             => array_keys($this->tabMap),
            'search'           => $search,
            'logFilter'        => $logFilter,
            'logisticsOptions' => $logisticsOptions,
        ]);
    }


    /**
     * Detail view (read only fields for semua staf OMS)
     */
    public function show(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);

        $details = DB::table('transaksi_d as d')
            ->leftJoin('produk as p','p.id_produk','=','d.id_produk')
            ->selectRaw('
                d.id_produk,
                d.nama_produk,
                d.qty,
                COALESCE(NULLIF(p.harga_jual, \'\')::numeric, 0) as harga,
                d.shopee_item_name,
                d.shopee_model_name,
                d.shopee_item_id,
                d.shopee_model_id,
                d.shopee_order_item_id
            ')
            ->where('d.id_transaksi_h', $transaksi->id_transaksi)
            ->orderBy('d.nama_produk')
            ->get()
            ->map(function ($row) {
                $row->subtotal = (float)$row->harga * (int)$row->qty;
                return $row;
        });

        $totalNilai = (float) $details->sum('subtotal');
        $canAct = $transaksi->status !== 'new';

        $toko = Toko::where('chain_link', Auth::user()->chain_link)->first();
        return view('oms.transaksi.show', [
            'transaksi'  => $transaksi,
            'details'    => $details,
            'totalNilai' => $totalNilai,
            'toko'       => $toko,
            'canAct' => $canAct,
        ]);

    }

    // --- existing status mutation methods (you already have them) ---
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
    public function toCancel(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);
        $transaksi->update(['status'=>'cancel']);
        return back()->with('ok','Status diubah ke CANCEL.');
    }

    // approveCancel, approveEdit, rejectRequest ... (tetap sama)
    // ----------------------------------------------------------

    /**
     * printResi: mencetak resi untuk 1 transaksi (hanya boleh jika transaksi->status === 'processing' atau sesuai kebijakan)
     */
    public function printResi(TransaksiH $transaksi)
    {
        $this->ensureSameChain($transaksi);

        // Batasi: hanya boleh cetak jika status processing (sesuaikan jika mau izinkan ready juga)
        abort_unless(in_array($transaksi->status, ['processing', 'shipped', 'ready']), 403);

        $details = $transaksi->details()->get();

        // Jika pakai barryvdh/laravel-dompdf:
        // $pdf = PDF::loadView('oms.transaksi.print', compact('transaksi','details'));
        // return $pdf->stream("resi_{$transaksi->id_transaksi}.pdf");

        // Simpel: return view HTML yang bisa user print dari browser
        return view('oms.transaksi.print', compact('transaksi','details'));
    }

    /**
     * printResiMass: menerima array id_transaksi, generate halaman gabungan untuk cetak massal
     * - input: request->input('ids') array of transaksi id
     */
    
    public function printResiMass(Request $request)
    {
        // Ambil input mentah
        $idsInput = $request->input('ids', []);

        // Normalisasi ke array
        $ids = [];

        if (is_array($idsInput)) {
            $ids = $idsInput;
        } elseif (is_string($idsInput)) {
            // Coba decode JSON terlebih dulu
            $decoded = json_decode($idsInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ids = $decoded;
            } else {
                // Jika bukan JSON, coba comma-separated
                $trimmed = trim($idsInput);
                // hapus bracket kalau ada: ["1","2"]
                $trimmed = trim($trimmed, "[] \t\n\r");
                if ($trimmed === '') {
                    $ids = [];
                } else {
                    // split by comma (toleransi spasi)
                    $parts = preg_split('/\s*,\s*/', $trimmed);
                    $ids = $parts ?: [];
                }
            }
        } else {
            // unexpected type -> buat kosong
            $ids = [];
        }

        // Cast & filter: ambil hanya numeric -> integer positive
        $ids = array_values(array_filter(array_map(function ($v) {
            // jika object/array lagi, ignore
            if (is_array($v) || is_object($v)) return null;
            // accept numeric strings and ints
            if (is_numeric($v)) return (int) $v;
            // kadang ada kutipan di string -> hapus non-digit
            $clean = preg_replace('/\D+/', '', (string) $v);
            return $clean !== '' ? (int) $clean : null;
        }, $ids), function ($v) {
            return $v !== null && $v > 0;
        }));

        if (empty($ids)) {
            return back()->with('err', 'Pilih transaksi untuk dicetak.');
        }

        // (Opsional) batasi jumlah id supaya tidak overload
        $max = 500;
        if (count($ids) > $max) {
            return back()->with('err', "Jumlah transaksi terlalu banyak (maks {$max}). Pilih sebagian.");
        }

        try {
            $rows = TransaksiH::whereIn('id_transaksi', $ids)
                ->where('chain_link', Auth::user()->chain_link)
                ->whereIn('status', ['processing', 'shipped', 'ready'])
                ->with('details')
                ->get();
        } catch (QueryException $ex) {
            Log::error('printResiMass QueryException', ['err'=>$ex->getMessage(), 'ids'=>$ids]);
            return back()->with('err', 'Terjadi kesalahan pada database saat memproses permintaan. Silakan coba lagi.');
        } catch (\Exception $ex) {
            Log::error('printResiMass Exception', ['err'=>$ex->getMessage(), 'ids'=>$ids]);
            return back()->with('err', 'Terjadi kesalahan saat memproses permintaan. Silakan hubungi admin.');
        }

        if ($rows->isEmpty()) {
            return back()->with('err', 'Tidak ada transaksi valid untuk dicetak (cek status/akses).');
        }

        return view('oms.transaksi.print-mass', ['rows' => $rows]);
    }

}
