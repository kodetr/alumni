<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integration_settings') || Schema::hasColumn('integration_settings', 'default_alumni_permissions')) {
            return;
        }

        Schema::table('integration_settings', function (Blueprint $table): void {
            $table->json('default_alumni_permissions')->nullable()->after('maintenance_ends_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_settings') || ! Schema::hasColumn('integration_settings', 'default_alumni_permissions')) {
            return;
        }

        Schema::table('integration_settings', function (Blueprint $table): void {
            $table->dropColumn('default_alumni_permissions');
        });
    }
};
