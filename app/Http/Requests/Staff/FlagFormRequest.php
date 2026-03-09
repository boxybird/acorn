<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class FlagFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'form_response_id' => ['required', 'exists:form_responses,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
