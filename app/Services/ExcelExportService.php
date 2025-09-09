<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExcelExportService
{
    public function exportData($data, $headers)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column.'1', $header);
            $sheet->getStyle($column.'1')->getFont()->setBold(true);
            $sheet->getStyle($column.'1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $column++;
        }

        $row = 2;
        foreach ($data as $item) {
            $column = 'A';
            foreach ($headers as $key => $header) {
                $sheet->setCellValue($column.$row, $item->{$key} ?? '');
                $column++;
            }
            $row++;
        }

        foreach (range('A', $column) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
