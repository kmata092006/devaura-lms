<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the system-account flag and marks default personnel accounts.
     */
    public function up(): void
    {
        // Add the flag only when it is missing from older imports.
        if (!Schema::hasColumn('library_personnel', 'is_system_account')) {
            Schema::table('library_personnel', function (Blueprint $table) {
                $table->boolean('is_system_account')
                    ->default(false)
                    ->after('password_hash');
            });
        }

        // Mark default accounts when they already exist in seeded data.
        try {
            DB::table('library_personnel')
                ->whereIn('username', ['admin', 'staff', 'student staff'])
                ->update(['is_system_account' => true]);
        } catch (\Exception $e) {
            // Leave the flag unset if the personnel table has no seed rows yet.
        }
    }

    /**
     * Removes the system-account flag when rolling the migration back.
     */
    public function down(): void
    {
        if (Schema::hasColumn('library_personnel', 'is_system_account')) {
            Schema::table('library_personnel', function (Blueprint $table) {
                $table->dropColumn('is_system_account');
            });
        }
    }
};
