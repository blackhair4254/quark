<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ubah 'date' -> 'timestamp without time zone'
        DB::statement("
            ALTER TABLE inbound_h
            ALTER COLUMN tanggal_inbound
            TYPE timestamp without time zone
            USING tanggal_inbound::timestamp
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE inbound_h
            ALTER COLUMN tanggal_inbound
            TYPE date
            USING tanggal_inbound::date
        ");
    }
};
