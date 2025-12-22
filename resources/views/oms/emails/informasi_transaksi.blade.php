
Mohon input transaksi {{ $transaksi->invoice }}

Nama penerima : {{ $transaksi->nama_penerima }}
Ekspedisi     : {{ $transaksi->jenis_logistik }}
No Resi       : {{ $transaksi->no_resi }}
Alamat        : {{ $transaksi->alamat_penerima }}
No Telp       : {{ $transaksi->no_telp_penerima }}

Barang :
@foreach($details as $i => $d)
{{ $i+1 }}. {{ $d->nama_produk }} - {{ $d->shopee_model_name ?? '-' }}, qty {{ $d->qty }}
@endforeach
