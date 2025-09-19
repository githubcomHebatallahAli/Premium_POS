<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\ShipmentProduct;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
public function create(array $data): Invoice
{
    return DB::transaction(function () use ($data) {
        $invoice = Invoice::create([
            'customerName'   => $data['customerName'],
            'customerPhone'  => $data['customerPhone'],
            'admin_id'       => auth()->id(),
            'payment'        => $data['payment'] ?? null,
            'pullType'       => $data['pullType'],
            'discount'       => $data['discount'] ?? 0,
            'discountType'   => $data['discountType'] ?? null,
            'extraAmount'    => $data['extraAmount'] ?? 0,
            'taxType'        => $data['taxType'] ?? null,
            'paidAmount'     => $data['paidAmount'] ?? 0,
            'creationDate'   => now(),
        ]);

        $total  = 0;
        $profit = 0;

        foreach ($data['products'] as $productData) {
            $productId = $productData['id'];
            $variantId = $productData['product_variant_id'] ?? null;
            $quantity  = $productData['quantity'];

            $product = Product::findOrFail($productId);

            $lineTotal  = 0;
            $lineProfit = 0;

            if ($data['pullType'] === 'fifo') {
                $availableStocks = ShipmentProduct::where('product_id', $productId)
                    ->when($variantId, fn($q) => $q->where('product_variant_id', $variantId))
                    ->where('remainingQuantity', '>', 0)
                    ->orderBy('created_at')
                    ->get();

                $remainingNeeded = $quantity;

                foreach ($availableStocks as $stock) {
                    if ($remainingNeeded <= 0) break;

                    $takeQty = min($remainingNeeded, $stock->remainingQuantity);
                    $stock->decrement('remainingQuantity', $takeQty);

                    $sellingPrice  = $product->sellingPrice;
                    $purchasePrice = $stock->unitPrice;

                    $subTotal  = $takeQty * $sellingPrice;
                    $subProfit = ($sellingPrice - $purchasePrice) * $takeQty;

                    $invoice->products()->attach($product->id, [
                        'shipment_product_id' => $stock->id,
                        'product_variant_id'  => $variantId,
                        'quantity'            => $takeQty,
                        'total'               => $subTotal,
                        'profit'              => $subProfit,
                    ]);

                    $lineTotal  += $subTotal;
                    $lineProfit += $subProfit;
                    $remainingNeeded -= $takeQty;
                }

                if ($remainingNeeded > 0) {
                    throw new \Exception("Not enough stock for product {$product->name}");
                }
            } else {
                $shipmentProduct = ShipmentProduct::findOrFail($productData['shipment_product_id']);

                if ($shipmentProduct->remainingQuantity < $quantity) {
                    throw new \Exception("Not enough stock for product {$product->name}");
                }

                $shipmentProduct->decrement('remainingQuantity', $quantity);

                $sellingPrice  = $product->sellingPrice;
                $purchasePrice = $shipmentProduct->unitPrice;

                $lineTotal  = $sellingPrice * $quantity;
                $lineProfit = ($sellingPrice - $purchasePrice) * $quantity;

                $invoice->products()->attach($product->id, [
                    'shipment_product_id' => $shipmentProduct->id,
                    'product_variant_id'  => $variantId,
                    'quantity'            => $quantity,
                    'total'               => $lineTotal,
                    'profit'              => $lineProfit,
                ]);
            }

            $total  += $lineTotal;
            $profit += $lineProfit;
        }

        $this->calculateTotals($invoice, $total, $profit);
        $invoice->updateInvoiceProductCount();

        return $invoice->fresh(['products.variants', 'invoiceProducts.shipmentProduct']);
    });
}

public function update(Invoice $invoice, array $data): Invoice
{
    return DB::transaction(function () use ($invoice, $data) {
        // امسح المنتجات القديمة
        $invoice->products()->detach();

        // تحديث بيانات الفاتورة الأساسية
        $invoice->update([
            'customerName' => $data['customerName'],
            'customerPhone'=> $data['customerPhone'],
            'payment'      => $data['payment'] ?? $invoice->payment,
            'pullType'     => $data['pullType'],
            'discount'     => $data['discount'] ?? 0,
            'discountType' => $data['discountType'] ?? $invoice->discountType,
            'extraAmount'  => $data['extraAmount'] ?? 0,
            'taxType'      => $data['taxType'] ?? $invoice->taxType,
            'paidAmount'   => $data['paidAmount'] ?? 0,
        ]);

        $total  = 0;
        $profit = 0;

        foreach ($data['products'] as $p) {
            $product   = Product::findOrFail($p['id']);
            $variantId = $p['product_variant_id'] ?? null;
            $quantity  = $p['quantity'];

            $lineTotal  = 0;
            $lineProfit = 0;

            if ($data['pullType'] === 'fifo') {
                // نفس منطق create: توزيع الكمية على كل الشحنات المتاحة
                $availableStocks = ShipmentProduct::where('product_id', $product->id)
                    ->when($variantId, fn($q) => $q->where('product_variant_id', $variantId))
                    ->where('remainingQuantity', '>', 0)
                    ->orderBy('created_at')
                    ->get();

                $remainingNeeded = $quantity;

                foreach ($availableStocks as $stock) {
                    if ($remainingNeeded <= 0) break;

                    $takeQty = min($remainingNeeded, $stock->remainingQuantity);
                    $stock->decrement('remainingQuantity', $takeQty);

                    $sellingPrice  = $product->sellingPrice;
                    $purchasePrice = $stock->unitPrice;

                    $subTotal  = $takeQty * $sellingPrice;
                    $subProfit = ($sellingPrice - $purchasePrice) * $takeQty;

                    $invoice->products()->attach($product->id, [
                        'shipment_product_id' => $stock->id,
                        'product_variant_id'  => $variantId,
                        'quantity'            => $takeQty,
                        'total'               => $subTotal,
                        'profit'              => $subProfit,
                    ]);

                    $lineTotal  += $subTotal;
                    $lineProfit += $subProfit;
                    $remainingNeeded -= $takeQty;
                }

                if ($remainingNeeded > 0) {
                    throw new \Exception("Not enough stock for product {$product->name}");
                }
            } else {
                // Manual
                $shipmentProduct = ShipmentProduct::findOrFail($p['shipment_product_id']);

                if ($shipmentProduct->remainingQuantity < $quantity) {
                    throw new \Exception("Not enough stock for product {$product->name}");
                }

                $shipmentProduct->decrement('remainingQuantity', $quantity);

                $sellingPrice  = $product->sellingPrice;
                $purchasePrice = $shipmentProduct->unitPrice;

                $lineTotal  = $sellingPrice * $quantity;
                $lineProfit = ($sellingPrice - $purchasePrice) * $quantity;

                $invoice->products()->attach($product->id, [
                    'shipment_product_id' => $shipmentProduct->id,
                    'product_variant_id'  => $variantId,
                    'quantity'            => $quantity,
                    'total'               => $lineTotal,
                    'profit'              => $lineProfit,
                ]);
            }

            $total  += $lineTotal;
            $profit += $lineProfit;
        }

        // إعادة حساب الإجماليات وتحديث الفاتورة
        $this->calculateTotals($invoice, $total, $profit);
        $invoice->updateInvoiceProductCount();

        return $invoice->fresh(['products.variants', 'invoiceProducts.shipmentProduct']);
    });
}


    // public function fullReturn(Invoice $invoice): Invoice
    // {
    //     return DB::transaction(function () use ($invoice) {
    //         foreach ($invoice->products as $p) {
    //             $shipmentProduct = ShipmentProduct::findOrFail($p->pivot->shipment_product_id);
    //             $shipmentProduct->increment('remainingQuantity', $p->pivot->quantity);
    //         }

    //         $invoice->update([
    //             'status'       => 'return',
    //             'returnReason' => request('returnReason', 'إرجاع كامل'),
    //         ]);

    //         $invoice->updateInvoiceProductCount();

    //         return $invoice->fresh(['products']);
    //     });
    // }

public function fullReturn(Invoice $invoice): Invoice
{
    return DB::transaction(function () use ($invoice) {
        foreach ($invoice->products as $p) {
            $shipmentProduct = ShipmentProduct::findOrFail($p->pivot->shipment_product_id);
            $shipmentProduct->increment('remainingQuantity', $p->pivot->quantity);
        }

        // فك ارتباط كل المنتجات من الفاتورة
        $invoice->products()->detach();

        // تحديث حالة الفاتورة
        $invoice->update([
            'status'        => 'return',
            'returnReason'  => request('returnReason', 'إرجاع كامل'),
            'totalInvoicePrice'    => 0,
            'invoiceAfterDiscount' => 0,
            'profit'               => 0,
            'remainingAmount'      => 0,
        ]);

        $invoice->updateInvoiceProductCount();

        return $invoice->fresh(['products']);
    });
}

public function partialReturn(Invoice $invoice, array $products): Invoice
{
    return DB::transaction(function () use ($invoice, $products) {
        $globalReason = request('returnReason', 'إرجاع جزئي');

        foreach ($products as $p) {
            $product = $invoice->products()->where('product_id', $p['id'])->first();
            if (!$product) continue;

            $pivot = $product->pivot;
            $returnQty = min($p['quantity'], $pivot->quantity);

            $shipmentProduct = ShipmentProduct::findOrFail($pivot->shipment_product_id);
            $shipmentProduct->increment('remainingQuantity', $returnQty);

            $newQty = $pivot->quantity - $returnQty;

            if ($newQty > 0) {
                $invoice->products()->updateExistingPivot($product->id, [
                    'quantity'     => $newQty,
                    'total'        => $product->sellingPrice * $newQty,
                    'profit'       => ($product->sellingPrice - $shipmentProduct->unitPrice) * $newQty,
                    'returnReason' => $p['reason'] ?? $globalReason,
                ]);
            } else {
                $invoice->products()->updateExistingPivot($product->id, [
                    'returnReason' => $p['reason'] ?? $globalReason,
                ]);
                $invoice->products()->detach($product->id);
            }
        }

        // إعادة حساب الإجماليات
        $total  = $invoice->products->sum(fn($prod) => $prod->pivot->total);
        $profit = $invoice->products->sum(fn($prod) => $prod->pivot->profit);

        $this->calculateTotals($invoice, $total, $profit);

        // تحديث الحالة وسبب الإرجاع
        $invoice->update([
            'status'       => 'partialReturn',
            'returnReason' => $globalReason,
        ]);

        $invoice->updateInvoiceProductCount();

        return $invoice->fresh(['products']);
    });
}



public function calculateTotals(Invoice $invoice, float $total, float $profit): void
{
    $discount = $invoice->discount ?? 0;
    $extra    = $invoice->extraAmount ?? 0;

    // حساب الخصم
    if ($invoice->discountType === 'percentage' && $discount > 0) {
        $discountAmount = ($total * $discount) / 100;
    } else {
        $discountAmount = $discount;
    }

    // حساب الإضافي (ضريبة أو رسوم)
    if ($invoice->taxType === 'percentage' && $extra > 0) {
        $extraAmount = ($total * $extra) / 100;
    } else {
        $extraAmount = $extra;
    }

    // الإجمالي النهائي
    $final = $total - $discountAmount + $extraAmount;

    // المتبقي بعد الدفع
    $remaining = $final - ($invoice->paidAmount ?? 0);

    // تحديد الحالة
    $status = $remaining <= 0 ? 'completed' : 'indebted';

    // تحديث الفاتورة
    $invoice->update([
        'totalInvoicePrice'    => $total,
        'invoiceAfterDiscount' => $final,
        'profit'               => $profit,
        'remainingAmount'      => $remaining > 0 ? $remaining : 0,
        'status'               => $status,
    ]);
}


public function recalculateTotals(Invoice $invoice): void
{
    // حساب الإجمالي والربح من المنتجات
    $total  = $invoice->products->sum(fn($p) => $p->pivot->total);
    $profit = $invoice->products->sum(fn($p) => $p->pivot->profit);

    // استدعاء الدالة اللي بتعمل الحسابات والتحديث
    $this->calculateTotals($invoice, $total, $profit);

    // تحديث عدد المنتجات في الفاتورة
    $invoice->updateInvoiceProductCount();
}

}


