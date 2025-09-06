<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'product_id' => 'required|exists:products,id',
            'color' => 'nullable|string',
            'size' => 'nullable|string',
            'clothes' => 'nullable|string',
            'sku' => 'nullable|string|unique:product_variants,sku,'.$this->id,
            'barcode' => 'nullable|string|unique:product_variants,barcode,'.$this->id,
            'sellingPrice' => 'required|numeric|min:0',
            'images' => 'nullable|array',
            'images.*'=>'nullable|image|mimes:jpg,jpeg,png,gif,svg',
            'creationDate'=> 'nullable|date_format:Y-m-d H:i:s',
            'notes' => 'nullable|string',
        ];
    }
}
