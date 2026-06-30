<?php

namespace App\Http\Requests\WorkspaceChat;

use Illuminate\Foundation\Http\FormRequest;

class SendWorkspaceMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:10000'],
            'mentions' => ['nullable', 'array'],
            'mentions.*' => ['integer', 'exists:users,id'],
            'mention_everyone' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'reply_to_id' => ['nullable', 'integer', 'exists:workspace_messages,id'],
        ];
    }
}
