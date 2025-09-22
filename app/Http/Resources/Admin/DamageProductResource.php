<?php

namespace App\Http\Resources\Admin;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DamageProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 'product_id' => $this->product_id,
            // 'product_variant_id' => $this->product_variant_id,
            'shipmentProduct' => new ShipmentProductResource($this->whenLoaded('shipment')),
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'status' => $this->status,
            'creationDate' => $this->creationDate,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
        ];
    }
}
