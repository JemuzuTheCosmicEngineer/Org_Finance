<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $roleAdded = false;

        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('Viewer');
            });
            $roleAdded = true;
        }

        if (!Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status', 20)->default('Active');
            });
        }

        // The oldest existing account becomes the Admin so nobody gets locked out.
        if ($roleAdded) {
            $first = DB::table('users')->min('id');
            if ($first) {
                DB::table('users')->where('id', $first)->update(['role' => 'Admin', 'status' => 'Active']);
            }
        }
    }

    public function down(): void
    {
        foreach (['role', 'status'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                Schema::table('users', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
