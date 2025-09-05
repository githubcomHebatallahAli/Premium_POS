<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            // تحديد سعر الشراء للقطعة
            $unitPrice = $productData['unitPrice'] ?? null;
            
            // إذا سعر القطعة مش متوفر، نحسبه من سعر الجملة
            if (empty($unitPrice) && isset($productData['price'])) {
                $unitPrice = $productData['price'] / $productData['quantity'];
            }

            // سعر الجملة الإجمالي
            $totalPrice = $productData['price'] ?? $productData['quantity'] * $unitPrice;

            // إنشاء منتج الشحنة في جدول shipment_products
            ShipmentProduct::create([
                'shipment_id' => $shipment->id,
                'product_id' => $product->id,
                'quantity' => $productData['quantity'],
                'unitPrice' => $unitPrice,  // سعر الشراء للقطعة
                'price' => $totalPrice,     // سعر الشراء الإجمالي للكمية
            ]);

            $total += $totalPrice;
        }

        // حساب الإجماليات النهائية للشحنة
        $this->calculateTotals($shipment, $total);

        // تحديث عدد المنتجات في الشحنة
        $shipment->updateShipmentProductsCount();

        return $shipment->fresh(['products', 'supplier']);
    });
}

public function calculateTotals(Shipment $shipment, float $total): void
{
    // تأكد من أن القيم ليست null
    $discount = $shipment->discount ?? 0;
    $extra = $shipment->extraAmount ?? 0;

    // إذا discountType هو percentage نحسب النسبة
    if ($shipment->discountType === 'percentage' && $discount > 0) {
        $discountAmount = ($total * $discount) / 100;
    } else {
        $discountAmount = $discount;
    }

    // إذا taxType هو percentage نحسب النسبة
    if ($shipment->taxType === 'percentage' && $extra > 0) {
        $extraAmount = ($total * $extra) / 100;
    } else {
        $extraAmount = $extra;
    }

    // حساب الإجمالي النهائي
    $final = $total - $discountAmount + $extraAmount;
    
    $remaining = $final - ($shipment->paidAmount ?? 0);

    // تحديد الحالة
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
        // حذف المنتجات القديمة من جدول shipment_products
        $shipment->products()->detach();

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

            // تحديد سعر الشراء للقطعة
            $unitPrice = $productData['unitPrice'] ?? null;
            
            // إذا سعر القطعة مش متوفر، نحسبه من سعر الجملة
            if (empty($unitPrice) && isset($productData['price'])) {
                $unitPrice = $productData['price'] / $productData['quantity'];
            }

            // سعر الجملة الإجمالي
            $totalPrice = $productData['price'] ?? $productData['quantity'] * $unitPrice;

            // إضافة المنتج للشحنة في جدول shipment_products
            $shipment->products()->attach($product->id, [
                'quantity' => $productData['quantity'],
                'unitPrice' => $unitPrice,
                'price' => $totalPrice,
            ]);

            $total += $totalPrice;
        }

        // حساب الإجماليات النهائية للشحنة
        $this->calculateTotals($shipment, $total);

        // تحديث عدد المنتجات في الشحنة
        $shipment->updateShipmentProductsCount();

        return $shipment->fresh(['products', 'supplier']);
    });
}


public function fullReturn(Shipment $shipment): Shipment
{
    return DB::transaction(function () use ($shipment) {
        // فقط نغير حالة الشحنة بدون ما نلمس أي جدول تاني
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

            // تحديث كمية الشحنة في جدول shipment_products
            $newQuantity = $product->pivot->quantity - $returnQty;
            
            if ($newQuantity > 0) {
                $shipment->products()->updateExistingPivot($product->id, [
                    'quantity' => $newQuantity,
                    'price' => $product->pivot->unitPrice * $newQuantity
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

        // إعادة حساب إجماليات الشحنة
        $total = $shipment->products->sum(function($product) {
            return $product->pivot->price;
        });

        $this->calculateTotals($shipment, $total);

        $shipment->update([
            'status' => 'partialReturn',
            'returnReason' => json_encode($returnedProducts)
        ]);

        return $shipment->fresh('products');
    });
}

public function recalculateTotals(Shipment $shipment): void
{
    // نحسب الـ total من المنتجات
    $total = $shipment->products->sum(function($product) {
        return $product->pivot->price;
    });
    
    // ثم نستدعي الدالة الأصلية
    $this->calculateTotals($shipment, $total);
}


}