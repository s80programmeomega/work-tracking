<?php

namespace App\Http\Requests\WorkspaceChat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
        ];
    }
}
