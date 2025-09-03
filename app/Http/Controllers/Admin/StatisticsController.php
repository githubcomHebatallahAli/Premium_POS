<?php

namespace App\Http\Controllers\Admin;

use App\Models\Dept;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Category;
use App\Models\Withdraw;
use App\Http\Controllers\Controller;

class StatisticsController extends Controller
{
    public function showStatistics()
{
    $this->authorize('manage_users');

    $productsCount = Product::count();
    $categoriesCount = Category::count();
    $invoicesCount = Invoice::count();


    $paidDeptInvoicesCount = Dept::where('status', 'paid')->count();
    $invoicesCount += $paidDeptInvoicesCount;

    // حساب إجمالي المبيعات
    $salesFromInvoices = Invoice::sum('invoiceAfterDiscount');
    $salesFromDepts = Dept::where('status', 'paid')->sum('depetAfterDiscount');
    $totalSales = $salesFromInvoices + $salesFromDepts;

    // حساب صافي الربح
    $profitFromInvoices = Invoice::sum('profit');
    $profitFromDepts = Dept::where('status', 'paid')->sum('profit'); // تأكد من وجود عمود 'profit' في جدول Dept
    $netProfit = $profitFromInvoices + $profitFromDepts;

    // إجمالي المبالغ المسحوبة
    $totalWithdrawals = Withdraw::sum('withdrawnAmount');

    // المبلغ المتاح للسحب
    $availableWithdrawal = $totalSales - $totalWithdrawals;

    $statistics = [
        'Categories_count' => $categoriesCount,
        'Products_count' => $productsCount,
        'Invoices_count' => $invoicesCount,
        'Sales' => $totalSales,
        'Net_Profit' => $netProfit,
        'Available_Withdrawal' => $availableWithdrawal,
    ];

    return response()->json($statistics);
}

}
