<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlumniRequest extends FormRequest
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
        $maxYear = now()->year + 1;

        return [
            'nama' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:30', Rule::unique('alumni', 'nim')],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'jurusan' => ['required', 'string', 'max:255'],
            'tahun_lulus' => ['nullable', 'integer', 'digits:4', 'between:1900,'.($maxYear + 6)],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
