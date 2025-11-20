<?php

namespace App\Http\Requests\Borrow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BorrowBookRequest extends FormRequest
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
            'book_id' => 'required|exists:books,id',
            'borrow_days' => 'nullable|integer|min:1|max:30',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'book_id.required' => 'Book is required',
            'book_id.exists' => 'Selected book does not exist',
            'borrow_days.integer' => 'Borrow days must be a number',
            'borrow_days.min' => 'Borrow days must be at least 1 day',
            'borrow_days.max' => 'Borrow days cannot exceed 30 days',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
