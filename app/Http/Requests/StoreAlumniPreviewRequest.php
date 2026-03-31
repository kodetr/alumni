<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlumniPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => ['nullable', 'array', 'min:1'],
            'records.*.nim' => ['required_with:records', 'string', 'max:30'],
            'records.*.nama' => ['required_with:records', 'string', 'max:255'],
            'records.*.jurusan' => ['required_with:records', 'string', 'max:255'],
            'records.*.photo_url' => ['nullable', 'string', 'max:2048'],
            'records.*.no_telepon' => ['nullable', 'string', 'max:30'],
            'records.*.tahun_lulus' => ['nullable', 'integer', 'between:1900,2100'],
            'records.*.pekerjaan' => ['nullable', 'string', 'max:255'],
            'records.*.instansi' => ['nullable', 'string', 'max:255'],
            'records.*.alamat' => ['nullable', 'string'],
            'records.*.integration_payload' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'records.min' => 'Minimal ada satu data alumni untuk disimpan.',
            'records.*.nim.required_with' => 'NIM wajib diisi.',
            'records.*.nama.required_with' => 'Nama wajib diisi.',
            'records.*.jurusan.required_with' => 'Jurusan wajib diisi.',
        ];
    }
}
