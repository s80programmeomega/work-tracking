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
            'reproducibility' => ['nullable', 'string', 'in:always,sometimes,rarely,not_reproducible,na'],
            'steps_to_reproduce' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png,gif,zip,webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => __('support.validation.invalid_category'),
            'subject.required' => __('support.validation.subject_required'),
            'message.required' => __('support.validation.message_required'),
            'attachments.*.max' => __('support.validation.attachment_too_large'),
            'reproducibility.in' => __('support.validation.invalid_reproducibility'),
        ];
    }
}
