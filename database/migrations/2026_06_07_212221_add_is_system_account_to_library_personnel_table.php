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
        // 1. First, ensure the column exists
        if (!Schema::hasColumn('library_personnel', 'is_system_account')) {
            Schema::table('library_personnel', function (Blueprint $table) {
                $table->boolean('is_system_account')
                    ->default(false)
                    ->after('password_hash');
            });
        }

        // 2. Wrap the update in a try-catch to prevent a crash if table is empty 
        // or if records don't exist yet
        try {
            DB::table('library_personnel')
                ->whereIn('username', ['admin', 'staff', 'student staff'])
                ->update(['is_system_account' => true]);
        } catch (\Exception $e) {
            // Silently ignore if update fails (e.g., table empty)
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
