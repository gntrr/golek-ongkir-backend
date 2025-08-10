<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistrictsRequest extends FormRequest 
{
  public function authorize(): bool { return true; }
  
  public function rules(): array {
    return ['city' => ['required','integer']];
  }
}
