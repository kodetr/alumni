<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alumni')) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table): void {
            if (! Schema::hasColumn('alumni', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('alamat');
            }

            if (! Schema::hasColumn('alumni', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (! Schema::hasColumn('alumni', 'geocoded_at')) {
                $table->timestamp('geocoded_at')->nullable()->after('longitude');
            }

            if (! Schema::hasColumn('alumni', 'geocoding_source')) {
                $table->string('geocoding_source', 50)->nullable()->after('geocoded_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('alumni')) {
            return;
        }

        Schema::table('alumni', function (Blueprint $table): void {
            $columnsToDrop = [];

            if (Schema::hasColumn('alumni', 'geocoding_source')) {
                $columnsToDrop[] = 'geocoding_source';
            }

            if (Schema::hasColumn('alumni', 'geocoded_at')) {
                $columnsToDrop[] = 'geocoded_at';
            }

            if (Schema::hasColumn('alumni', 'longitude')) {
                $columnsToDrop[] = 'longitude';
            }

            if (Schema::hasColumn('alumni', 'latitude')) {
                $columnsToDrop[] = 'latitude';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
