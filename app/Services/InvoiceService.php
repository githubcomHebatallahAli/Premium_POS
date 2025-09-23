<?php

namespace App\Services;

use App\Models\Company;
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
            'company_id'     => Company::first()->id, 
            'description'    => $data['description'] ?? null,
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
       
        $invoice->products()->detach();
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

// public function fullReturn(Invoice $invoice): Invoice
// {
//     return DB::transaction(function () use ($invoice) {
//         foreach ($invoice->products as $p) {
//             $shipmentProduct = ShipmentProduct::findOrFail($p->pivot->shipment_product_id);
//             $shipmentProduct->increment('remainingQuantity', $p->pivot->quantity);
//         }

//         // فك ارتباط كل المنتجات من الفاتورة
//         $invoice->products()->detach();

//         // تحديث حالة الفاتورة
//         $invoice->update([
//             'status'        => 'return',
//             'returnReason'  => request('returnReason', 'إرجاع كامل'),
//             'totalInvoicePrice'    => 0,
//             'invoiceAfterDiscount' => 0,
//             'profit'               => 0,
//             'remainingAmount'      => 0,
//         ]);

//         $invoice->updateInvoiceProductCount();

//         return $invoice->fresh(['products']);
//     });
// }

// public function fullReturn(Invoice $invoice): Invoice
// {
//     return DB::transaction(function () use ($invoice) {
//         $reason = request('returnReason', 'إرجاع كامل');

//         foreach ($invoice->products as $product) {
//             $invoice->products()->updateExistingPivot($product->id, [
//                 'quantity'     => 0,
//                 'total'        => 0,
//                 'profit'       => 0,
//                 'returnReason' => $reason,
//             ]);

//             $shipmentProduct = ShipmentProduct::findOrFail($product->pivot->shipment_product_id);
//             $shipmentProduct->increment('remainingQuantity', $product->pivot->quantity);
//         }

//         $this->calculateTotals($invoice, 0, 0);

//         $invoice->update([
//             'status'       => 'return',
//             'returnReason' => $reason,
//         ]);

//         $invoice->updateInvoiceProductCount();

//         return $invoice->fresh(['products']);
//     });
// }

// public function partialReturn(Invoice $invoice, array $products): Invoice
// {
//     return DB::transaction(function () use ($invoice, $products) {
//         $globalReason = request('returnReason', 'إرجاع جزئي');

//         foreach ($products as $p) {
//             $product = $invoice->products()->where('product_id', $p['id'])->first();
//             if (!$product) continue;

//             $pivot     = $product->pivot;
//             $returnQty = min($p['quantity'], $pivot->quantity);

//             $shipmentProduct = ShipmentProduct::findOrFail($pivot->shipment_product_id);
//             $shipmentProduct->increment('remainingQuantity', $returnQty);

//             $newQty = $pivot->quantity - $returnQty;
//             $reason = $p['reason'] ?? $globalReason;

//             if ($newQty > 0) {
//                 $invoice->products()->updateExistingPivot($product->id, [
//                     'quantity'     => $newQty,
//                     'total'        => $product->sellingPrice * $newQty,
//                     'profit'       => ($product->sellingPrice - $shipmentProduct->unitPrice) * $newQty,
//                     'returnReason' => $reason,
//                 ]);
//             } else {
                
//                 $invoice->products()->updateExistingPivot($product->id, [
//                     'quantity'     => 0,
//                     'total'        => 0,
//                     'profit'       => 0,
//                     'returnReason' => $reason,
//                 ]);
//             }
//         }
//         $invoice->load('products');

//         $total  = $invoice->products->sum(fn($prod) => $prod->pivot->total);
//         $profit = $invoice->products->sum(fn($prod) => $prod->pivot->profit);

//         $this->calculateTotals($invoice, $total, $profit);

//         $invoice->update([
//             'status'       => 'partialReturn',
//             'returnReason' => $globalReason,
//         ]);

//         $invoice->updateInvoiceProductCount();

//         return $invoice->fresh(['products']);
//     });
// }


public function fullReturn(Invoice $invoice): Invoice
{
    return DB::transaction(function () use ($invoice) {
        $reason = request('returnReason', 'إرجاع كامل');

        
        $total = $invoice->products->sum(fn($p) => $p->pivot->total);
        $profit = $invoice->products->sum(fn($p) => $p->pivot->profit);

      
        $discount = $invoice->discount ?? 0;
        $extra    = $invoice->extraAmount ?? 0;

        $discountAmount = ($invoice->discountType === 'percentage' && $discount > 0)
            ? ($total * $discount) / 100
            : $discount;

        $extraAmount = ($invoice->taxType === 'percentage' && $extra > 0)
            ? ($total * $extra) / 100
            : $extra;

        foreach ($invoice->products as $product) {
            $pivot = $product->pivot;

          
            $share = $pivot->total > 0 && $total > 0 ? $pivot->total / $total : 0;

          
            $productDiscount = round($discountAmount * $share, 2);
            $productExtra    = round($extraAmount * $share, 2);

            // السعر النهائي للمنتج بعد الخصم والضريبة
            $finalProductTotal = $pivot->total - $productDiscount + $productExtra;

            // تصفير الكمية والسعر والربح
            $invoice->products()->updateExistingPivot($product->id, [
                'quantity'     => 0,
                'total'        => 0,
                'profit'       => 0,
                'returnReason' => $reason,
            ]);

           
            $shipmentProduct = ShipmentProduct::findOrFail($pivot->shipment_product_id);
            $shipmentProduct->increment('remainingQuantity', $pivot->quantity);

            // ممكن تسجل قيمة المرتجع (finalProductTotal) في جدول منفصل لو محتاج تقارير دقيقة
        }

        // تصفير الإجماليات
        $this->calculateTotals($invoice, 0, 0);

        $invoice->update([
            'status'       => 'return',
            'returnReason' => $reason,
        ]);

        $invoice->updateInvoiceProductCount();

        return $invoice->fresh(['products']);
    });
}

public function partialReturn(Invoice $invoice, array $products): Invoice
{
    return DB::transaction(function () use ($invoice, $products) {
        $globalReason = request('returnReason', 'إرجاع جزئي');

        // إجمالي الفاتورة قبل الخصم والضريبة
        $total = $invoice->products->sum(fn($p) => $p->pivot->total);
        $profit = $invoice->products->sum(fn($p) => $p->pivot->profit);

        // حساب الخصم والضريبة الكلية
        $discount = $invoice->discount ?? 0;
        $extra    = $invoice->extraAmount ?? 0;

        $discountAmount = ($invoice->discountType === 'percentage' && $discount > 0)
            ? ($total * $discount) / 100
            : $discount;

        $extraAmount = ($invoice->taxType === 'percentage' && $extra > 0)
            ? ($total * $extra) / 100
            : $extra;

        foreach ($products as $p) {
            $product = $invoice->products()->where('product_id', $p['id'])->first();
            if (!$product) continue;

            $pivot     = $product->pivot;
            $returnQty = min($p['quantity'], $pivot->quantity);
            $reason    = $p['reason'] ?? $globalReason;
            $newQty    = $pivot->quantity - $returnQty;

            // نسبة مساهمة المنتج
            $share = $pivot->total > 0 && $total > 0 ? $pivot->total / $total : 0;

            // نصيب المنتج من الخصم والضريبة
            $productDiscount = round($discountAmount * $share, 2);
            $productExtra    = round($extraAmount * $share, 2);

            // السعر النهائي للمنتج بعد الخصم والضريبة
            $finalProductTotal = $pivot->total - $productDiscount + $productExtra;

            // قيمة المرتجع (بالنسبة للكمية المرتجعة فقط)
            $unitFinalPrice = $finalProductTotal / $pivot->quantity;
            $returnedValue  = $unitFinalPrice * $returnQty;

            // تحديث الكمية أو تصفيرها
            if ($newQty > 0) {
                $invoice->products()->updateExistingPivot($product->id, [
                    'quantity'     => $newQty,
                    'total'        => $product->sellingPrice * $newQty,
                    'profit'       => ($product->sellingPrice - $pivot->unitPrice) * $newQty,
                    'returnReason' => $reason,
                ]);
            } else {
                $invoice->products()->updateExistingPivot($product->id, [
                    'quantity'     => 0,
                    'total'        => 0,
                    'profit'       => 0,
                    'returnReason' => $reason,
                ]);
            }

            $shipmentProduct = ShipmentProduct::findOrFail($pivot->shipment_product_id);
            $shipmentProduct->increment('remainingQuantity', $returnQty);

            $total  -= $returnedValue;
            $profit -= ($product->sellingPrice - $pivot->unitPrice) * $returnQty;
        }

       
        $this->calculateTotals($invoice, $total, $profit);

        $invoice->update([
            'status'       => 'partialReturn',
            'returnReason' => $globalReason,
        ]);

        $invoice->updateInvoiceProductCount();

        return $invoice->fresh(['products']);
    });
}



// public function partialReturn(Invoice $invoice, array $products): Invoice
// {
//     return DB::transaction(function () use ($invoice, $products) {
//         $globalReason = request('returnReason', 'إرجاع جزئي');

//         foreach ($products as $p) {
//             $product = $invoice->products()->where('product_id', $p['id'])->first();
//             if (!$product) continue;

//             $pivot = $product->pivot;
//             $returnQty = min($p['quantity'], $pivot->quantity);

//             $shipmentProduct = ShipmentProduct::findOrFail($pivot->shipment_product_id);
//             $shipmentProduct->increment('remainingQuantity', $returnQty);

//             $newQty = $pivot->quantity - $returnQty;

//             if ($newQty > 0) {
//                 $invoice->products()->updateExistingPivot($product->id, [
//                     'quantity'     => $newQty,
//                     'total'        => $product->sellingPrice * $newQty,
//                     'profit'       => ($product->sellingPrice - $shipmentProduct->unitPrice) * $newQty,
//                     'returnReason' => $p['reason'] ?? $globalReason,
//                 ]);
//             } else {
//                 $invoice->products()->updateExistingPivot($product->id, [
//                     'returnReason' => $p['reason'] ?? $globalReason,
//                 ]);
//                 $invoice->products()->detach($product->id);
//             }
//         }

//         // إعادة حساب الإجماليات
//         $total  = $invoice->products->sum(fn($prod) => $prod->pivot->total);
//         $profit = $invoice->products->sum(fn($prod) => $prod->pivot->profit);

//         $this->calculateTotals($invoice, $total, $profit);

//         // تحديث الحالة وسبب الإرجاع
//         $invoice->update([
//             'status'       => 'partialReturn',
//             'returnReason' => $globalReason,
//         ]);

//         $invoice->updateInvoiceProductCount();

//         return $invoice->fresh(['products']);
//     });
// }


public function calculateTotals(Invoice $invoice, float $total, float $profit): void
{
    $discount = $invoice->discount ?? 0;
    $extra    = $invoice->extraAmount ?? 0;

    if ($invoice->discountType === 'percentage' && $discount > 0) {
        $discountAmount = ($total * $discount) / 100;
    } else {
        $discountAmount = $discount;
    }

    if ($invoice->taxType === 'percentage' && $extra > 0) {
        $extraAmount = ($total * $extra) / 100;
    } else {
        $extraAmount = $extra;
    }

    $final = $total - $discountAmount + $extraAmount;

    $remaining = $final - ($invoice->paidAmount ?? 0);

    $status = $remaining <= 0 ? 'completed' : 'indebted';

    $invoice->update([
        'totalInvoicePrice'    => $total,
        'invoiceAfterDiscount' => $final,
        'profit'               => $profit,
        'remainingAmount'      => $remaining > 0 ? $remaining : 0,
        'status'               => $status,
    ]);
}


// public function recalculateTotals(Invoice $invoice): void
// {
//     // حساب الإجمالي والربح من المنتجات
//     $total  = $invoice->products->sum(fn($p) => $p->pivot->total);
//     $profit = $invoice->products->sum(fn($p) => $p->pivot->profit);

//     // استدعاء الدالة اللي بتعمل الحسابات والتحديث
//     $this->calculateTotals($invoice, $total, $profit);

//     // تحديث عدد المنتجات في الفاتورة
//     $invoice->updateInvoiceProductCount();
// }

// public function recalculateTotals(Invoice $invoice): void
// {
//     $invoice->load('products');
//     $total  = $invoice->products->sum(fn($prod) => $prod->pivot->total);
//     $profit = $invoice->products->sum(fn($prod) => $prod->pivot->profit);

//     $this->calculateTotals($invoice, $total, $profit);
//     $invoice->updateInvoiceProductCount();
// }

public function recalculateTotals(Invoice $invoice): void
{
    $invoice->load('products');

    $total  = $invoice->products->sum(fn($p) => $p->pivot->total);
    $profit = $invoice->products->sum(fn($p) => $p->pivot->profit);

    $discount = $invoice->discount ?? 0;
    $extra    = $invoice->extraAmount ?? 0;

    $discountAmount = ($invoice->discountType === 'percentage' && $discount > 0)
        ? ($total * $discount) / 100
        : $discount;

    $extraAmount = ($invoice->taxType === 'percentage' && $extra > 0)
        ? ($total * $extra) / 100
        : $extra;

    foreach ($invoice->products as $product) {
        $pivot = $product->pivot;
        if ($total > 0 && $pivot->total > 0) {
            $share = $pivot->total / $total;

            $productDiscount = round($discountAmount * $share, 2);
            $productExtra    = round($extraAmount * $share, 2);

            $finalProductTotal = $pivot->total - $productDiscount + $productExtra;

        }
    }

    $final     = $total - $discountAmount + $extraAmount;
    $remaining = $final - ($invoice->paidAmount ?? 0);

    $status = $remaining <= 0 ? 'completed' : 'indebted';

    $invoice->update([
        'totalInvoicePrice'   => $total,
        'invoiceAfterDiscount'=> $final,
        'remainingAmount'     => $remaining > 0 ? $remaining : 0,
        'profit'              => $profit,
        'status'              => $status,
    ]);

    $invoice->updateInvoiceProductCount();
}

}


