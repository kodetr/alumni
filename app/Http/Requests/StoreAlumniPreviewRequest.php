<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlumniPreviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.nim' => ['required', 'string', 'max:30'],
            'records.*.nama' => ['required', 'string', 'max:255'],
            'records.*.jurusan' => ['required', 'string', 'max:255'],
            'records.*.angkatan' => ['required', 'integer', 'between:1900,2100'],
            'records.*.email' => ['nullable', 'email', 'max:255'],
            'records.*.no_telepon' => ['nullable', 'string', 'max:30'],
            'records.*.tahun_lulus' => ['nullable', 'integer', 'between:1900,2100'],
            'records.*.pekerjaan' => ['nullable', 'string', 'max:255'],
            'records.*.instansi' => ['nullable', 'string', 'max:255'],
            'records.*.alamat' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'records.required' => 'Data preview alumni belum tersedia.',
            'records.min' => 'Minimal ada satu data alumni untuk disimpan.',
            'records.*.nim.required' => 'NIM wajib diisi.',
            'records.*.nama.required' => 'Nama wajib diisi.',
            'records.*.jurusan.required' => 'Jurusan wajib diisi.',
            'records.*.angkatan.required' => 'Angkatan wajib diisi untuk setiap alumni.',
        ];
    }
}
