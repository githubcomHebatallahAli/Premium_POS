<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentProduct;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
public function create(array $data): Shipment
{
    return DB::transaction(function () use ($data) {
        $shipment = Shipment::create([
            'supplier_id' => $data['supplier_id'],
            'importer' => $data['importer'] ?? null,
            'admin_id' => auth()->id(),
            'discount' => $data['discount'] ?? 0,
            'discountType' => $data['discountType'] ?? null,
            'extraAmount' => $data['extraAmount'] ?? 0,
            'taxType' => $data['taxType'] ?? null,
            'paidAmount' => $data['paidAmount'] ?? 0,
            'creationDate' => now(),
            'payment' => $data['payment'] ?? null,
        ]);

        $total = 0;

        foreach ($data['products'] as $productData) {
            $productId = $productData['id'] ?? $productData['product_id'] ?? null;
            
            if (!$productId) {
                throw new \Exception("Product ID is required for all products");
            }

            $product = Product::findOrFail($productId);

            $unitPrice = $productData['unitPrice'] ?? null;
            
            if (empty($unitPrice) && isset($productData['price'])) {
                $unitPrice = $productData['price'] / $productData['quantity'];
            }

            $totalPrice = $productData['price'] ?? $productData['quantity'] * $unitPrice;

            
            ShipmentProduct::create([
                'shipment_id' => $shipment->id,
                'product_id' => $product->id,
                'product_variant_id' => $productData['product_variant_id'],
                'quantity' => $productData['quantity'],
                'remainingQuantity' => $productData['quantity'],
                'unitPrice' => $unitPrice,
                'price' => $totalPrice,
                'endDate' => $productData['endDate'] ?? null,
            ]);

            $total += $totalPrice;
        }

        $this->calculateTotals($shipment, $total);
        $shipment->updateShipmentProductsCount();
        $shipment->fresh(['products.variants', 'supplier', 'shipmentProducts.variant']);

        
        return $shipment;
    }); 
}

public function calculateTotals(Shipment $shipment, float $total): void
{
    $discount = $shipment->discount ?? 0;
    $extra = $shipment->extraAmount ?? 0;

    if ($shipment->discountType === 'percentage' && $discount > 0) {
        $discountAmount = ($total * $discount) / 100;
    } else {
        $discountAmount = $discount;
    }

   
    if ($shipment->taxType === 'percentage' && $extra > 0) {
        $extraAmount = ($total * $extra) / 100;
    } else {
        $extraAmount = $extra;
    }

   
    $final = $total - $discountAmount + $extraAmount;
    
    $remaining = $final - ($shipment->paidAmount ?? 0);

    
    if ($remaining <= 0) {
        $status = 'completed';
    } else {
        $status = 'indebted';
    }

    $shipment->update([
        'totalPrice' => $total,
        'invoiceAfterDiscount' => $final,
        'remainingAmount' => $remaining > 0 ? $remaining : 0,
        'status' => $status
    ]);

    $shipment->updateShipmentProductsCount();
}



public function update(Shipment $shipment, array $data): Shipment
{
    return DB::transaction(function () use ($shipment, $data) {
        // حذف جميع المنتجات المرتبطة بالشحنة باستخدام نموذج Pivot
        $shipment->shipmentProducts()->delete();

        
        $shipment->update([
            'supplier_id' => $data['supplier_id'],
            'importer' => $data['importer'] ?? $shipment->importer,
            'discount' => $data['discount'] ?? $shipment->discount,
            'discountType' => $data['discountType'] ?? $shipment->discountType,
            'extraAmount' => $data['extraAmount'] ?? $shipment->extraAmount,
            'taxType' => $data['taxType'] ?? $shipment->taxType,
            'paidAmount' => $data['paidAmount'] ?? $shipment->paidAmount,
            'payment' => $data['payment'] ?? $shipment->payment,
        ]);

        $total = 0;

        foreach ($data['products'] as $productData) {
            $productId = $productData['id'] ?? $productData['product_id'] ?? null;
            
            if (!$productId) {
                throw new \Exception("Product ID is required for all products");
            }

            $product = Product::findOrFail($productId);

            $unitPrice = $productData['unitPrice'] ?? null;
            
            if (empty($unitPrice) && isset($productData['price'])) {
                $unitPrice = $productData['price'] / $productData['quantity'];
            }

            $totalPrice = $productData['price'] ?? $productData['quantity'] * $unitPrice;

            ShipmentProduct::create([
                'shipment_id' => $shipment->id,
                'product_id' => $product->id,
                'product_variant_id' => $productData['product_variant_id'] ?? null,
                'quantity' => $productData['quantity'],
                'unitPrice' => $unitPrice,
                'price' => $totalPrice,
                'endDate' => $productData['endDate'] ?? null,
            ]);

            $total += $totalPrice;
        }

       
        $this->calculateTotals($shipment, $total);

        $shipment->updateShipmentProductsCount();

        return $shipment->fresh(['products.variants', 'supplier', 'shipmentProducts.variant']);
    });
}


public function fullReturn(Shipment $shipment): Shipment
{
    return DB::transaction(function () use ($shipment) {
        
        foreach ($shipment->products as $product) {
            $shipment->products()->updateExistingPivot($product->id, [
                'returnReason' => request('returnReason', 'إرجاع كامل')
            ]);
        }

        $shipment->update([
            'status' => 'return',
            'returnReason' => request('returnReason', 'إرجاع كامل')
        ]);

        return $shipment->fresh('products');
    });
}


public function partialReturn(Shipment $shipment, array $products): Shipment
{
    return DB::transaction(function () use ($shipment, $products) {
        $globalReturnReason = request('returnReason', 'إرجاع جزئي');

        foreach ($products as $productData) {
            $product = $shipment->products()->where('product_id', $productData['id'])->first();
            
            if (!$product) continue;

            $returnQty = min($productData['quantity'], $product->pivot->quantity);
            $reason = $productData['reason'] ?? $globalReturnReason;

            $newQuantity = $product->pivot->quantity - $returnQty;
            
            if ($newQuantity > 0) {
                $shipment->products()->updateExistingPivot($product->id, [
                    'quantity' => $newQuantity,
                    'price' => $product->pivot->unitPrice * $newQuantity,
                    'returnReason' => $reason
                ]);
            } else {
                $shipment->products()->updateExistingPivot($product->id, [
                    'returnReason' => $reason
                ]);
                $shipment->products()->detach($product->id);
            }
        }

        $total = $shipment->products->sum(function($product) {
            return $product->pivot->price;
        });

        $this->calculateTotals($shipment, $total);

        $shipment->update([
            'status' => 'partialReturn',
            'returnReason' => $globalReturnReason
        ]);

        return $shipment->fresh(['products', 'supplier']);
    });
}

public function recalculateTotals(Shipment $shipment): void
{
    $total = $shipment->products->sum(function($product) {
        return $product->pivot->price;
    });
    
    $this->calculateTotals($shipment, $total);
}


}