<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('toko', function (Blueprint $table) {
            // Kolom bank (opsional)
            $table->string('bank_name', 100)->nullable()->after('website');
            $table->string('bank_account_no', 50)->nullable()->after('bank_name');
            $table->string('bank_account_name', 100)->nullable()->after('bank_account_no');

            // Path logo (kalau belum ada, aman untuk ditambahkan lagi)
            if (!Schema::hasColumn('toko', 'logo_path')) {
                $table->string('logo_path', 255)->nullable()->after('website');
            }
        });
    }

    public function down(): void
    {
        Schema::table('toko', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_no',
                'bank_account_name',
            ]);

            if (Schema::hasColumn('toko', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }
};
