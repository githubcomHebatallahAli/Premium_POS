<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ProductVariantRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sellingPrice' => 'nullable|numeric|min:0',
            'product_id' => 'required|exists:products,id',
            'notes' => 'nullable|string',
            'color' => 'nullable|string',
            'size' => 'nullable|string',
            'barcode' => 'nullable|string',
            'sku' => 'nullable|string|unique:products,sku,'.$this->id,
            'clothes' => 'nullable|in:sm,md,lg,xl,2xl,3xl,4xl,5xl,6xl,+xl',
        ];
    }

        public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));
    }
}
