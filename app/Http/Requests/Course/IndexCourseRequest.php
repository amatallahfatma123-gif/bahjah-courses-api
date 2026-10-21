<?php

declare(strict_types=1);

namespace App\Http\Requests\Course;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'published' => ['nullable', Rule::in(['true', 'false', '1', '0'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function searchTerm(): ?string
    {
        $term = $this->validated('search');

        return is_string($term) ? $term : null;
    }

    public function publishedFilter(): ?bool
    {
        return $this->filled('published') ? $this->boolean('published') : null;
    }

    public function perPage(): ?int
    {
        $perPage = $this->validated('per_page');

        return $perPage === null ? null : (int) $perPage;
    }
}
