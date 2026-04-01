<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniRequest extends FormRequest
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
        $alumni = $this->route('alumni');
        $alumniId = is_object($alumni) ? $alumni->id : $alumni;
        $maxYear = now()->year + 1;

        return [
            'nama' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:30', Rule::unique('alumni', 'nim')->ignore($alumniId)],
            'tanggal_lahir' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'jurusan' => ['required', 'string', 'max:255'],
            'tahun_lulus' => ['nullable', 'integer', 'digits:4', 'between:1900,'.($maxYear + 6)],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
