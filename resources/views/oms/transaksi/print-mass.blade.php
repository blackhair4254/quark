<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="{{ asset('quark.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('quark.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('quark.svg') }}">
    <title>Cetak Resi Massal</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        /* A6 portrait ~ 105 x 148 mm */
        @page {
            size: A6 portrait;
            margin: 0;
        }

        .sheet {
            width: 105mm;
            height: 148mm;
            padding: 4mm;
            page-break-after: always;
        }

        .resi-frame {
            border: 1px solid #000;
            height: 100%;
            padding: 4mm;
            display: flex;
            flex-direction: column;
        }

        .center { text-align: center; }
        .right  { text-align: right; }

        .exp-name {
            font-size: 18px;
            font-weight: bold;
        }

        .dash-line {
            border-bottom: 1px dashed #000;
            margin: 2mm 0 3mm;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 4mm;
            margin-bottom: 2mm;
        }

        .box-inline {
            border: 1px solid #000;
            padding: 1mm 2mm;
            font-weight: bold;
            font-size: 10px;
        }

        .half {
            width: 50%;
            font-size: 10px;
        }

        .bold { font-weight: bold; }
        .mt-2 { margin-top: 2mm; }
        .mt-3 { margin-top: 3mm; }

        /* BARCODE */
        .barcode-wrap-big,
        .barcode-wrap-small {
            text-align: center;
        }

        .barcode-wrap-big svg,
        .barcode-wrap-small svg {
            width: 100%;
            max-height: 22mm;
        }

        .barcode-text {
            font-size: 10px;
            margin-top: 1mm;
            letter-spacing: 1px;
        }

        .barcode-placeholder {
            font-size: 12px;
            font-weight: bold;
        }

        /* tabel produk */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
            font-size: 10px;
        }
        table.items th,
        table.items td {
            padding: 1mm 1mm;
        }
        table.items thead th {
            border-bottom: 1px dashed #000;
        }
        table.items tbody td {
            vertical-align: top;
        }
        table.items td.num {
            text-align: right;
            width: 6mm;
        }

        .note {
            margin-top: 3mm;
            font-size: 9px;
        }

        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body>
@foreach($rows as $transaksi)
    @php
        $orderNo  = $transaksi->invoice ?? $transaksi->order_sn ?? $transaksi->id_transaksi;
        $noResi   = $transaksi->no_resi ?: '';
        $expedisi = $transaksi->jenis_logistik ?: 'NAMA EKSPEDISI';

        $berat = $transaksi->berat_total
            ?? $transaksi->total_berat
            ?? $transaksi->total_berat_gram
            ?? $transaksi->berat
            ?? null;
    @endphp

    <div class="sheet">
        <div class="resi-frame">
            {{-- NAMA EKSPEDISI --}}
            <div class="center exp-name">
                {{ strtoupper($expedisi) }}
            </div>

            <div class="dash-line"></div>

            {{-- NO PESANAN & NO RESI --}}
            <div class="row">
                <div class="box-inline">
                    No Pesanan : {{ $orderNo }}
                </div>
                <div class="box-inline">
                    No Resi : {{ $noResi !== '' ? $noResi : '-' }}
                </div>
            </div>

            {{-- BARCODE BESAR DARI NO RESI (SVG) --}}
            <div class="barcode-wrap-big">
                @if($noResi !== '')
                    {!! DNS1D::getBarcodeSVG($noResi, 'C128', 1.75, 50, 'black', false) !!}
                    <div class="barcode-text">{{ $noResi }}</div>
                @else
                    <div class="barcode-placeholder">NO RESI BELUM TERSEDIA</div>
                @endif
            </div>

            <div class="dash-line"></div>

            {{-- PENERIMA & PENGIRIM --}}
            <div class="row mt-2">
                <div class="half">
                    <span class="bold">Penerima :</span> {{ $transaksi->nama_penerima }}<br>
                    <span class="bold">Telp :</span> {{ $transaksi->no_telp_penerima }}<br>
                    {{ $transaksi->alamat_penerima }}
                </div>
                <div class="half">
                    <span class="bold">Pengirim :</span> {{ $transaksi->pengirim ?? 'Admin WMS' }}<br>
                    @if(!empty($transaksi->kota_pengirim))
                        {{ $transaksi->kota_pengirim }}
                    @endif
                </div>
            </div>

            {{-- BERAT & BARCODE KECIL --}}
            <div class="row mt-3">
                <div class="half">
                    <div>
                        <span class="bold">Berat :</span>
                        {{ $berat ? $berat.' gr' : 'nnnnn gr' }}
                    </div>
                    <div>
                        <span class="bold">No Pesanan :</span> {{ $orderNo }}
                    </div>
                </div>
                <div class="half center">
                    @if($noResi !== '')
                        <div class="barcode-wrap-small">
                            {!! DNS1D::getBarcodeSVG($noResi, 'C128', 0.8, 35, 'black', true) !!}
                        </div>
                    @else
                        <div class="bold">NO RESI BELUM TERSEDIA</div>
                    @endif
                </div>
            </div>

            {{-- TABEL PRODUK --}}
            <table class="items mt-3">
                <thead>
                <tr>
                    <th style="width:6mm;">No</th>
                    <th>Nama Produk</th>
                    <th style="width:18mm;">SKU</th>
                    <th style="width:22mm;">Variasi</th>
                    <th style="width:8mm;" class="right">QTY</th>
                </tr>
                </thead>
                <tbody>
                @foreach($transaksi->details as $idx => $d)
                    @php
                        $sku = $d->sku
                            ?? $d->sku_produk
                            ?? $d->kode_sku
                            ?? '-';
                        
                        $variasi = $d->variasi
                            ?? $d->shopee_model_name
                            ?? $d->keterangan
                            ?? '-';
                    @endphp
                    <tr>
                        <td class="num">{{ $idx + 1 }}</td>
                        <td>{{ $d->nama_produk }}</td>
                        <td>{{ $sku }}</td>
                        <td>{{ $variasi }}</td>
                        <td class="right">{{ $d->qty }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="note">
                Note : - (Jika tidak ada)
            </div>
        </div>
    </div>
@endforeach

<script>
    window.print();
</script>
</body>
</html>
