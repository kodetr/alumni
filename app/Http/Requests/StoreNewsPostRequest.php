<?php

namespace App\Http\Requests;

use App\Support\HtmlSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNewsPostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $isPublished = filter_var($this->input('is_published', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $removeImage = filter_var($this->input('remove_image', false), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        $this->merge([
            'title' => HtmlSanitizer::sanitizeText($this->input('title')),
            'excerpt' => HtmlSanitizer::sanitizeText($this->input('excerpt')),
            'content' => HtmlSanitizer::sanitizeRichText($this->input('content')),
            'is_published' => $isPublished ?? true,
            'remove_image' => $removeImage ?? false,
        ]);
    }
}
