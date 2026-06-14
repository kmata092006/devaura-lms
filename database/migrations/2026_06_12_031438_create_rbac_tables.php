<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the ERD-compliant RBAC tables if they don't already exist from the SQL import.
     */
    public function up(): void
    {
        // Store role names used by personnel accounts.
        if (!Schema::hasTable('personnel_role')) {
            Schema::create('personnel_role', function (Blueprint $table) {
                $table->id('personnel_role_id');
                $table->string('personnel_role_name', 100)->unique();
            });
        }

        // Store module permission names shown in the role matrix.
        if (!Schema::hasTable('permission')) {
            Schema::create('permission', function (Blueprint $table) {
                $table->id('permission_id');
                $table->string('permission_name', 100)->unique();
            });
        }

        // Link roles to permissions using a composite primary key.
        if (!Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->unsignedBigInteger('personnel_role_id');
                $table->unsignedBigInteger('permission_id');

                $table->primary(['personnel_role_id', 'permission_id']);

                $table->foreign('personnel_role_id')->references('personnel_role_id')->on('personnel_role')->onDelete('cascade');
                $table->foreign('permission_id')->references('permission_id')->on('permission')->onDelete('cascade');
            });
        }
    }

    /**
     * Drops the RBAC tables in dependency order.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permission');
        Schema::dropIfExists('personnel_role');
    }
};
