<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;

class BlueprintShowRequest extends BlueprintRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'include' => ['sometimes', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateInclude($validator),
        ];
    }
}
