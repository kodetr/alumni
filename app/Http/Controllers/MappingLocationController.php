<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MappingLocationController extends Controller
{
    private const GEOCODING_ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    private const GEOCODING_CACHE_PREFIX = 'mapping:geocode:';

    private const GEOCODING_SUCCESS_TTL_DAYS = 30;

    private const GEOCODING_FAILURE_TTL_HOURS = 12;

    private const GEOCODING_MAX_LOOKUPS_PER_REQUEST = 10;

    /**
     * @var array<int, array{key: string, label: string, lat: float, lng: float, aliases: array<int, string>}>
     */
    private const CITY_CATALOG = [
        ['key' => 'jakarta', 'label' => 'DKI Jakarta', 'lat' => -6.2088, 'lng' => 106.8456, 'aliases' => ['jakarta', 'dki jakarta']],
        ['key' => 'bandung', 'label' => 'Bandung', 'lat' => -6.9175, 'lng' => 107.6191, 'aliases' => ['bandung', 'kota bandung']],
        ['key' => 'bogor', 'label' => 'Bogor', 'lat' => -6.5971, 'lng' => 106.8060, 'aliases' => ['bogor', 'kota bogor']],
        ['key' => 'depok', 'label' => 'Depok', 'lat' => -6.4025, 'lng' => 106.7942, 'aliases' => ['depok', 'kota depok']],
        ['key' => 'bekasi', 'label' => 'Bekasi', 'lat' => -6.2383, 'lng' => 106.9756, 'aliases' => ['bekasi', 'kota bekasi']],
        ['key' => 'tangerang', 'label' => 'Tangerang', 'lat' => -6.1783, 'lng' => 106.6319, 'aliases' => ['tangerang', 'kota tangerang']],
        ['key' => 'semarang', 'label' => 'Semarang', 'lat' => -6.9667, 'lng' => 110.4167, 'aliases' => ['semarang', 'kota semarang']],
        ['key' => 'yogyakarta', 'label' => 'Yogyakarta', 'lat' => -7.7956, 'lng' => 110.3695, 'aliases' => ['yogyakarta', 'jogja']],
        ['key' => 'surabaya', 'label' => 'Surabaya', 'lat' => -7.2575, 'lng' => 112.7521, 'aliases' => ['surabaya', 'kota surabaya']],
        ['key' => 'malang', 'label' => 'Malang', 'lat' => -7.9666, 'lng' => 112.6326, 'aliases' => ['malang', 'kota malang']],
        ['key' => 'denpasar', 'label' => 'Denpasar', 'lat' => -8.6705, 'lng' => 115.2126, 'aliases' => ['denpasar', 'bali']],
        ['key' => 'medan', 'label' => 'Medan', 'lat' => 3.5952, 'lng' => 98.6722, 'aliases' => ['medan', 'kota medan']],
        ['key' => 'palembang', 'label' => 'Palembang', 'lat' => -2.9761, 'lng' => 104.7754, 'aliases' => ['palembang', 'kota palembang']],
        ['key' => 'pekanbaru', 'label' => 'Pekanbaru', 'lat' => 0.5333, 'lng' => 101.4500, 'aliases' => ['pekanbaru', 'kota pekanbaru']],
        ['key' => 'padang', 'label' => 'Padang', 'lat' => -0.9471, 'lng' => 100.4172, 'aliases' => ['padang', 'kota padang']],
        ['key' => 'batam', 'label' => 'Batam', 'lat' => 1.1301, 'lng' => 104.0530, 'aliases' => ['batam', 'kota batam']],
        ['key' => 'pontianak', 'label' => 'Pontianak', 'lat' => -0.0263, 'lng' => 109.3425, 'aliases' => ['pontianak', 'kota pontianak']],
        ['key' => 'banjarmasin', 'label' => 'Banjarmasin', 'lat' => -3.3186, 'lng' => 114.5944, 'aliases' => ['banjarmasin', 'kota banjarmasin']],
        ['key' => 'balikpapan', 'label' => 'Balikpapan', 'lat' => -1.2379, 'lng' => 116.8529, 'aliases' => ['balikpapan', 'kota balikpapan']],
        ['key' => 'makassar', 'label' => 'Makassar', 'lat' => -5.1477, 'lng' => 119.4327, 'aliases' => ['makassar', 'ujung pandang']],
        ['key' => 'manado', 'label' => 'Manado', 'lat' => 1.4748, 'lng' => 124.8421, 'aliases' => ['manado', 'kota manado']],
    ];

    /**
     * @var array<string, array{lat: float, lng: float, city: ?string}|null>
     */
    private array $runtimeGeocodingCache = [];

    private int $geocodingLookupCount = 0;

    private ?bool $coordinateColumnsAvailable = null;

    private ?bool $geocodedAtColumnAvailable = null;

    private ?bool $geocodingSourceColumnAvailable = null;

    public function locations(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $year = trim($request->string('year')->toString());
        $city = trim($request->string('city')->toString());
        $perPage = (int) $request->integer('per_page', 25);
        $perPageOptions = [25, 50, 100];

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        if (! Schema::hasTable('alumni')) {
            return Inertia::render('Mapping/Locations', [
                'alumni' => $this->emptyPaginator($request, $perPage),
                'stats' => $this->emptyStats(),
                'topCities' => [],
                'markers' => [],
                'cityOptions' => [],
                'yearOptions' => [],
                'filters' => [
                    'search' => $search,
                    'year' => $year,
                    'city' => $city,
                    'per_page' => $perPage,
                ],
                'perPageOptions' => $perPageOptions,
            ]);
        }

        $selectColumns = [
            'id',
            'nim',
            'nama',
            'jurusan',
            'tahun_lulus',
            'alamat',
            'tempat_lahir',
            'updated_at',
        ];

        if ($this->hasCoordinateColumns()) {
            $selectColumns[] = 'latitude';
            $selectColumns[] = 'longitude';
        }

        if ($this->hasGeocodingSourceColumn()) {
            $selectColumns[] = 'geocoding_source';
        }

        $query = Alumni::query()->select($selectColumns);

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery
                    ->where('nama', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('jurusan', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhere('tempat_lahir', 'like', "%{$search}%");
            });
        }

        if (preg_match('/^\d{4}$/', $year) === 1) {
            $query->where('tahun_lulus', (int) $year);
        }

        $alumniCollection = $query
            ->orderByDesc('tahun_lulus')
            ->orderBy('nama')
            ->get()
            ->map(function (Alumni $alumni): array {
                $location = $this->resolveLocation($alumni);

                return [
                    'id' => $alumni->id,
                    'nim' => $alumni->nim,
                    'nama' => $alumni->nama,
                    'jurusan' => $alumni->jurusan,
                    'tahun_lulus' => $alumni->tahun_lulus,
                    'alamat' => $alumni->alamat,
                    'tempat_lahir' => $alumni->tempat_lahir,
                    'updated_at' => $alumni->updated_at,
                    'location_key' => $location['key'],
                    'location_label' => $location['label'],
                    'location_source' => $location['source'],
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'has_coordinates' => $location['lat'] !== null && $location['lng'] !== null,
                ];
            });

        $cityAggregation = $this->aggregateCities($alumniCollection);
        $cityOptions = $cityAggregation
            ->sortBy('label')
            ->values()
            ->map(fn (array $item): array => [
                'key' => $item['key'],
                'label' => $item['label'],
                'count' => $item['count'],
            ])
            ->all();

        if ($city !== '' && ! collect($cityOptions)->contains(fn (array $item): bool => $item['key'] === $city)) {
            $city = '';
        }

        if ($city !== '') {
            $alumniCollection = $alumniCollection
                ->filter(fn (array $item): bool => $item['location_key'] === $city)
                ->values();
        }

        $totalAlumni = $alumniCollection->count();
        $withLocation = $alumniCollection->filter(fn (array $item): bool => $item['location_label'] !== null)->count();
        $withCoordinates = $alumniCollection->filter(fn (array $item): bool => $item['has_coordinates'])->count();
        $uniqueCities = $alumniCollection
            ->filter(fn (array $item): bool => $item['location_label'] !== null)
            ->pluck('location_label')
            ->unique()
            ->count();
        $withoutLocation = $totalAlumni - $withLocation;

        $topCities = $this->aggregateCities($alumniCollection)
            ->sortByDesc('count')
            ->values()
            ->map(function (array $item) use ($totalAlumni): array {
                return [
                    ...$item,
                    'percentage' => $totalAlumni > 0
                        ? round(($item['count'] / $totalAlumni) * 100, 1)
                        : 0,
                ];
            })
            ->take(10)
            ->values()
            ->all();

        $markers = $this->aggregateCities($alumniCollection)
            ->sortByDesc('count')
            ->values()
            ->map(function (array $item) use ($totalAlumni): array {
                return [
                    ...$item,
                    'percentage' => $totalAlumni > 0
                        ? round(($item['count'] / $totalAlumni) * 100, 1)
                        : 0,
                ];
            })
            ->filter(fn (array $item): bool => $item['lat'] !== null && $item['lng'] !== null)
            ->values()
            ->all();

        $paginator = $this->paginateCollection($alumniCollection, $request, $perPage);

        $yearOptions = Alumni::query()
            ->whereNotNull('tahun_lulus')
            ->select('tahun_lulus')
            ->distinct()
            ->orderByDesc('tahun_lulus')
            ->pluck('tahun_lulus')
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();

        return Inertia::render('Mapping/Locations', [
            'alumni' => $paginator,
            'stats' => [
                'totalAlumni' => $totalAlumni,
                'withLocation' => $withLocation,
                'withCoordinates' => $withCoordinates,
                'withoutLocation' => $withoutLocation,
                'uniqueCities' => $uniqueCities,
                'coveragePercentage' => $totalAlumni > 0
                    ? round(($withCoordinates / $totalAlumni) * 100, 1)
                    : 0,
            ],
            'topCities' => $topCities,
            'markers' => $markers,
            'cityOptions' => $cityOptions,
            'yearOptions' => $yearOptions,
            'filters' => [
                'search' => $search,
                'year' => $year,
                'city' => $city,
                'per_page' => $perPage,
            ],
            'perPageOptions' => $perPageOptions,
        ]);
    }

    /**
     * @return array{key: ?string, label: ?string, source: ?string, lat: ?float, lng: ?float}
     */
    private function resolveLocation(Alumni $alumni): array
    {
        $address = is_string($alumni->alamat) ? trim($alumni->alamat) : '';
        $birthPlace = is_string($alumni->tempat_lahir) ? trim($alumni->tempat_lahir) : '';
        $locationText = strtolower(trim($address.' '.$birthPlace));
        $fallbackCity = $this->extractCityFromAddress($address);
        $catalogCity = $this->matchCatalogCity($locationText);
        $labelFallback = $catalogCity['label']
            ?? $fallbackCity
            ?? ($birthPlace !== '' ? $birthPlace : null);

        if ($this->hasCoordinateColumns()) {
            $storedLatitude = is_numeric($alumni->latitude) ? (float) $alumni->latitude : null;
            $storedLongitude = is_numeric($alumni->longitude) ? (float) $alumni->longitude : null;

            if ($storedLatitude !== null && $storedLongitude !== null) {
                $label = $labelFallback ?? 'Lokasi Tersimpan';

                return [
                    'key' => $catalogCity['key'] ?? Str::slug($label),
                    'label' => $label,
                    'source' => $this->resolveStoredSourceLabel($alumni),
                    'lat' => $storedLatitude,
                    'lng' => $storedLongitude,
                ];
            }
        }

        if ($address !== '') {
            $geocodedAddress = $this->geocodeAddress($address);

            if ($geocodedAddress !== null) {
                $label = $catalogCity['label']
                    ?? $geocodedAddress['city']
                    ?? $fallbackCity
                    ?? ($birthPlace !== '' ? $birthPlace : 'Lokasi Alamat');

                return [
                    'key' => $catalogCity['key'] ?? Str::slug($label),
                    'label' => $label,
                    'source' => 'Alamat (Geocoding)',
                    'lat' => $geocodedAddress['lat'],
                    'lng' => $geocodedAddress['lng'],
                ];

                $this->persistCoordinates($alumni, $geocodedAddress['lat'], $geocodedAddress['lng'], 'address_geocoding');

                return $resolved;
            }
        }

        if ($catalogCity !== null) {
            $resolved = [
                'key' => $catalogCity['key'],
                'label' => $catalogCity['label'],
                'source' => $address !== '' ? 'Alamat (Katalog Kota)' : 'Tempat Lahir',
                'lat' => $catalogCity['lat'],
                'lng' => $catalogCity['lng'],
            ];

            if ($address !== '') {
                $this->persistCoordinates($alumni, $catalogCity['lat'], $catalogCity['lng'], 'catalog_city');
            }

            return $resolved;
        }

        if ($birthPlace !== '') {
            return [
                'key' => Str::slug($birthPlace),
                'label' => $birthPlace,
                'source' => 'Tempat Lahir',
                'lat' => null,
                'lng' => null,
            ];
        }

        if ($fallbackCity !== null) {
            return [
                'key' => Str::slug($fallbackCity),
                'label' => $fallbackCity,
                'source' => 'Alamat',
                'lat' => null,
                'lng' => null,
            ];
        }

        return [
            'key' => null,
            'label' => null,
            'source' => null,
            'lat' => null,
            'lng' => null,
        ];
    }

    private function hasCoordinateColumns(): bool
    {
        if ($this->coordinateColumnsAvailable !== null) {
            return $this->coordinateColumnsAvailable;
        }

        $this->coordinateColumnsAvailable = Schema::hasColumn('alumni', 'latitude')
            && Schema::hasColumn('alumni', 'longitude');

        return $this->coordinateColumnsAvailable;
    }

    private function hasGeocodedAtColumn(): bool
    {
        if ($this->geocodedAtColumnAvailable !== null) {
            return $this->geocodedAtColumnAvailable;
        }

        $this->geocodedAtColumnAvailable = Schema::hasColumn('alumni', 'geocoded_at');

        return $this->geocodedAtColumnAvailable;
    }

    private function hasGeocodingSourceColumn(): bool
    {
        if ($this->geocodingSourceColumnAvailable !== null) {
            return $this->geocodingSourceColumnAvailable;
        }

        $this->geocodingSourceColumnAvailable = Schema::hasColumn('alumni', 'geocoding_source');

        return $this->geocodingSourceColumnAvailable;
    }

    private function resolveStoredSourceLabel(Alumni $alumni): string
    {
        if (! $this->hasGeocodingSourceColumn()) {
            return 'Koordinat Tersimpan';
        }

        return match ($alumni->geocoding_source) {
            'address_geocoding' => 'Alamat (Geocoding Tersimpan)',
            'catalog_city' => 'Alamat (Katalog Kota Tersimpan)',
            'integration_payload' => 'Alamat (Dari Integrasi)',
            default => 'Koordinat Tersimpan',
        };
    }

    private function persistCoordinates(Alumni $alumni, float $lat, float $lng, string $source): void
    {
        if (! $this->hasCoordinateColumns()) {
            return;
        }

        $storedLatitude = is_numeric($alumni->latitude) ? (float) $alumni->latitude : null;
        $storedLongitude = is_numeric($alumni->longitude) ? (float) $alumni->longitude : null;
        $storedSource = $this->hasGeocodingSourceColumn() ? $alumni->geocoding_source : null;

        if (
            $storedLatitude !== null
            && $storedLongitude !== null
            && abs($storedLatitude - $lat) < 0.0000001
            && abs($storedLongitude - $lng) < 0.0000001
            && (! $this->hasGeocodingSourceColumn() || $storedSource === $source)
        ) {
            return;
        }

        $updateData = [
            'latitude' => round($lat, 7),
            'longitude' => round($lng, 7),
        ];

        if ($this->hasGeocodedAtColumn()) {
            $updateData['geocoded_at'] = now();
        }

        if ($this->hasGeocodingSourceColumn()) {
            $updateData['geocoding_source'] = $source;
        }

        Alumni::withoutTimestamps(function () use ($alumni, $updateData): void {
            $alumni->forceFill($updateData)->saveQuietly();
        });
    }

    /**
     * @return array{key: string, label: string, lat: float, lng: float, aliases: array<int, string>}|null
     */
    private function matchCatalogCity(string $text): ?array
    {
        $normalizedText = strtolower(trim($text));

        if ($normalizedText === '') {
            return null;
        }

        foreach (self::CITY_CATALOG as $city) {
            foreach ($city['aliases'] as $alias) {
                if (str_contains($normalizedText, strtolower($alias))) {
                    return $city;
                }
            }
        }

        return null;
    }

    /**
     * @return array{lat: float, lng: float, city: ?string}|null
     */
    private function geocodeAddress(string $address): ?array
    {
        $normalizedAddress = $this->normalizeAddress($address);

        if ($normalizedAddress === '') {
            return null;
        }

        if (array_key_exists($normalizedAddress, $this->runtimeGeocodingCache)) {
            return $this->runtimeGeocodingCache[$normalizedAddress];
        }

        $cacheKey = self::GEOCODING_CACHE_PREFIX.sha1($normalizedAddress);
        $cached = Cache::get($cacheKey, '__missing__');

        if ($cached !== '__missing__') {
            $resolved = is_array($cached) ? $this->parseGeocodingPayload($cached) : null;
            $this->runtimeGeocodingCache[$normalizedAddress] = $resolved;

            return $resolved;
        }

        if ($this->geocodingLookupCount >= self::GEOCODING_MAX_LOOKUPS_PER_REQUEST) {
            $this->runtimeGeocodingCache[$normalizedAddress] = null;

            return null;
        }

        $this->geocodingLookupCount++;

        $headers = [
            'User-Agent' => $this->geocodingUserAgent(),
        ];

        $appUrl = config('app.url');

        if (is_string($appUrl) && trim($appUrl) !== '') {
            $headers['Referer'] = trim($appUrl);
        }

        try {
            $response = Http::timeout(6)
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
            $this->runtimeGeocodingCache[$normalizedAddress] = null;

            return null;
        }

        if (! $response->ok()) {
            Cache::put($cacheKey, false, now()->addHours(self::GEOCODING_FAILURE_TTL_HOURS));
            $this->runtimeGeocodingCache[$normalizedAddress] = null;

            return null;
        }

        $results = $response->json();

        if (! is_array($results) || ! isset($results[0]) || ! is_array($results[0])) {
            Cache::put($cacheKey, false, now()->addHours(self::GEOCODING_FAILURE_TTL_HOURS));
            $this->runtimeGeocodingCache[$normalizedAddress] = null;

            return null;
        }

        $resolved = $this->parseGeocodingPayload($results[0]);

        if ($resolved === null) {
            Cache::put($cacheKey, false, now()->addHours(self::GEOCODING_FAILURE_TTL_HOURS));
            $this->runtimeGeocodingCache[$normalizedAddress] = null;

            return null;
        }

        Cache::put($cacheKey, $resolved, now()->addDays(self::GEOCODING_SUCCESS_TTL_DAYS));
        $this->runtimeGeocodingCache[$normalizedAddress] = $resolved;

        return $resolved;
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

        return $appName.' Mapping/1.0 ('.$appUrl.')';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{lat: float, lng: float, city: ?string}|null
     */
    private function parseGeocodingPayload(array $payload): ?array
    {
        $lat = filter_var($payload['lat'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        $lng = filter_var($payload['lon'] ?? $payload['lng'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

        if (! is_float($lat) || ! is_float($lng)) {
            return null;
        }

        $address = is_array($payload['address'] ?? null) ? $payload['address'] : [];
        $city = is_string($payload['city'] ?? null) ? trim((string) $payload['city']) : null;

        if ($city === null || $city === '') {
            foreach (['city', 'town', 'municipality', 'village', 'county', 'state_district', 'state'] as $field) {
                $value = $address[$field] ?? null;

                if (is_string($value) && trim($value) !== '') {
                    $city = trim($value);
                    break;
                }
            }
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'city' => $city !== '' ? $city : null,
        ];
    }

    private function extractCityFromAddress(string $address): ?string
    {
        $normalized = trim($address);

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/(?:kota|kab(?:upaten)?)\s+([a-zA-Z\s]+)/i', $normalized, $matches) === 1) {
            return ucwords(strtolower(trim($matches[1])));
        }

        $segments = collect(explode(',', $normalized))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => $part !== '')
            ->values();

        if ($segments->isEmpty()) {
            return null;
        }

        return $segments->last();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $collection
     * @return Collection<int, array{key: string, label: string, count: int, lat: ?float, lng: ?float}>
     */
    private function aggregateCities(Collection $collection): Collection
    {
        return $collection
            ->filter(fn (array $item): bool => is_string($item['location_label']) && $item['location_label'] !== '')
            ->groupBy('location_key')
            ->map(function (Collection $group): array {
                $first = $group->first();
                $latitudes = $group
                    ->pluck('lat')
                    ->filter(fn ($value): bool => is_numeric($value))
                    ->map(fn ($value): float => (float) $value)
                    ->values();
                $longitudes = $group
                    ->pluck('lng')
                    ->filter(fn ($value): bool => is_numeric($value))
                    ->map(fn ($value): float => (float) $value)
                    ->values();

                return [
                    'key' => (string) ($first['location_key'] ?? Str::slug((string) $first['location_label'])),
                    'label' => (string) $first['location_label'],
                    'count' => $group->count(),
                    'lat' => $latitudes->isNotEmpty() ? round((float) $latitudes->avg(), 6) : null,
                    'lng' => $longitudes->isNotEmpty() ? round((float) $longitudes->avg(), 6) : null,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function paginateCollection(Collection $items, Request $request, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) $request->integer('page', 1));
        $total = $items->count();
        $paginatedItems = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function emptyPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            max(1, (int) $request->integer('page', 1)),
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyStats(): array
    {
        return [
            'totalAlumni' => 0,
            'withLocation' => 0,
            'withCoordinates' => 0,
            'withoutLocation' => 0,
            'uniqueCities' => 0,
            'coveragePercentage' => 0,
        ];
    }
}
