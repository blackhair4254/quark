<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('transaksi_h', function (Blueprint $t) {
            $t->string('pending_action', 24)->nullable()->index(); // 'edit' | 'cancel'
            $t->json('pending_payload')->nullable();               // data usulan
        });
    }
    public function down(): void {
        Schema::table('transaksi_h', function (Blueprint $t) {
            $t->dropColumn(['pending_action','pending_payload']);
        });
    }
};
