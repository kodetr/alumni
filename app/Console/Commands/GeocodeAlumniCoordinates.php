<?php

namespace App\Console\Commands;

use App\Models\Alumni;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

#[Signature('app:geocode-alumni-coordinates {--limit=300 : Maksimum jumlah alumni diproses} {--force : Proses ulang meski koordinat sudah terisi}')]
#[Description('Isi koordinat alumni dari alamat menggunakan Nominatim OpenStreetMap')]
class GeocodeAlumniCoordinates extends Command
{
    private const GEOCODING_ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    private const GEOCODING_CACHE_PREFIX = 'mapping:geocode:';

    private const GEOCODING_SUCCESS_TTL_DAYS = 30;

    private const GEOCODING_FAILURE_TTL_HOURS = 12;

    /**
     * @var array<string, array{lat: float, lng: float}|null>
     */
    private array $runtimeCache = [];

    public function handle(): int
    {
        if (! Schema::hasTable('alumni')) {
            $this->error('Tabel alumni tidak ditemukan.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('alumni', 'latitude') || ! Schema::hasColumn('alumni', 'longitude')) {
            $this->error('Kolom latitude/longitude belum tersedia. Jalankan migration terlebih dahulu.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $query = Alumni::query()
            ->select(['id', 'nim', 'nama', 'alamat', 'latitude', 'longitude'])
            ->whereNotNull('alamat')
            ->where('alamat', '!=', '');

        if (! $force) {
            $query->where(function ($inner): void {
                $inner->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        $targets = $query->orderBy('id')->limit($limit)->get();
        $total = $targets->count();

        if ($total === 0) {
            $this->info('Tidak ada data alumni yang perlu diproses.');

            return self::SUCCESS;
        }

        $this->info("Memproses {$total} data alumni...");
        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $updated = 0;
        $failed = 0;

        foreach ($targets as $alumni) {
            $address = trim((string) $alumni->alamat);

            if ($address === '') {
                $failed++;
                $progress->advance();

                continue;
            }

            $geocoded = $this->geocodeAddress($address);

            if ($geocoded === null) {
                $failed++;
                $progress->advance();

                continue;
            }

            Alumni::withoutTimestamps(function () use ($alumni, $geocoded): void {
                $payload = [
                    'latitude' => round($geocoded['lat'], 7),
                    'longitude' => round($geocoded['lng'], 7),
                ];

                if (Schema::hasColumn('alumni', 'geocoded_at')) {
                    $payload['geocoded_at'] = now();
                }

                if (Schema::hasColumn('alumni', 'geocoding_source')) {
                    $payload['geocoding_source'] = 'address_geocoding';
                }

                $alumni->forceFill($payload)->saveQuietly();
            });

            $updated++;
            $progress->advance();
            usleep(250000);
        }

        $progress->finish();
        $this->newLine(2);

        $this->info("Selesai. Berhasil: {$updated}, gagal: {$failed}, total: {$total}.");

        return self::SUCCESS;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function geocodeAddress(string $address): ?array
    {
        $normalizedAddress = $this->normalizeAddress($address);

        if ($normalizedAddress === '') {
            return null;
        }

        if (array_key_exists($normalizedAddress, $this->runtimeCache)) {
            return $this->runtimeCache[$normalizedAddress];
        }

        $cacheKey = self::GEOCODING_CACHE_PREFIX.sha1($normalizedAddress);
        $cached = Cache::get($cacheKey, '__missing__');

        if ($cached !== '__missing__') {
            $resolved = is_array($cached) ? $this->parseGeocodingPayload($cached) : null;
            $this->runtimeCache[$normalizedAddress] = $resolved;

            return $resolved;
        }

        $headers = [
            'User-Agent' => $this->geocodingUserAgent(),
        ];

        $appUrl = config('app.url');

        if (is_string($appUrl) && trim($appUrl) !== '') {
            $headers['Referer'] = trim($appUrl);
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->withHeaders($headers)
                ->get(self::GEOCODING_ENDPOINT, [
                    'q' => $normalizedAddress.' Indonesia',
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'limit' => 1,
                    'countrycodes' => 'id',
                ]);
        } catch (\Throwable) {
            $this->runtimeCache[$normalizedAddress] = null;

            return null;
        }

        if (! $response->ok()) {
            Cache::put($cacheKey, false, now()->addHours(self::GEOCODING_FAILURE_TTL_HOURS));
            $this->runtimeCache[$normalizedAddress] = null;

            return null;
        }

        $results = $response->json();

        if (! is_array($results) || ! isset($results[0]) || ! is_array($results[0])) {
            Cache::put($cacheKey, false, now()->addHours(self::GEOCODING_FAILURE_TTL_HOURS));
            $this->runtimeCache[$normalizedAddress] = null;

            return null;
        }

        $resolved = $this->parseGeocodingPayload($results[0]);

        if ($resolved === null) {
            Cache::put($cacheKey, false, now()->addHours(self::GEOCODING_FAILURE_TTL_HOURS));
            $this->runtimeCache[$normalizedAddress] = null;

            return null;
        }

        Cache::put($cacheKey, $resolved, now()->addDays(self::GEOCODING_SUCCESS_TTL_DAYS));
        $this->runtimeCache[$normalizedAddress] = $resolved;

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{lat: float, lng: float}|null
     */
    private function parseGeocodingPayload(array $payload): ?array
    {
        $lat = filter_var($payload['lat'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        $lng = filter_var($payload['lon'] ?? $payload['lng'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

        if (! is_float($lat) || ! is_float($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    private function normalizeAddress(string $address): string
    {
        $normalized = strtolower(trim($address));

        return preg_replace('/\s+/', ' ', $normalized) ?? '';
    }

    private function geocodingUserAgent(): string
    {
        $appName = trim((string) config('app.name', 'AlumniApp'));
        $appUrl = trim((string) config('app.url', 'http://localhost'));

        return $appName.' Geocoding/1.0 ('.$appUrl.')';
    }
}
