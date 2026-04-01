<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Carbon;

class AlumniMaintenanceService
{
    /**
     * @return array{enabled:bool,ends_at:?string,remaining_seconds:?int}
     */
    public function status(): array
    {
        $setting = IntegrationSetting::query()->find(1);

        if (! $setting || ! $setting->maintenance_enabled) {
            return [
                'enabled' => false,
                'ends_at' => null,
                'remaining_seconds' => null,
            ];
        }

        $endsAt = $setting->maintenance_ends_at instanceof Carbon
            ? $setting->maintenance_ends_at
            : ($setting->maintenance_ends_at ? Carbon::parse($setting->maintenance_ends_at) : null);

        if ($endsAt && $endsAt->isPast()) {
            $this->deactivate();

            return [
                'enabled' => false,
                'ends_at' => null,
                'remaining_seconds' => null,
            ];
        }

        return [
            'enabled' => true,
            'ends_at' => $endsAt?->toIso8601String(),
            'remaining_seconds' => $endsAt ? max(0, now()->diffInSeconds($endsAt, false)) : null,
        ];
    }

    public function isActive(): bool
    {
        return $this->status()['enabled'];
    }

    public function activate(int $durationMinutes): void
    {
        $minutes = max(1, $durationMinutes);
        $endsAt = now()->addMinutes($minutes);

        $this->persist([
            'maintenance_enabled' => true,
            'maintenance_ends_at' => $endsAt,
        ]);
    }

    public function deactivate(): void
    {
        $this->persist([
            'maintenance_enabled' => false,
            'maintenance_ends_at' => null,
        ]);
    }

    /**
     * @param  array{maintenance_enabled:bool,maintenance_ends_at:mixed}  $payload
     */
    private function persist(array $payload): void
    {
        $setting = IntegrationSetting::query()->find(1);

        if (! $setting) {
            $setting = new IntegrationSetting;
            $setting->id = 1;
            $setting->endpoint = (string) config('integration.menu_data_endpoint', 'http://127.0.0.1:8001/api/integration/alumni/menu-data');
            $setting->api_key = (string) config('integration.api_key', '');
        }

        $setting->maintenance_enabled = $payload['maintenance_enabled'];
        $setting->maintenance_ends_at = $payload['maintenance_ends_at'];
        $setting->save();
    }
}
