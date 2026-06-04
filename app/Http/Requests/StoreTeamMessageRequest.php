<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'mentions' => 'sometimes|array',
            'mentions.*' => 'integer|exists:users,id',
            'reply_to_id' => 'sometimes|nullable|integer|exists:team_messages,id',
            'attachment' => 'sometimes|nullable|file|max:10240|mimes:jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => __('team_show.message_required'),
            'content.max' => __('team_show.message_too_long'),
            'attachment.max' => __('team_show.attachment_too_large'),
        ];
    }
}
