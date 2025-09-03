<?php

namespace App\Http\Controllers\Excel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ExcelExportService;
use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    protected $exportService;

    public function __construct(ExcelExportService $exportService)
    {
        $this->exportService = $exportService;
        $this->authorizeResource(Product::class, 'product');
    }

    public function exportProducts()
    {
        $this->authorize('exportProducts', Product::class);
        $products = Product::all();

        $headers = [
            'id' => 'ID',
            'name' => 'Product Name',
            'price' => 'Price',
            'created_at' => 'Created Date'
        ];

        $spreadsheet = $this->exportService->exportData($products, $headers);

        $fileName = 'products_export_'.date('YmdHis').'.xlsx';
        $tempPath = storage_path('app/public/'.$fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

}
