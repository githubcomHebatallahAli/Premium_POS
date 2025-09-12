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
            'name' => 'required|string|max:255',
            'sellingPrice' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'nullable|string',
            'country' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode,' . $this->id,
            'sku' => 'nullable|string|unique:products,sku,'.$this->id,
            'image_ids'   => 'nullable|array',
            'image_ids.*' => 'exists:images,id',
            
            'variants' => 'nullable|array',
            'variants.*.color' => 'nullable|string',
            'variants.*.size' => 'nullable|string',
            'variants.*.clothes' => 'nullable|in:sm,md,lg,xl,2xl,3xl,4xl,5xl,6xl,+xl',
            'variants.*.sellingPrice' => 'nullable|numeric|min:0',
            'variants.*.barcode' => 'nullable|string',
            'variants.*.sku' => 'nullable|string|unique:product_variants,sku',
            'variants.*.notes' => 'nullable|string',
            'variants.*.images'   => 'nullable|array',
            'variants.*.images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg',
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
