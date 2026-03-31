<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'is_blocked')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_blocked')->default(false)->after('role');
            $table->index(['role', 'is_blocked']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'is_blocked')) {
            return;
        }

        if (Schema::hasIndex('users', 'users_role_is_blocked_index')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_role_is_blocked_index');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_blocked');
        });
    }
};
