<?php
namespace App\Http\Controllers\Excel;

use Log;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Services\ExcelImportService;
use App\Http\Requests\Admin\ExcelRequest;
use App\Http\Requests\Admin\ProductRequest;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ExcelImportService $importService)
    {
        $this->importService = $importService;
        $this->authorizeResource(Product::class, 'product');
    }

    public function importProducts(ExcelRequest $request)
    {
        $this->authorize('importProducts', Product::class);
        try {
            $file = $request->file('file');

            // إنشاء مجلد uploads إذا لم يكن موجوداً
            $uploadPath = public_path('uploads');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0775, true);
            }

            // إنشاء مجلد imports داخل uploads
            $importPath = $uploadPath . DIRECTORY_SEPARATOR . 'imports';
            if (!file_exists($importPath)) {
                mkdir($importPath, 0775, true);
            }

            $fileName = 'import_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $fullPath = $importPath . DIRECTORY_SEPARATOR . $fileName;

            // نقل الملف مع استخدام المسار الصحيح لنظام Windows
            $file->move($importPath, $fileName);

            // التحقق من وجود الملف فعلياً
            if (!file_exists($fullPath)) {
                throw new \Exception("فشل في حفظ الملف في المسار: " . $fullPath);
            }

            $productRequest = new ProductRequest();
            $validationRules = $productRequest->rules();

            $result = $this->importService->importData(
                $fullPath,
                \App\Models\Product::class,
                $validationRules
            );

            return response()->json([
                'success' => true,
                'message' => 'تم الاستيراد بنجاح',
                'stats' => $result,
                'file_url' => url('uploads/imports/' . $fileName)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في عملية الاستيراد',
                'error' => $e->getMessage(),
                'debug_path' => $fullPath ?? 'unknown',
                'actual_path' => isset($fullPath) ? str_replace('\\', '/', $fullPath) : 'unknown'
            ], 500);
        }
    }
}
