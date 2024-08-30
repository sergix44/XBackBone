<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadResourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['file'],
            'name' => ['sometimes', 'string', 'nullable'],
            'data' => ['sometimes', 'string', 'nullable']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
