<?php

namespace Database\Seeders;

use App\Models\Produk;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Storage;

class ProdukSeeder extends Seeder
{
    // public function run(): void
    // {
    //     $p = Produk::updateOrCreate(
    //         ['chain_link' => 'CHAIN-001', 'sku' => 'SKU-001'],
    //         [
    //             'nama_produk'    => 'Contoh Lem',
    //             'category'       => 'Adhesive',
    //             'deskripsi'      => 'Lem serbaguna',
    //             'berat'          => 250,
    //             'dimensi_barang' => '10x4x3 cm',
    //             'foto'           => 'images/adhesive.png',
    //             'harga_beli'     => 10000,
    //             'harga_jual'     => 15000,
    //         ]
    //     );

    //     // stok awal (kalau belum otomatis dibuat: lihat langkah 3)
    //     $p->stock()->updateOrCreate([], ['chain_link' => $p->chain_link, 'qty' => 100]);
    // }
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $chain = 'CHAIN-001';

        // 1 gambar untuk semua produk
        $fotoPath = 'products/adhesive.png';

        // Pastikan file ada di storage publik
        if (!Storage::disk('public')->exists($fotoPath)) {
            $source = public_path('images/adhesive.png'); // <- biasanya kamu punya ini
            if (is_file($source)) {
                Storage::disk('public')->put($fotoPath, file_get_contents($source));
            } else {
                // fallback: placeholder 1x1 px agar tidak broken
                $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
                Storage::disk('public')->put($fotoPath, base64_decode($pngBase64));
            }
        }

        $kategori = ['Adhesive','Home Appliance','Rooftop','Plumbing'];

        for ($i = 2; $i <= 21; $i++) {
            $nama = ucfirst($faker->unique()->words(2, true));
            $sku  = 'SKU-'.str_pad((string)$i, 3, '0', STR_PAD_LEFT);

            $p = Produk::create([
                'chain_link'     => $chain,
                'nama_produk'    => $nama,
                'sku'            => $sku,
                'category'       => $kategori[array_rand($kategori)],
                'deskripsi'      => $faker->sentence(12),
                'berat'          => $faker->numberBetween(100, 5000), // gram
                'dimensi_barang' => $faker->numberBetween(5,50).'x'.$faker->numberBetween(5,50).'x'.$faker->numberBetween(5,50).' cm',
                'foto'           => $fotoPath, // <-- satu gambar untuk semua
                'harga_beli'     => $faker->numberBetween(10000, 200000),
                'harga_jual'     => $faker->numberBetween(20000, 350000),
            ]);

            // stok
            $p->stock()->firstOrCreate([], [
                'chain_link' => $chain,
                'qty'        => $faker->numberBetween(0, 200),
            ]);
        }
    }
}
