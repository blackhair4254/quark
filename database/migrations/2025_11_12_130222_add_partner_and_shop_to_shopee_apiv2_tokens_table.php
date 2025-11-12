<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shopee_apiv2_tokens', function (Blueprint $table) {
            $table->integer('partner_id')->nullable()->after('chain_link')->index();
            $table->integer('shop_id')->nullable()->after('partner_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('shopee_apiv2_tokens', function (Blueprint $table) {
            $table->dropColumn(['partner_id', 'shop_id']);
        });
    }
};
