<?php

namespace App\Http\Controllers;

use App\Http\Requests\FetchIntegrationMenuDataRequest;
use App\Http\Requests\StoreAlumniPreviewRequest;
use App\Models\Alumni;
use App\Models\IntegrationSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Throwable;

class IntegrationSettingsController extends Controller
{
    public function index(): Response
    {
        $defaults = $this->integrationDefaults();

        return Inertia::render('Settings/Integration', [
            'defaults' => [
                'endpoint' => old('endpoint', $defaults['endpoint']),
                'api_key' => old('api_key', $defaults['api_key']),
            ],
            'integrationResult' => session('integrationResult'),
            'integrationError' => session('integrationError'),
            'integrationStatus' => session('integrationStatus'),
            'integrationTest' => session('integrationTest'),
            'databaseError' => session('databaseError'),
            'databaseBackups' => $this->listDatabaseBackups(),
            'connectionStatus' => Cache::get($this->statusCacheKey()),
        ]);
    }

    public function testConnection(FetchIntegrationMenuDataRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->saveIntegrationConfig($validated['endpoint'], $validated['api_key']);

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withHeaders([
                    'X-API-KEY' => $validated['api_key'],
                    'Authorization' => 'Bearer '.$validated['api_key'],
                ])
                ->get($validated['endpoint']);

            if (! $response->successful()) {
                $defaultMessage = "Koneksi API gagal (HTTP {$response->status()}).";
                $responseMessage = $response->json('message');
                $message = is_string($responseMessage) && $responseMessage !== '' ? $responseMessage : $defaultMessage;

                $this->storeConnectionStatus(false, $response->status(), $validated['endpoint'], $message);

                return to_route('settings.integration.index')
                    ->withInput()
                    ->with('integrationTest', [
                        'ok' => false,
                        'status' => $response->status(),
                        'checked_at' => now()->toDateTimeString(),
                        'message' => $message,
                    ]);
            }

            $this->storeConnectionStatus(true, $response->status(), $validated['endpoint'], 'Koneksi API berhasil.');

            return to_route('settings.integration.index')
                ->withInput()
                ->with('integrationTest', [
                    'ok' => true,
                    'status' => $response->status(),
                    'checked_at' => now()->toDateTimeString(),
                    'message' => 'Koneksi API berhasil.',
                ]);
        } catch (Throwable) {
            $this->storeConnectionStatus(
                false,
                null,
                $validated['endpoint'],
                'Koneksi ke API gagal. Periksa endpoint atau API key, lalu coba lagi.',
            );

            return to_route('settings.integration.index')
                ->withInput()
                ->with('integrationTest', [
                    'ok' => false,
                    'status' => null,
                    'checked_at' => now()->toDateTimeString(),
                    'message' => 'Koneksi ke API gagal. Periksa endpoint atau API key, lalu coba lagi.',
                ]);
        }
    }

    public function saveConfig(FetchIntegrationMenuDataRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->saveIntegrationConfig($validated['endpoint'], $validated['api_key']);

        return to_route('settings.integration.index')
            ->withInput()
            ->with('success', 'Endpoint API dan API key berhasil disimpan.');
    }

    public function storeAlumniPreview(StoreAlumniPreviewRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $created = 0;
        $updated = 0;

        try {
            DB::transaction(function () use ($validated, &$created, &$updated): void {
                foreach ($validated['records'] as $record) {
                    $nim = trim((string) $record['nim']);
                    $existing = Alumni::query()->where('nim', $nim)->first();

                    $email = $this->normalizeAlumniEmail(
                        $record['email'] ?? null,
                        $nim,
                    );

                    Alumni::query()->updateOrCreate(
                        ['nim' => $nim],
                        [
                            'nama' => trim((string) $record['nama']),
                            'email' => $email,
                            'email_kampus' => $this->nullableString($record['email_kampus'] ?? null),
                            'email_pribadi' => $this->nullableString($record['email_pribadi'] ?? null),
                            'photo_url' => $this->downloadAndSavePhoto(
                                $record['photo_url'] ?? null,
                                $record['integration_payload']['photo_3x4_path'] ?? $record['photo_3x4_path'] ?? null,
                                $nim,
                            ),
                            'no_telepon' => $this->nullableString($record['no_telepon'] ?? null),
                            'jurusan' => trim((string) $record['jurusan']),
                            'tahun_lulus' => isset($record['tahun_lulus']) && $record['tahun_lulus'] !== '' ? (int) $record['tahun_lulus'] : null,
                            'pekerjaan' => $this->nullableString($record['pekerjaan'] ?? null),
                            'organisasi' => $this->nullableString($record['organisasi'] ?? null),
                            'fakultas' => $this->nullableString($record['fakultas'] ?? null),
                            'instansi' => $this->nullableString($record['instansi'] ?? null),
                            'alamat' => $this->nullableString($record['alamat'] ?? null),
                            'integration_payload' => isset($record['integration_payload']) && is_array($record['integration_payload'])
                                ? $this->filterIntegrationPayload($record['integration_payload'])
                                : null,
                            'tempat_lahir' => $this->nullableString($record['tempat_lahir'] ?? $record['birth_place'] ?? null),
                            'tanggal_lahir' => $this->parseDate($record['tanggal_lahir'] ?? $record['birth_date'] ?? null),
                            'agama' => $this->nullableString($record['agama'] ?? $record['religion'] ?? null),
                            'jenis_kelamin' => $this->nullableString($record['jenis_kelamin'] ?? $record['gender'] ?? null),
                            'no_ktp' => $this->nullableString($record['no_ktp'] ?? $record['ktp_number'] ?? null),
                            'ipk' => isset($record['ipk']) ? (float) $record['ipk'] : null,
                            'predikat' => $this->nullableString($record['predikat'] ?? $record['predicate'] ?? null),
                            'judul_skripsi' => $this->nullableString($record['judul_skripsi'] ?? $record['thesis_title'] ?? null),
                            'pembimbing_1' => $this->nullableString($record['pembimbing_1'] ?? $record['supervisor_1'] ?? null),
                            'pembimbing_2' => $this->nullableString($record['pembimbing_2'] ?? $record['supervisor_2'] ?? null),
                            'ukuran_toga' => $this->nullableString($record['ukuran_toga'] ?? $record['gown_size'] ?? null),
                            'status_bekerja' => isset($record['status_bekerja']) || isset($record['is_employed'])
                                ? ($record['status_bekerja'] ?? $record['is_employed'] ?? null)
                                : null,
                            'nama_ayah' => $this->nullableString($record['nama_ayah'] ?? $record['father_name'] ?? null),
                            'nama_ibu' => $this->nullableString($record['nama_ibu'] ?? $record['mother_name'] ?? null),
                            'no_telepon_orang_tua' => $this->nullableString($record['no_telepon_orang_tua'] ?? $record['parent_phone'] ?? null),
                            'link_dokumen_tambahan' => $this->nullableString($record['link_dokumen_tambahan'] ?? $record['extra_document_link'] ?? null),
                        ],
                    );

                    if ($existing) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }
            });

            $total = $created + $updated;

            return to_route('settings.integration.index')
                ->with('success', "{$total} data alumni berhasil disimpan. ({$created} baru, {$updated} diperbarui)");
        } catch (Throwable $exception) {
            return to_route('settings.integration.index')
                ->withInput()
                ->with('integrationError', 'Gagal menyimpan data alumni. '.$this->humanReadableError($exception));
        }
    }

    public function fetch(FetchIntegrationMenuDataRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->saveIntegrationConfig($validated['endpoint'], $validated['api_key']);

        try {
            $http = $this->integrationHttpClient($validated['api_key']);
            $response = $http->get($validated['endpoint']);

            if (! $response->successful()) {
                $defaultMessage = "Gagal mengambil data dari API (HTTP {$response->status()}).";
                $responseMessage = $response->json('message');
                $message = is_string($responseMessage) && $responseMessage !== '' ? $responseMessage : $defaultMessage;

                $this->storeConnectionStatus(false, $response->status(), $validated['endpoint'], $message);

                return to_route('settings.integration.index')
                    ->withInput()
                    ->with('integrationStatus', $response->status())
                    ->with('integrationError', $message);
            }

            $payload = $response->json();
            $aggregatedPayload = $this->aggregatePaginatedApiPayload(
                $http,
                $validated['endpoint'],
                is_array($payload) ? $payload : [],
            );
            $totalRecords = $this->extractTotalRecords($aggregatedPayload);

            $this->storeConnectionStatus(true, $response->status(), $validated['endpoint'], 'Data menu berhasil diambil dari API.');

            return to_route('settings.integration.index')
                ->with('success', "Data alumni berhasil diambil dari API ({$totalRecords} data).")
                ->with('integrationStatus', $response->status())
                ->with('integrationResult', [
                    'fetched_at' => now()->toDateTimeString(),
                    'endpoint' => $validated['endpoint'],
                    'data' => $aggregatedPayload,
                ]);
        } catch (Throwable) {
            $this->storeConnectionStatus(
                false,
                null,
                $validated['endpoint'],
                'Koneksi ke API gagal. Periksa endpoint atau API key, lalu coba lagi.',
            );

            return to_route('settings.integration.index')
                ->withInput()
                ->with('integrationError', 'Koneksi ke API gagal. Periksa endpoint atau API key, lalu coba lagi.');
        }
    }

    public function backupDatabase(Request $request): RedirectResponse
    {
        try {
            $backup = $this->createDatabaseBackup();

            return to_route('settings.integration.index')
                ->with('success', "Backup database berhasil dibuat: {$backup['name']}");
        } catch (Throwable $exception) {
            return to_route('settings.integration.index')
                ->with('databaseError', 'Gagal membuat backup database. '.$this->humanReadableError($exception));
        }
    }

    public function restoreDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file_name' => ['required', 'string'],
        ]);

        try {
            $relativePath = $this->resolveBackupFilePath($validated['file_name']);
            $this->importSqlFromPath(Storage::disk('local')->path($relativePath));

            return to_route('settings.integration.index')
                ->with('success', "Restore database berhasil dari file {$validated['file_name']}.");
        } catch (Throwable $exception) {
            return to_route('settings.integration.index')
                ->with('databaseError', 'Gagal restore database. '.$this->humanReadableError($exception));
        }
    }

    public function importDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sql_file' => ['required', 'file', 'mimes:sql,txt', 'max:51200'],
        ]);

        try {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $validated['sql_file'];

            $this->importSqlFromPath($uploadedFile->getRealPath());

            return to_route('settings.integration.index')
                ->with('success', "Import database berhasil dari file {$uploadedFile->getClientOriginalName()}.");
        } catch (Throwable $exception) {
            return to_route('settings.integration.index')
                ->with('databaseError', 'Gagal import SQL. '.$this->humanReadableError($exception));
        }
    }

    public function deleteBackup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file_name' => ['required', 'string'],
        ]);

        try {
            $relativePath = $this->resolveBackupFilePath($validated['file_name']);
            Storage::disk('local')->delete($relativePath);

            return to_route('settings.integration.index')
                ->with('success', "File backup {$validated['file_name']} berhasil dihapus.");
        } catch (Throwable $exception) {
            return to_route('settings.integration.index')
                ->with('databaseError', 'Gagal menghapus file backup. '.$this->humanReadableError($exception));
        }
    }

    public function downloadBackup(string $fileName): BinaryFileResponse
    {
        try {
            $relativePath = $this->resolveBackupFilePath($fileName);
        } catch (Throwable) {
            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($relativePath),
            basename($relativePath),
            ['Content-Type' => 'application/sql'],
        );
    }

    private function storeConnectionStatus(bool $ok, ?int $status, string $endpoint, string $message): void
    {
        Cache::put($this->statusCacheKey(), [
            'ok' => $ok,
            'status' => $status,
            'endpoint' => $endpoint,
            'message' => $message,
            'checked_at' => now()->toDateTimeString(),
        ], now()->addDays(14));
    }

    private function statusCacheKey(): string
    {
        return (string) config('integration.status_cache_key', 'integration.menu_data.connection_status');
    }

    private function integrationHttpClient(string $apiKey): PendingRequest
    {
        return Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'X-API-KEY' => $apiKey,
                'Authorization' => 'Bearer '.$apiKey,
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function aggregatePaginatedApiPayload(PendingRequest $http, string $endpoint, array $payload): array
    {
        $pagination = $payload['pagination'] ?? null;

        if (! is_array($pagination)) {
            return $payload;
        }

        $firstPageRows = $pagination['data'] ?? [];

        if (! is_array($firstPageRows)) {
            return $payload;
        }

        $currentPage = (int) ($pagination['current_page'] ?? 1);
        $lastPage = (int) ($pagination['last_page'] ?? $currentPage);

        if ($lastPage <= $currentPage) {
            return $payload;
        }

        $allRows = $firstPageRows;
        $path = is_string($pagination['path'] ?? null) && $pagination['path'] !== ''
            ? (string) $pagination['path']
            : $endpoint;

        for ($page = $currentPage + 1; $page <= $lastPage; $page++) {
            $nextResponse = $http->get($path, ['page' => $page]);

            if (! $nextResponse->successful()) {
                throw new RuntimeException("Gagal mengambil halaman API ke-{$page} (HTTP {$nextResponse->status()}).");
            }

            $nextPayload = $nextResponse->json();

            if (! is_array($nextPayload)) {
                continue;
            }

            $nextPagination = $nextPayload['pagination'] ?? null;
            $nextRows = is_array($nextPagination) && is_array($nextPagination['data'] ?? null)
                ? $nextPagination['data']
                : [];

            if ($nextRows !== []) {
                $allRows = [...$allRows, ...$nextRows];
            }
        }

        $payload['pagination']['data'] = $allRows;
        $payload['pagination']['current_page'] = 1;
        $payload['pagination']['last_page'] = 1;
        $payload['pagination']['per_page'] = max(1, count($allRows));
        $payload['pagination']['total'] = count($allRows);
        $payload['pagination']['from'] = count($allRows) > 0 ? 1 : null;
        $payload['pagination']['to'] = count($allRows) > 0 ? count($allRows) : null;

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractTotalRecords(array $payload): int
    {
        $pagination = $payload['pagination'] ?? null;

        if (is_array($pagination) && is_array($pagination['data'] ?? null)) {
            return count($pagination['data']);
        }

        if (is_array($payload['data'] ?? null)) {
            return count($payload['data']);
        }

        return 0;
    }

    /**
     * @return array{endpoint:string,api_key:string}
     */
    private function integrationDefaults(): array
    {
        $savedConfig = $this->savedIntegrationConfig();

        return [
            'endpoint' => (string) ($savedConfig['endpoint'] ?? config('integration.menu_data_endpoint', '')),
            'api_key' => (string) ($savedConfig['api_key'] ?? config('integration.api_key', '')),
        ];
    }

    /**
     * @return array{endpoint:string,api_key:string}|null
     */
    private function savedIntegrationConfig(): ?array
    {
        $setting = IntegrationSetting::query()->find(1);

        if (! $setting) {
            return null;
        }

        return [
            'endpoint' => (string) $setting->endpoint,
            'api_key' => (string) $setting->api_key,
        ];
    }

    private function saveIntegrationConfig(string $endpoint, string $apiKey): void
    {
        IntegrationSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'endpoint' => $endpoint,
                'api_key' => $apiKey,
            ],
        );
    }

    /**
     * @return array<int, array{name:string,size:string,created_at:string,download_url:string}>
     */
    private function listDatabaseBackups(): array
    {
        $disk = Storage::disk('local');

        return collect($disk->files($this->backupDirectory()))
            ->filter(fn (string $path) => str_ends_with(strtolower($path), '.sql'))
            ->sortDesc()
            ->values()
            ->map(function (string $path) use ($disk): array {
                return [
                    'name' => basename($path),
                    'size' => $this->formatBytes((int) $disk->size($path)),
                    'created_at' => now()->setTimestamp($disk->lastModified($path))->toDateTimeString(),
                    'download_url' => route('settings.database.download', ['fileName' => basename($path)]),
                ];
            })
            ->all();
    }

    /**
     * @return array{name:string,path:string}
     */
    private function createDatabaseBackup(): array
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory($this->backupDirectory());

        $fileName = 'backup_'.now()->format('Ymd_His').'.sql';
        $relativePath = $this->backupDirectory().'/'.$fileName;
        $absolutePath = $disk->path($relativePath);

        $config = $this->currentConnectionConfig();
        $driver = (string) ($config['driver'] ?? '');

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $sqlOutput = $this->buildMysqlDump();
        } else {
            $process = $this->makeDatabaseDumpProcess();
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Proses backup gagal dijalankan. Pastikan tool backup database tersedia di server.');
            }

            $sqlOutput = $process->getOutput();
        }

        File::put($absolutePath, $sqlOutput);

        if (! File::exists($absolutePath) || File::size($absolutePath) === 0) {
            throw new RuntimeException('File backup tidak berhasil dibuat atau kosong.');
        }

        return [
            'name' => $fileName,
            'path' => $relativePath,
        ];
    }

    private function importSqlFromPath(string $absolutePath): void
    {
        if (! File::exists($absolutePath)) {
            throw new RuntimeException('File SQL tidak ditemukan.');
        }

        $sqlContent = File::get($absolutePath);

        if (trim($sqlContent) === '') {
            throw new RuntimeException('File SQL kosong.');
        }

        $config = $this->currentConnectionConfig();
        $driver = (string) ($config['driver'] ?? '');

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->executeMysqlSql($sqlContent);

            return;
        }

        $process = $this->makeDatabaseImportProcess();
        $process->setTimeout(300);
        $process->setInput($sqlContent);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Proses import/restore gagal. Periksa file SQL dan konfigurasi database.');
        }
    }

    private function buildMysqlDump(): string
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $databaseName = (string) $connection->getDatabaseName();

        if ($databaseName === '') {
            throw new RuntimeException('Nama database tidak ditemukan untuk proses backup MySQL.');
        }

        $dump = [];
        $dump[] = '-- Alumni Portal MySQL Backup';
        $dump[] = '-- Generated at '.now()->toDateTimeString();
        $dump[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $dump[] = '';

        $tables = array_map(
            static fn ($row): string => (string) array_values((array) $row)[0],
            $connection->select('SHOW TABLES'),
        );

        foreach ($tables as $tableName) {
            $quotedTable = $this->quoteIdentifier($tableName);
            $createTableRow = $connection->selectOne("SHOW CREATE TABLE {$quotedTable}");

            if (! $createTableRow) {
                continue;
            }

            $createTableData = array_values((array) $createTableRow);
            $createTableSql = isset($createTableData[1]) ? (string) $createTableData[1] : '';

            if ($createTableSql === '') {
                continue;
            }

            $dump[] = "DROP TABLE IF EXISTS {$quotedTable};";
            $dump[] = $createTableSql.';';

            $columns = array_map(
                static fn ($row): string => (string) ((array) $row)['Field'],
                $connection->select("SHOW COLUMNS FROM {$quotedTable}"),
            );

            if ($columns === []) {
                $dump[] = '';

                continue;
            }

            $selectColumns = array_map(
                fn (string $column): string => $this->quoteIdentifier($column),
                $columns,
            );

            $rows = $connection->table($tableName)->selectRaw(implode(', ', $selectColumns))->get();

            foreach ($rows->chunk(200) as $chunkedRows) {
                $valueLines = [];

                foreach ($chunkedRows as $row) {
                    $rowArray = (array) $row;
                    $values = [];

                    foreach ($columns as $column) {
                        $values[] = $this->quoteMysqlValue($rowArray[$column] ?? null, $pdo);
                    }

                    $valueLines[] = '('.implode(', ', $values).')';
                }

                if ($valueLines !== []) {
                    $columnSql = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns));
                    $dump[] = "INSERT INTO {$quotedTable} ({$columnSql}) VALUES\n".implode(",\n", $valueLines).';';
                }
            }

            $dump[] = '';
        }

        $views = $connection->select("SHOW FULL TABLES WHERE Table_type = 'VIEW'");

        foreach ($views as $view) {
            $viewName = (string) array_values((array) $view)[0];
            $quotedView = $this->quoteIdentifier($viewName);
            $createViewRow = $connection->selectOne("SHOW CREATE VIEW {$quotedView}");

            if (! $createViewRow) {
                continue;
            }

            $createViewData = array_values((array) $createViewRow);
            $createViewSql = isset($createViewData[1]) ? (string) $createViewData[1] : '';

            if ($createViewSql !== '') {
                $dump[] = "DROP VIEW IF EXISTS {$quotedView};";
                $dump[] = $createViewSql.';';
                $dump[] = '';
            }
        }

        $dump[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $dump)."\n";
    }

    private function executeMysqlSql(string $sqlContent): void
    {
        $connection = DB::connection();
        $statements = $this->splitSqlStatements($sqlContent);

        if ($statements === []) {
            throw new RuntimeException('Tidak ada perintah SQL valid untuk dijalankan.');
        }

        $connection->unprepared('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($statements as $statement) {
                $trimmedStatement = trim($statement);

                if ($trimmedStatement === '') {
                    continue;
                }

                $connection->unprepared($trimmedStatement);
            }
        } finally {
            $connection->unprepared('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sqlContent): array
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent) ?? $sqlContent;
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }

                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $index++;
                }

                continue;
            }

            if (! $inSingleQuote && ! $inDoubleQuote && ! $inBacktick) {
                if ($char === '-' && $next === '-' && ($index + 2 >= $length || ctype_space($sql[$index + 2]))) {
                    $inLineComment = true;
                    $index++;

                    continue;
                }

                if ($char === '#') {
                    $inLineComment = true;

                    continue;
                }

                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $index++;

                    continue;
                }
            }

            if ($char === "'" && ! $inDoubleQuote && ! $inBacktick) {
                if ($inSingleQuote && $next === "'") {
                    $buffer .= "''";
                    $index++;

                    continue;
                }

                $inSingleQuote = ! $inSingleQuote;
                $buffer .= $char;

                continue;
            }

            if ($char === '"' && ! $inSingleQuote && ! $inBacktick) {
                $inDoubleQuote = ! $inDoubleQuote;
                $buffer .= $char;

                continue;
            }

            if ($char === '`' && ! $inSingleQuote && ! $inDoubleQuote) {
                $inBacktick = ! $inBacktick;
                $buffer .= $char;

                continue;
            }

            if ($char === ';' && ! $inSingleQuote && ! $inDoubleQuote && ! $inBacktick) {
                $statement = trim($buffer);

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        $lastStatement = trim($buffer);

        if ($lastStatement !== '') {
            $statements[] = $lastStatement;
        }

        return $statements;
    }

    private function quoteMysqlValue(mixed $value, \PDO $pdo): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function makeDatabaseDumpProcess(): Process
    {
        $config = $this->currentConnectionConfig();
        $driver = (string) ($config['driver'] ?? '');

        if ($driver === 'sqlite') {
            return new Process(['sqlite3', $this->sqliteDatabasePath($config), '.dump']);
        }

        throw new RuntimeException("Driver database '{$driver}' belum didukung untuk backup otomatis.");
    }

    private function makeDatabaseImportProcess(): Process
    {
        $config = $this->currentConnectionConfig();
        $driver = (string) ($config['driver'] ?? '');

        if ($driver === 'sqlite') {
            return new Process(['sqlite3', $this->sqliteDatabasePath($config)]);
        }

        throw new RuntimeException("Driver database '{$driver}' belum didukung untuk import otomatis.");
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    private function mysqlDumpCommand(array $config): array
    {
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new RuntimeException('Konfigurasi database belum lengkap untuk proses backup.');
        }

        $command = [
            'mysqldump',
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? 3306),
            '--user='.$username,
            '--single-transaction',
            '--skip-lock-tables',
            '--routines',
            '--triggers',
        ];

        if ($password !== '') {
            $command[] = '--password='.$password;
        }

        if (! empty($config['unix_socket'])) {
            $command[] = '--socket='.(string) $config['unix_socket'];
        }

        if (! empty($config['charset'])) {
            $command[] = '--default-character-set='.(string) $config['charset'];
        }

        $command[] = $database;

        return $command;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    private function mysqlImportCommand(array $config): array
    {
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new RuntimeException('Konfigurasi database belum lengkap untuk proses import.');
        }

        $command = [
            'mysql',
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? 3306),
            '--user='.$username,
        ];

        if ($password !== '') {
            $command[] = '--password='.$password;
        }

        if (! empty($config['unix_socket'])) {
            $command[] = '--socket='.(string) $config['unix_socket'];
        }

        if (! empty($config['charset'])) {
            $command[] = '--default-character-set='.(string) $config['charset'];
        }

        $command[] = $database;

        return $command;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function sqliteDatabasePath(array $config): string
    {
        $database = (string) ($config['database'] ?? '');

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('Database SQLite in-memory tidak mendukung backup/import file SQL.');
        }

        if (! str_starts_with($database, DIRECTORY_SEPARATOR)) {
            $database = database_path($database);
        }

        return $database;
    }

    /**
     * @return array<string, mixed>
     */
    private function currentConnectionConfig(): array
    {
        $connection = (string) config('database.default');

        return (array) config("database.connections.{$connection}", []);
    }

    private function resolveBackupFilePath(string $fileName): string
    {
        $safeFileName = basename($fileName);

        if ($safeFileName === '' || $safeFileName !== $fileName || ! preg_match('/^[A-Za-z0-9._-]+$/', $safeFileName)) {
            throw new RuntimeException('Nama file backup tidak valid.');
        }

        $relativePath = $this->backupDirectory().'/'.$safeFileName;

        if (! Storage::disk('local')->exists($relativePath)) {
            throw new RuntimeException('File backup tidak ditemukan.');
        }

        return $relativePath;
    }

    private function backupDirectory(): string
    {
        return 'backups/sql';
    }

    private function humanReadableError(Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        return $message !== ''
            ? $message
            : 'Periksa koneksi database dan pastikan tool backup/import tersedia di server.';
    }

    private function normalizeAlumniEmail(mixed $email, string $nim): ?string
    {
        $normalized = $this->nullableString($email);

        if (! $normalized) {
            return null;
        }

        $normalized = strtolower($normalized);

        $usedByOtherNim = Alumni::query()
            ->where('email', $normalized)
            ->where('nim', '!=', $nim)
            ->exists();

        return $usedByOtherNim ? null : $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $date = match (true) {
            is_numeric($value) => gmdate('Y-m-d', (int) $value),
            strtotime($value) !== false => date('Y-m-d', strtotime($value)),
            default => null,
        };

        return $date;
    }

    private function filterIntegrationPayload(array $payload): array
    {
        $excludeKeys = [
            'source_graduate_id',
            'source_student_id',
            'student_user_id',
            'faculty_id',
            'study_program_id',
            'source_session_id',
            'source_ceremony_id',
            'archived_by_user_id',
            'verified_by_user_id',
            'rejection_note',
            'profile_status',
            'call_order',
            'attendance_status',
            'archived_at',
            'created_at',
            'updated_at',
            'submitted_at',
            'verified_at',
            'source_session',
            'faculty',
            'study_program',
            'archived_by',
            'verified_by',
        ];

        $excludePatterns = ['_id', 'Nama Sesi', 'Nama Acara', 'ID User', 'Diperbarui', 'Diarsipkan', 'Diverifikasi', 'Dokumen'];

        $filtered = [];

        foreach ($payload as $key => $value) {
            if (in_array($key, $excludeKeys, true)) {
                continue;
            }

            $skip = false;
            foreach ($excludePatterns as $pattern) {
                if (str_contains($key, $pattern)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            if (is_array($value)) {
                $filtered[$key] = $this->filterIntegrationPayload($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return number_format($bytes / (1024 * 1024), 2).' MB';
    }

    private function downloadAndSavePhoto(?string $directUrl, ?string $apiPhotoPath, string $nim): ?string
    {
        $urlToDownload = null;

        if ($directUrl && filter_var($directUrl, FILTER_VALIDATE_URL)) {
            $urlToDownload = $directUrl;
        } elseif ($apiPhotoPath && filter_var($apiPhotoPath, FILTER_VALIDATE_URL)) {
            $urlToDownload = $apiPhotoPath;
        }

        if (! $urlToDownload) {
            return $this->nullableString($directUrl);
        }

        try {
            $contents = Http::timeout(30)->get($urlToDownload)->body();

            if (empty($contents)) {
                return $this->nullableString($directUrl);
            }

            $extension = 'jpg';
            if (preg_match('/\.(\w+)/', $urlToDownload, $matches)) {
                $ext = strtolower($matches[1]);
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $extension = $ext;
                }
            }

            $filename = 'alumni/'.$nim.'_'.time().'.'.$extension;
            $saved = Storage::disk('public')->put($filename, $contents);

            if ($saved) {
                return Storage::disk('public')->url($filename);
            }
        } catch (Throwable) {
        }

        return $this->nullableString($directUrl);
    }
}
