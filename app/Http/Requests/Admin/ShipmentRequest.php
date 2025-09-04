<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ShipmentRequest extends FormRequest
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
            'supplier_id' => 'required|exists:suppliers,id',
            //  'supplierName' => 'required|string',
             'importer' => 'nullable|string',
            'admin_id' =>'nullable|exists:admins,id',
             'creationDate'=> 'nullable|date_format:Y-m-d H:i:s',
             'paidAmount' => 'required|numeric|min:0',
             'status'=> 'nullable|in:pending,paid,partialReturn,return',
            'discount' => 'nullable|numeric|min:0',
            'extraAmount' => 'nullable|numeric|min:0',
            'discountType' => 'nullable|in:pounds,percentage',
            'taxType' => 'nullable|in:pounds,percentage',
            'payment'=> 'nullable|in:visa,cash,wallet,instapay',
             'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.unitPrice' => 'nullable|numeric|min:0',
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
