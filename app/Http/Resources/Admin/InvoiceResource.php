<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Auth\AdminRegisterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            'customerName' => $this->customerName,
            'customerPhone' => $this->customerPhone,
            'admin' => new AdminRegisterResource($this->admin),
            'invoiceProductCount' => $this->invoiceProductCount,
            'creationDate' => $this->creationDate,
            'status' => $this->status,
            'payment' => $this->payment,
            'pullType' => $this->pullType,
            'discountType' => $this->discountType,
            'taxType' => $this->taxType,
            'returnReason' => $this->returnReason,

            // ✅ المبالغ المالية
            'totalInvoicePrice'   => number_format($this->totalInvoicePrice, 2, '.', ''),
            'discount'            => number_format($this->discount ?? 0, 2, '.', ''),
            'extraAmount'         => number_format($this->extraAmount ?? 0, 2, '.', ''),
            'invoiceAfterDiscount'=> number_format($this->invoiceAfterDiscount ?? 0, 2, '.', ''),
            // 'profit'              => number_format($this->profit ?? 0, 2, '.', ''),
            'paidAmount'          => number_format($this->paidAmount ?? 0, 2, '.', ''),
            'remainingAmount'     => number_format($this->remainingAmount ?? 0, 2, '.', ''),

            
            'products' => $this->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'categoryName' => $product->category->name ?? null, 
                    'brandName' => $product->brand->name ?? null, 
                    'sellingPrice' => $product->sellingPrice,
                    'quantity' => $product->pivot->quantity,
                    'total' => $product->pivot->total,
                    'shipment_product_id' => $product->pivot->shipment_product_id,
                    'product_variant_id' => $product->pivot->product_variant_id,
                    'returnReason' => $product->pivot->returnReason,
                    // 'profit' => $product->pivot->profit
                ];
            }),
        ];
    }
}
