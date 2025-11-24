<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="{{ asset('quark.svg') }}">
  <link rel="icon" type="image/x-icon" href="{{ asset('quark.svg') }}">
  <link rel="apple-touch-icon" href="{{ asset('quark.svg') }}">
  <title>Resi #{{ $transaksi->id_transaksi }}</title>
  <style>
    /* style minimal agar rapi saat print */
    body { font-family: Arial, sans-serif; font-size: 12px; }
    .box { border:1px solid #000; padding:8px; margin-bottom:10px; }
  </style>
</head>
<body>
  <div class="box">
    <h2>Resi Pengiriman</h2>
    <p>No Transaksi: {{ $transaksi->id_transaksi }}</p>
    <p>Penerima: {{ $transaksi->nama_penerima }} - {{ $transaksi->no_telp_penerima }}</p>
    <p>Alamat: {{ $transaksi->alamat_penerima }}</p>
    <p>Jenis Logistik: {{ $transaksi->jenis_logistik }}</p>
    <p>No Resi: {{ $transaksi->no_resi }}</p>
    <hr>
    <h4>Detail Produk</h4>
    <ul>
      @foreach($details as $d)
        <li>{{ $d->nama_produk }} x {{ $d->qty }}</li>
      @endforeach
    </ul>
  </div>
  <script>window.print();</script>
</body>
</html>
