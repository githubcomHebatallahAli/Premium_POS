<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ProductRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string',
            // 'quantity'=> 'required|string',
            'sellingPrice' => 'required|numeric|min:0',
            'image.*'=>'nullable|image|mimes:jpg,jpeg,png,gif,svg',
            'creationDate'=> 'nullable|date_format:Y-m-d H:i:s',
            'color' => 'nullable|string',
            'size' => 'nullable|string',
            'clothes' => 'nullable|string',
            'country' => 'nullable|string',
            'endDate' => 'nullable|date_format:Y-m-d',
            'code' => 'nullable|string|unique:products,code,'.$this->id,
            'description' => 'nullable|string',
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
