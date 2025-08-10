<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CostRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin'      => ['required','integer'],      // id district asal
            'destination' => ['required','integer'],      // id district tujuan (atau id yang disyaratkan Komerce)
            'weight'      => ['required','integer','min:1'],
            'courier'    => ['required','string'],       // colon-separated, ex: "jne:pos:tiki"
        ];
    }
}
