<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('account', function (Blueprint $table) {
            $table->id('id_account');
            $table->string('nama_pengguna');
            $table->string('email_pengguna')->unique();
            $table->string('password');
            $table->string('chain_link')->index();
            $table->string('role')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account');
    }
};
