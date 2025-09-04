<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class InvoiceRequest extends FormRequest
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
            'creationDate'=> 'nullable|date_format:Y-m-d H:i:s',
            'customerName' => 'required|string',
            'customerPhone' => 'required|string',
            // 'sellerName' => 'required|string',
            'admin_id' =>'nullable|exists:admins,id',
            'status'=> 'nullable|in:completed,return,indebted,partialReturn',
            'returnReason'=> 'nullable|string',
            'payment'=> 'nullable|in:visa,cash,wallet,instapay',
            'pullType' => 'required|in:fifo,manual', 
            'discount' => 'nullable|numeric|min:0',
            'discountType' => 'nullable|in:percentage,pounds',
            'taxType' => 'nullable|in:percentage,pounds',
            'extraAmount' => 'nullable|numeric|min:0',
            'paidAmount' => 'nullable|numeric|min:0',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            "products.*.shipment_id" => "required_if:pullType,manual|exists:shipment_products,shipment_id",
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.returnReason' => 'nullable|string',

            // 'discount' => 'nullable|numeric|min:0',
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
