<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $term = $this->input('search')
            ?? $this->input('keyword')
            ?? $this->input('q');

        if ($term !== null) {
            $this->merge(['search' => $term]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['required','string','min:2'],
        ];
    }
}
