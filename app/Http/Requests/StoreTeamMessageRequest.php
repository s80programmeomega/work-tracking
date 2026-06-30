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

    protected function prepareForValidation(): void
    {
        // Le frontend envoie 'mentions' en JSON string via FormData (upload de fichier) ;
        // les appels JSON envoient directement un tableau. On normalise vers un tableau.
        if (is_string($this->input('mentions'))) {
            $this->merge(['mentions' => json_decode((string) $this->input('mentions'), true) ?? []]);
        }
    }

    public function rules(): array
    {
        $hasAttachment = $this->hasFile('attachment') || $this->filled('attachments_json');

        return [
            'content' => [$hasAttachment ? 'sometimes' : 'required', 'nullable', 'string', 'max:5000'],
            'mentions' => 'sometimes|nullable|array',
            'mentions.*' => 'integer|exists:users,id',
            'mention_everyone' => 'sometimes|nullable|string',
            'reply_to_id' => 'sometimes|nullable|integer|exists:team_messages,id',
            'attachment' => 'sometimes|nullable|file|max:10240|mimes:jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
            'attachments_json' => 'sometimes|nullable|string',
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
