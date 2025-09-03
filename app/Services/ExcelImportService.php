<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;

class ExcelImportService
{
    public function importData($filePath, $modelClass, $validationRules)
    {
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);

        // تحقق إضافي من وجود الملف
        if (!file_exists($filePath)) {
            throw new \Exception("الملف غير موجود في المسار: " . $filePath .
                   "\nالمسار الموحد: " . str_replace('\\', '/', $filePath));
        }
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $rows = $worksheet->toArray();
        $headers = array_shift($rows);

        $imported = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            $data = $this->mapRowData($headers, $row);
            $validator = Validator::make($data, $validationRules);

            if ($validator->fails()) {
                $errors[] = $this->formatError($rowIndex + 2, $validator);
                continue;
            }

            try {
                $modelClass::create($data);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowIndex + 2,
                    'errors' => ['خطأ في قاعدة البيانات: ' . $e->getMessage()]
                ];
            }
        }

        return [
            'imported_count' => $imported,
            'errors_count' => count($errors),
            'errors' => $errors
        ];
    }

    protected function mapRowData($headers, $row)
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            $mapped[$header] = $row[$index] ?? null;
        }

        $sellingPrice = $mapped['sellingPrice'] ?? $mapped['price'] ?? 0;
        $purchesPrice = $mapped['purchesPrice'] ?? $mapped['cost'] ?? 0;
        $profit = $sellingPrice - $purchesPrice;

        $categoryName = $mapped['categoryName'] ?? $mapped['category'] ?? null;
        $categoryId = null;

        if ($categoryName && class_exists('App\Models\Category')) {
            $category = \App\Models\Category::firstOrCreate(
                ['name' => $categoryName],
                ['name' => $categoryName]
            );
            $categoryId = $category->id;
        }
        return [
            'category_id' => $categoryId,
            'name' => $mapped['name'] ?? $mapped['product_name'] ?? null,
            'quantity' => $mapped['quantity'] ?? $mapped['qty'] ?? null,
            'priceBeforeDiscount' => $mapped['priceBeforeDiscount'] ?? $mapped['old_price'] ?? null,
            'sellingPrice' => $sellingPrice,
            'purchesPrice' => $purchesPrice,
            'profit' => $profit 
        ];
    }

    protected function formatError($rowNumber, $validator)
    {
        return [
            'row' => $rowNumber,
            'errors' => $validator->errors()->all(),
            'values' => $validator->getData()
        ];
    }
}
