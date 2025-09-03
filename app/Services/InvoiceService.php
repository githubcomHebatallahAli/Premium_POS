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
                'customerName' => $data['customerName'],
                'customerPhone' => $data['customerPhone'],
                'admin_id' => $data['admin_id'] ?? null,
                'status' => $data['status'] ?? 'completed',
                'payment' => $data['payment'] ?? 'cash',
                'pullType' => $data['pullType'],
                'discount' => $data['discount'] ?? 0,
                'discountType' => $data['discountType'] ?? 'percentage',
                'extraAmount' => $data['extraAmount'] ?? 0,
                'taxType' => $data['taxType'] ?? 'percentage',
                'paidAmount' => $data['paidAmount'] ?? 0,
                'creationDate' => $data['creationDate'] ?? now(),
            ]);

            $total = 0;
            $profit = 0;

            foreach ($data['products'] as $p) {
                $product = Product::findOrFail($p['id']);

                if ($data['pullType'] === 'fifo') {
                    $shipmentProduct = ShipmentProduct::where('product_id', $product->id)
                        ->where('remainingQuantity', '>', 0)
                        ->orderBy('created_at')
                        ->first();
                } else {
                    $shipmentProduct = ShipmentProduct::where('product_id', $product->id)
                        ->where('shipment_id', $p['shipment_id'])
                        ->first();
                }

                if (!$shipmentProduct || $shipmentProduct->remainingQuantity < $p['quantity']) {
                    throw new \Exception("Not enough stock for product {$product->name}");
                }

                $shipmentProduct->decrement('remainingQuantity', $p['quantity']);

                $lineTotal = $product->sellingPrice * $p['quantity'];
                $lineProfit = ($product->sellingPrice - $shipmentProduct->purchasePrice) * $p['quantity'];

                $invoice->products()->attach($product->id, [
                    'shipment_id' => $shipmentProduct->shipment_id,
                    'quantity' => $p['quantity'],
                    'price' => $product->sellingPrice,
                    'total' => $lineTotal,
                    'profit' => $lineProfit,
                ]);

                $total += $lineTotal;
                $profit += $lineProfit;
            }

            $calculated = $this->calculateTotals($invoice, $total, $profit);

            $invoice->update($calculated);

            return $invoice->fresh('products');
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice->products()->detach();

            $invoice->update([
                'customerName' => $data['customerName'],
                'customerPhone' => $data['customerPhone'],
                'status' => $data['status'] ?? $invoice->status,
                'payment' => $data['payment'] ?? $invoice->payment,
                'pullType' => $data['pullType'],
                'discount' => $data['discount'] ?? 0,
                'discountType' => $data['discountType'] ?? 'percentage',
                'extraAmount' => $data['extraAmount'] ?? 0,
                'taxType' => $data['taxType'] ?? 'percentage',
                'paidAmount' => $data['paidAmount'] ?? 0,
            ]);

            $total = 0;
            $profit = 0;

            foreach ($data['products'] as $p) {
                $product = Product::findOrFail($p['id']);

                if ($data['pullType'] === 'fifo') {
                    $shipmentProduct = ShipmentProduct::where('product_id', $product->id)
                        ->where('remainingQuantity', '>', 0)
                        ->orderBy('created_at')
                        ->first();
                } else {
                    $shipmentProduct = ShipmentProduct::where('product_id', $product->id)
                        ->where('shipment_id', $p['shipment_id'])
                        ->first();
                }

                if (!$shipmentProduct || $shipmentProduct->remainingQuantity < $p['quantity']) {
                    throw new \Exception("Not enough stock for product {$product->name}");
                }

                $shipmentProduct->decrement('remainingQuantity', $p['quantity']);

                $lineTotal = $product->sellingPrice * $p['quantity'];
                $lineProfit = ($product->sellingPrice - $shipmentProduct->purchasePrice) * $p['quantity'];

                $invoice->products()->attach($product->id, [
                    'shipment_id' => $shipmentProduct->shipment_id,
                    'quantity' => $p['quantity'],
                    'price' => $product->sellingPrice,
                    'total' => $lineTotal,
                    'profit' => $lineProfit,
                ]);

                $total += $lineTotal;
                $profit += $lineProfit;
            }

            $calculated = $this->calculateTotals($invoice, $total, $profit);

            $invoice->update($calculated);

            return $invoice->fresh('products');
        });
    }

    public function fullReturn(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            foreach ($invoice->products as $p) {
                $shipmentProduct = ShipmentProduct::where('product_id', $p->id)
                    ->where('shipment_id', $p->pivot->shipment_id)
                    ->first();

                $shipmentProduct->increment('remainingQuantity', $p->pivot->quantity);
            }

            $invoice->update(['status' => 'return']);

            return $invoice->fresh('products');
        });
    }

    public function partialReturn(Invoice $invoice, array $products): Invoice
    {
        return DB::transaction(function () use ($invoice, $products) {
            foreach ($products as $p) {
                $pivot = $invoice->products()->where('product_id', $p['id'])->first();

                if (!$pivot) continue;

                $returnQty = min($p['quantity'], $pivot->pivot->quantity);

                $shipmentProduct = ShipmentProduct::where('product_id', $pivot->id)
                    ->where('shipment_id', $pivot->pivot->shipment_id)
                    ->first();

                $shipmentProduct->increment('remainingQuantity', $returnQty);

                $invoice->products()->updateExistingPivot($pivot->id, [
                    'quantity' => $pivot->pivot->quantity - $returnQty,
                    'total' => $pivot->pivot->price * ($pivot->pivot->quantity - $returnQty),
                    'profit' => ($pivot->pivot->price - $shipmentProduct->purchasePrice) * ($pivot->pivot->quantity - $returnQty),
                ]);
            }

            $total = $invoice->products->sum('pivot.total');
            $profit = $invoice->products->sum('pivot.profit');

            $calculated = $this->calculateTotals($invoice, $total, $profit);

            $invoice->update(array_merge($calculated, ['status' => 'partialReturn']));

            return $invoice->fresh('products');
        });
    }

    public function calculateTotals(Invoice $invoice, float $total, float $profit): array
    {
        $discount = $invoice->discount ?? 0;
        if ($invoice->discountType === 'percentage') {
            $discount = ($total * $discount) / 100;
        }

        $extra = $invoice->extraAmount ?? 0;
        if ($invoice->taxType === 'percentage') {
            $extra = ($total * $extra) / 100;
        }

        $final = $total - $discount + $extra;
        $remaining = $final - ($invoice->paidAmount ?? 0);

        return [
            'totalInvoicePrice' => $total,
            'invoiceAfterDiscount' => $final,
            'profit' => $profit,
            'remainingAmount' => $remaining > 0 ? $remaining : 0,
        ];
    }
}


