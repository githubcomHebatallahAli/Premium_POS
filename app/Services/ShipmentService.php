<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
public function create(array $data): Shipment
{
    return DB::transaction(function () use ($data) {
        // إنشاء الشحنة
        $shipment = Shipment::create([
            'supplier_id' => $data['supplier_id'],
            'importer' => $data['importer'] ?? null,
            'admin_id' => auth()->id(),
            'discount' => $data['discount'] ?? 0,
            'discountType' => $data['discountType'] ?? 'percentage',
            'extraAmount' => $data['extraAmount'] ?? 0,
            'taxType' => $data['taxType'] ?? 'percentage',
            'paidAmount' => $data['paidAmount'] ?? 0,
            'creationDate' => now(),
            'payment' => $data['payment'] ?? 'cash',
        ]);

        $total = 0;

        foreach ($data['products'] as $productData) {
            // استخدام 'id' إذا كان هو الـ key المرسل
            $productId = $productData['id'] ?? $productData['product_id'] ?? null;
            
            if (!$productId) {
                throw new \Exception("Product ID is required for all products");
            }

            $product = Product::findOrFail($productId);

            // إنشاء منتج الشحنة
            $shipmentProduct = ShipmentProduct::create([
                'shipment_id' => $shipment->id,
                'product_id' => $product->id,
                'quantity' => $productData['quantity'],
                'unitPrice' => $productData['unitPrice'],
                'totalPrice' => $productData['quantity'] * $productData['unitPrice'],
            ]);

            // تحديث أسعار المنتج
            $product->update([
                'purchasePrice' => $productData['unitPrice'],
                'sellingPrice' => $productData['unitPrice'] * 1.2 // هامش ربح 20%
            ]);

            $total += $shipmentProduct->totalPrice;
        }

        // حساب الإجماليات النهائية
        $this->calculateTotals($shipment, $total);

        return $shipment->fresh(['products.product', 'supplier']);
    });
}

    public function update(Shipment $shipment, array $data): Shipment
    {
        return DB::transaction(function () use ($shipment, $data) {
            // حذف المنتجات القديمة
            $shipment->products()->delete();

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
                $product = Product::findOrFail($productData['product_id']);

                $shipmentProduct = ShipmentProduct::create([
                    'shipment_id' => $shipment->id,
                    'product_id' => $product->id,
                    'quantity' => $productData['quantity'],
                    'unitPrice' => $productData['unitPrice'],
                    'totalPrice' => $productData['quantity'] * $productData['unitPrice'],
                ]);

                $total += $shipmentProduct->totalPrice;
            }

            $this->calculateTotals($shipment, $total);

            return $shipment->fresh(['products', 'supplier']);
        });
    }

public function calculateTotals(Shipment $shipment, float $total): void
{
    $discount = $shipment->discount ?? 0;
    if ($shipment->discountType === 'percentage') {
        $discount = ($total * $discount) / 100;
    }

    $extra = $shipment->extraAmount ?? 0;
    if ($shipment->taxType === 'percentage') {
        $extra = ($total * $extra) / 100;
    }

    $final = $total - $discount + $extra;
    $remaining = $final - ($shipment->paidAmount ?? 0);

    // تحديد الحالة - نفس الفاتورة بالظبط
    if ($remaining <= 0) {
        $status = 'completed'; // مدفوع بالكامل
    } else {
        $status = 'indebted'; // غير مكتمل الدفع
    }

    $shipment->update([
        'totalPrice' => $total,
        'invoiceAfterDiscount' => $final,
        'remainingAmount' => $remaining > 0 ? $remaining : 0,
        'status' => $status
    ]);

    $shipment->updateShipmentProductCount();
}

public function fullReturn(Shipment $shipment): Shipment
{
    return DB::transaction(function () use ($shipment) {
        // إعادة الكميات للمنتجات
        foreach ($shipment->products as $product) {
            $product->increment('remainingQuantity', $product->pivot->quantity);
            
            // تحديث سعر الشراء إذا needed
            // $product->update(['purchasePrice' => ...]);
        }

        $shipment->update([
            'status' => 'return',
            'returnReason' => request('returnReason', 'Full return')
        ]);

        return $shipment->fresh('products');
    });
}

public function partialReturn(Shipment $shipment, array $products): Shipment
{
    return DB::transaction(function () use ($shipment, $products) {
        $returnedProducts = [];

        foreach ($products as $productData) {
            $product = $shipment->products()->where('product_id', $productData['id'])->first();
            
            if (!$product) continue;

            $returnQty = min($productData['quantity'], $product->pivot->quantity);

            // إعادة الكمية للمنتج
            $product->increment('remainingQuantity', $returnQty);

            // تحديث كمية الشحنة
            $newQuantity = $product->pivot->quantity - $returnQty;
            
            if ($newQuantity > 0) {
                $shipment->products()->updateExistingPivot($product->id, [
                    'quantity' => $newQuantity,
                    'totalPrice' => $product->pivot->unitPrice * $newQuantity
                ]);
            } else {
                $shipment->products()->detach($product->id);
            }

            $returnedProducts[] = [
                'product_id' => $product->id,
                'quantity' => $returnQty,
                'reason' => $productData['reason'] ?? 'Partial return'
            ];
        }

        // إعادة حساب الإجماليات
        $total = $shipment->products->sum(function($product) {
            return $product->pivot->quantity * $product->pivot->unitPrice;
        });

        $this->calculateTotals($shipment, $total);

        $shipment->update([
            'status' => 'partialReturn',
            'returnReason' => json_encode($returnedProducts)
        ]);

        return $shipment->fresh('products');
    });
}


}