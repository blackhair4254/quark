<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_h', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->string('chain_link')->index();
            $table->string('status',24);
            $table->string('invoice');
            $table->string('pengirim');
            $table->string('no_telp_pengirim',32);
            $table->string('jenis_logistik');
            $table->string('no_resi');
            $table->string('nama_penerima');
            $table->string('no_telp_penerima',32);
            $table->text('alamat_penerima');
            $table->date('tanggal');
            $table->timestamps();

            $table->unique(['chain_link','invoice']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_h');
    }
};
