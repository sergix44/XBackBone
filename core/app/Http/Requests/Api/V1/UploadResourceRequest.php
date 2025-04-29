<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadResourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'nullable'],
            'file' => ['file', 'prohibits:data', 'required_without:data'],
            'data' => ['string', 'prohibits:file', 'required_without:file'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
