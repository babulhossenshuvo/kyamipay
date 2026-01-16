<?php

namespace KPay\LaravelKPayment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'description' => 'nullable|string|max:30',
            'expiry' => 'nullable|date_format:Y-m-d H:i:s|after:now',
            'user_id' => 'nullable|string|max:255',
            'order_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a number',
            'amount.min' => 'Payment amount must be greater than 0',
            'description.max' => 'Description cannot exceed 30 characters',
            'expiry.date_format' => 'Expiry must be in format: Y-m-d H:i:s',
            'expiry.after' => 'Expiry date must be in the future',
        ];
    }
}
