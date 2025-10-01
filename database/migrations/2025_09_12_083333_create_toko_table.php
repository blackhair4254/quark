<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('toko', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Boundary per organisasi
            $table->string('chain_link', 100)->unique();

            // Identitas & alamat
            $table->string('nama_toko', 150);
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('negara', 100)->default('Indonesia');
            $table->string('kode_pos', 12)->nullable();

            // Opsional (disarankan)
            $table->string('no_telp', 32)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 150)->nullable();
            $table->string('logo_path', 255)->nullable(); // simpan path logo jika perlu

            // Preferensi toko (berguna utk invoice)
            $table->string('currency', 3)->default('IDR');
            $table->string('timezone', 50)->default('Asia/Jakarta');

            // Fondasi auto-numbering invoice (belum dipakai sekarang)
            $table->string('invoice_prefix', 10)->default('INV');
            $table->unsignedBigInteger('invoice_counter')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko');
    }
};
