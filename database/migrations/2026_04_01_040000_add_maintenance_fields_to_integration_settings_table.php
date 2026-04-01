<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integration_settings')) {
            return;
        }

        $hasMaintenanceEnabled = Schema::hasColumn('integration_settings', 'maintenance_enabled');
        $hasMaintenanceEndsAt = Schema::hasColumn('integration_settings', 'maintenance_ends_at');

        if ($hasMaintenanceEnabled && $hasMaintenanceEndsAt) {
            return;
        }

        Schema::table('integration_settings', function (Blueprint $table) use ($hasMaintenanceEnabled, $hasMaintenanceEndsAt): void {
            if (! $hasMaintenanceEnabled) {
                $table->boolean('maintenance_enabled')->default(false)->after('api_key');
            }

            if (! $hasMaintenanceEndsAt) {
                $table->timestamp('maintenance_ends_at')->nullable()->after('maintenance_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_settings')) {
            return;
        }

        $dropColumns = [];

        if (Schema::hasColumn('integration_settings', 'maintenance_ends_at')) {
            $dropColumns[] = 'maintenance_ends_at';
        }

        if (Schema::hasColumn('integration_settings', 'maintenance_enabled')) {
            $dropColumns[] = 'maintenance_enabled';
        }

        if ($dropColumns === []) {
            return;
        }

        Schema::table('integration_settings', function (Blueprint $table) use ($dropColumns): void {
            $table->dropColumn($dropColumns);
        });
    }
};
