<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// KAL-70: instrument_id FK had no explicit ON DELETE action (silent RESTRICT by default).
// Make it explicit so intent is clear and schema diffs are unambiguous.
return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL only — SQLite used in unit tests has no named FK constraints.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE calibrations DROP CONSTRAINT IF EXISTS calibrations_instrument_id_foreign');
        DB::statement('ALTER TABLE calibrations ADD CONSTRAINT calibrations_instrument_id_foreign FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE RESTRICT');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE calibrations DROP CONSTRAINT IF EXISTS calibrations_instrument_id_foreign');
        DB::statement('ALTER TABLE calibrations ADD CONSTRAINT calibrations_instrument_id_foreign FOREIGN KEY (instrument_id) REFERENCES instruments(id)');
    }
};
