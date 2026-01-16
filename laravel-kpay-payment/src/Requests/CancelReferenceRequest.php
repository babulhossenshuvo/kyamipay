<?php

namespace KPay\LaravelKPayment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelReferenceRequest extends FormRequest
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
            'reference' => 'required|string|size:15|regex:/^[0-9]+$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reference.required' => 'Reference is required',
            'reference.size' => 'Reference must be exactly 15 characters',
            'reference.regex' => 'Reference must contain only numbers',
        ];
    }
}
