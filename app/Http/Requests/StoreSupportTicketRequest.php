<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // tout utilisateur authentifié peut soumettre un ticket
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'in:bug,feature,billing,account,other'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png,gif,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => __('support.validation.invalid_category'),
            'subject.required' => __('support.validation.subject_required'),
            'message.required' => __('support.validation.message_required'),
            'attachment.max' => __('support.validation.attachment_too_large'),
        ];
    }
}
