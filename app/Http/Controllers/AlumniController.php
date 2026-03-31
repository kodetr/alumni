<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlumniRequest;
use App\Http\Requests\UpdateAlumniRequest;
use App\Models\Alumni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlumniController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $angkatan = $request->string('angkatan')->toString();
        $jurusan = $request->string('jurusan')->toString();
        $perPageOptions = [20, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 20);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }

        $alumni = Alumni::query()
            ->select(['id', 'nim', 'nama', 'jurusan', 'angkatan', 'email'])
            ->when($search, function ($query, $searchValue) {
                $query->where(function ($searchQuery) use ($searchValue): void {
                    $searchQuery
                        ->where('nama', 'like', "%{$searchValue}%")
                        ->orWhere('nim', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%")
                        ->orWhere('jurusan', 'like', "%{$searchValue}%");
                });
            })
            ->when($angkatan, fn ($query, $angkatanValue) => $query->where('angkatan', $angkatanValue))
            ->when($jurusan, fn ($query, $jurusanValue) => $query->where('jurusan', $jurusanValue))
            ->orderByDesc('angkatan')
            ->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Alumni/Index', [
            'alumni' => $alumni,
            'filters' => [
                'search' => $search,
                'angkatan' => $angkatan,
                'jurusan' => $jurusan,
                'per_page' => $perPage,
            ],
            'perPageOptions' => $perPageOptions,
            'angkatanOptions' => Alumni::query()
                ->select('angkatan')
                ->distinct()
                ->orderByDesc('angkatan')
                ->pluck('angkatan'),
            'jurusanOptions' => Alumni::query()
                ->select('jurusan')
                ->distinct()
                ->orderBy('jurusan')
                ->pluck('jurusan'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Alumni/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlumniRequest $request): RedirectResponse
    {
        Alumni::create($request->validated());

        return to_route('alumni.index')->with('success', 'Data alumni berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Alumni $alumni): Response
    {
        return Inertia::render('Alumni/Show', [
            'alumni' => $alumni,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Alumni $alumni): Response
    {
        return Inertia::render('Alumni/Edit', [
            'alumni' => $alumni,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlumniRequest $request, Alumni $alumni): RedirectResponse
    {
        $alumni->update($request->validated());

        return to_route('alumni.index')->with('success', 'Data alumni berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alumni $alumni): RedirectResponse
    {
        $alumni->delete();

        return to_route('alumni.index')->with('success', 'Data alumni berhasil dihapus.');
    }
}
