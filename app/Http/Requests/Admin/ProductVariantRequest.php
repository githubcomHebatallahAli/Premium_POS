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
            // 'product_id' => 'required|exists:products,id',
            // 'color' => 'nullable|string',
            // 'size' => 'nullable|string',
            // 'clothes' => 'nullable|string',
            // 'sku' => 'nullable|string|unique:product_variants,sku,'.$this->id,
            // 'barcode' => 'nullable|string|unique:product_variants,barcode,'.$this->id,
            // 'sellingPrice' => 'required|numeric|min:0',
            // 'images' => 'nullable|array',
            // 'images.*'=>'nullable|image|mimes:jpg,jpeg,png,gif,svg',
            // 'creationDate'=> 'nullable|date_format:Y-m-d H:i:s',
            // 'notes' => 'nullable|string',


            'name' => 'required|string|max:255',
            'sellingPrice' => 'required|numeric|min:0',
            'mainImage.*'=>'nullable|image|mimes:jpg,jpeg,png,gif,svg',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'nullable|string',
            'country' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode',
            'sku' => 'nullable|string|unique:products,sku,'.$this->id,
            
            'variants' => 'nullable|array',
            'variants.*.color' => 'nullable|string',
            'variants.*.size' => 'nullable|string',
            'variants.*.clothes' => 'nullable|in:sm,md,lg,xl,2xl,3xl,4xl,5xl,6xl,+xl',
            'variants.*.sellingPrice' => 'nullable|numeric|min:0',
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg',
            'variants.*.barcode' => 'nullable|string|unique:product_variants,barcode',
            'variants.*.sku' => 'nullable|string|unique:product_variants,sku',
            'variants.*.notes' => 'nullable|string'
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
