<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DamageProduct;
use App\Models\Dept;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Transaction;
use App\Models\Withdraw;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function showStatistics()
{
    $this->authorize('manage_users');

    $productsCount = Product::count();
    $productVariantsCount = ProductVariant::count();
    $categoriesCount = Category::count();
    $brandsCount = Brand::count();
    $invoicesCount = Invoice::count();
    $shipmentsCount = Shipment::count();
    $suppliersCount = Supplier::count();

    // إجمالي المبيعات
    $totalSalesFromInvoices = Invoice::sum('paidAmount');
    $totalDeposits = Transaction::where('type', 'deposit')->sum('amount');
    $totalReturns = SupplierReturn::sum('refund_amount');
    $totalSales = $totalSalesFromInvoices + $totalDeposits + $totalReturns;

    // إجمالي الخسائر
    $totalDamageLosses = DamageProduct::where('status', 'damage')
        ->join('shipment_products', 'damage_products.shipment_product_id', '=', 'shipment_products.id')
        ->sum(DB::raw('damage_products.quantity * shipment_products.unitPrice'));

    $supplierLosses = DamageProduct::where('status', 'return')
        ->join('shipment_products', 'damage_products.shipment_product_id', '=', 'shipment_products.id')
        ->join('supplier_returns', 'damage_products.id', '=', 'supplier_returns.damage_product_id')
        ->sum(DB::raw('(supplier_returns.returned_quantity * shipment_products.unitPrice) - supplier_returns.refund_amount'));

    $totalLosses = $totalDamageLosses + $supplierLosses;

    // إجمالي المسحوبات
    $totalWithdrawals = Transaction::where('type', 'withdraw')->sum('amount');

    // صافي الربح
    $netProfit = $totalSales - $totalLosses;

    // الرصيد المتاح للسحب
    $availableBalance = $totalSales - $totalWithdrawals - $totalLosses;

    return response()->json([
        'statistics' => [
            'products_count'   => $productsCount,
            'product_variants_count' => $productVariantsCount,
            'categories_count' => $categoriesCount,
            'brands_count'     => $brandsCount,
            'invoices_count'   => $invoicesCount,
            'suppliers_count'  => $suppliersCount,
            'shipments_count'  => $shipmentsCount,
            'total_sales'       => number_format($totalSales, 2),
            'total_losses'      => number_format($totalLosses, 2),
            'net_profit'        => number_format($netProfit, 2),
            'available_balance' => number_format($availableBalance, 2),
        ],
        'message' => "Dashboard statistics fetched successfully."
    ]);
}


}
