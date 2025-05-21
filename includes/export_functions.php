<?php
if (!file_exists('includes/db_functions.php')) {
    require_once '../includes/db_functions.php';
} else {
    require_once 'includes/db_functions.php';
}
require_once 'order_functions.php';

function generatePdfOrder($order_id) {
    $order = getOrderById($order_id);
    $items = getOrderItems($order_id);
    
    if (!$order || !$items) {
        return false;
    }
    
    if (!file_exists('vendor/autoload.php')) {
        $filename = 'order_' . $order_id . '_' . date('YmdHis') . '.html';
        $filepath = 'exports/' . $filename;
        
        $html = generateHtmlOrder($order, $items);
        
        if (!file_exists('exports/')) {
            mkdir('exports/', 0777, true);
        }
        
        file_put_contents($filepath, $html);
        
        return $filepath;
    }
    
    try {
        require_once 'vendor/autoload.php';
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10
        ]);
        
        $html = generateHtmlOrder($order, $items);
        
        $mpdf->WriteHTML($html);
        
        $filename = 'order_' . $order_id . '_' . date('YmdHis') . '.pdf';
        $filepath = 'exports/' . $filename;
        
        if (!file_exists('exports/')) {
            mkdir('exports/', 0777, true);
        }
        
        $mpdf->Output($filepath, \Mpdf\Output\Destination::FILE);
        
        return $filepath;
    } catch (Exception $e) {
        $filename = 'order_' . $order_id . '_' . date('YmdHis') . '.html';
        $filepath = 'exports/' . $filename;
        
        $html = generateHtmlOrder($order, $items);
        
        if (!file_exists('exports/')) {
            mkdir('exports/', 0777, true);
        }
        
        file_put_contents($filepath, $html);
        
        return $filepath;
    }
}

function generateHtmlOrder($order, $items) {
    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        h1 { font-size: 16pt; text-align: center; margin-bottom: 20px; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f0f0f0; }
        .total { font-weight: bold; text-align: right; }
    </style>
    
    <h1>Заказ №' . $order['id'] . '</h1>
    
    <div class="info">
        <p><strong>Дата создания:</strong> ' . date('d.m.Y H:i', strtotime($order['created_at'])) . '</p>
        <p><strong>Статус:</strong> ' . ucfirst($order['status']) . '</p>
        <p><strong>Пользователь:</strong> ' . $order['username'] . '</p>
    </div>
    
    <table>
        <tr>
            <th>№</th>
            <th>Наименование</th>
            <th>Тип</th>
            <th>Количество</th>
            <th>Цена</th>
            <th>Сумма</th>
        </tr>';
    
    $currentGroup = '';
    $counter = 1;
    $totalAmount = 0;
    
    foreach ($items as $item) {
        if ($item['group_name'] && $item['group_name'] != $currentGroup) {
            $html .= '<tr><td colspan="6" style="background-color: #e0e0e0; font-weight: bold;">' . $item['group_name'] . '</td></tr>';
            $currentGroup = $item['group_name'];
        }
        
        $sum = $item['price'] * $item['quantity'];
        $totalAmount += $sum;
        
        $html .= '<tr>
            <td>' . $counter++ . '</td>
            <td>' . $item['name'] . '</td>
            <td>' . ($item['type'] == 'product' ? 'Товар' : 'Услуга') . '</td>
            <td>' . $item['quantity'] . ' ' . $item['unit'] . '</td>
            <td>' . number_format($item['price'], 2, ',', ' ') . ' ₽</td>
            <td>' . number_format($sum, 2, ',', ' ') . ' ₽</td>
        </tr>';
    }
    
    $html .= '<tr>
        <td colspan="5" class="total">Итого:</td>
        <td class="total">' . number_format($totalAmount, 2, ',', ' ') . ' ₽</td>
    </tr>';
    
    $html .= '</table>';
    
    return $html;
}

function exportItemsToExcel($type = null) {
    if (!file_exists('vendor/autoload.php')) {
        $items = exportItems($type);
        
        $filename = 'items_' . ($type ?: 'all') . '_' . date('YmdHis') . '.csv';
        $filepath = 'exports/' . $filename;
        
        if (!file_exists('exports/')) {
            mkdir('exports/', 0777, true);
        }
        
        $file = fopen($filepath, 'w');
        
        fputcsv($file, ['ID', 'Наименование', 'Тип', 'Группа ID', 'Группа', 'Описание', 'Цена', 'Ед. измерения']);
        
        foreach ($items as $item) {
            fputcsv($file, [
                $item['id'],
                $item['name'],
                $item['type'],
                $item['group_id'],
                $item['group_name'],
                $item['description'],
                $item['price'],
                $item['unit']
            ]);
        }
        
        fclose($file);
        
        return $filepath;
    }
    
    try {
        require_once 'vendor/autoload.php';
        
        $items = exportItems($type);
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Наименование');
        $sheet->setCellValue('C1', 'Тип');
        $sheet->setCellValue('D1', 'Группа ID');
        $sheet->setCellValue('E1', 'Группа');
        $sheet->setCellValue('F1', 'Описание');
        $sheet->setCellValue('G1', 'Цена');
        $sheet->setCellValue('H1', 'Ед. измерения');
        
        $row = 2;
        foreach ($items as $item) {
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('B' . $row, $item['name']);
            $sheet->setCellValue('C' . $row, $item['type']);
            $sheet->setCellValue('D' . $row, $item['group_id']);
            $sheet->setCellValue('E' . $row, $item['group_name']);
            $sheet->setCellValue('F' . $row, $item['description']);
            $sheet->setCellValue('G' . $row, $item['price']);
            $sheet->setCellValue('H' . $row, $item['unit']);
            $row++;
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $filename = 'items_' . ($type ?: 'all') . '_' . date('YmdHis') . '.xlsx';
        $filepath = 'exports/' . $filename;
        
        if (!file_exists('exports/')) {
            mkdir('exports/', 0777, true);
        }
        
        $writer->save($filepath);
        
        return $filepath;
    } catch (Exception $e) {
        $items = exportItems($type);
        
        $filename = 'items_' . ($type ?: 'all') . '_' . date('YmdHis') . '.csv';
        $filepath = 'exports/' . $filename;
        
        if (!file_exists('exports/')) {
            mkdir('exports/', 0777, true);
        }
        
        $file = fopen($filepath, 'w');
        
        fputcsv($file, ['ID', 'Наименование', 'Тип', 'Группа ID', 'Группа', 'Описание', 'Цена', 'Ед. измерения']);
        
        foreach ($items as $item) {
            fputcsv($file, [
                $item['id'],
                $item['name'],
                $item['type'],
                $item['group_id'],
                $item['group_name'],
                $item['description'],
                $item['price'],
                $item['unit']
            ]);
        }
        
        fclose($file);
        
        return $filepath;
    }
}

function importItemsFromExcel($file) {
    if (!file_exists('vendor/autoload.php')) {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            $nameIndex = array_search('Наименование', $headers);
            $typeIndex = array_search('Тип', $headers);
            $groupIdIndex = array_search('Группа ID', $headers);
            $descriptionIndex = array_search('Описание', $headers);
            $priceIndex = array_search('Цена', $headers);
            $unitIndex = array_search('Ед. измерения', $headers);
            
            if ($nameIndex === false) {
                return ['success' => 0, 'failed' => 0, 'error' => 'Невозможно найти колонку с наименованием товара'];
            }
            
            $items = [];
            while (($row = fgetcsv($handle)) !== FALSE) {
                $items[] = [
                    'name' => $row[$nameIndex],
                    'type' => $typeIndex !== false ? $row[$typeIndex] : 'product',
                    'group_id' => $groupIdIndex !== false ? $row[$groupIdIndex] : null,
                    'description' => $descriptionIndex !== false ? $row[$descriptionIndex] : '',
                    'price' => $priceIndex !== false ? $row[$priceIndex] : 0,
                    'unit' => $unitIndex !== false ? $row[$unitIndex] : 'шт.'
                ];
            }
            
            fclose($handle);
            
            return importItems($items);
        } else {
            return ['success' => 0, 'failed' => 0, 'error' => 'Не удалось открыть файл'];
        }
    }
    
    try {
        require_once 'vendor/autoload.php';
        
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        $headers = array_shift($rows);
        $nameIndex = array_search('Наименование', $headers);
        $typeIndex = array_search('Тип', $headers);
        $groupIdIndex = array_search('Группа ID', $headers);
        $descriptionIndex = array_search('Описание', $headers);
        $priceIndex = array_search('Цена', $headers);
        $unitIndex = array_search('Ед. измерения', $headers);
        
        if ($nameIndex === false) {
            return ['success' => 0, 'failed' => count($rows), 'error' => 'Невозможно найти колонку с наименованием товара'];
        }
        
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'name' => $row[$nameIndex],
                'type' => $typeIndex !== false ? $row[$typeIndex] : 'product',
                'group_id' => $groupIdIndex !== false ? $row[$groupIdIndex] : null,
                'description' => $descriptionIndex !== false ? $row[$descriptionIndex] : '',
                'price' => $priceIndex !== false ? $row[$priceIndex] : 0,
                'unit' => $unitIndex !== false ? $row[$unitIndex] : 'шт.'
            ];
        }
        
        return importItems($items);
    } catch (Exception $e) {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            $nameIndex = array_search('Наименование', $headers);
            $typeIndex = array_search('Тип', $headers);
            $groupIdIndex = array_search('Группа ID', $headers);
            $descriptionIndex = array_search('Описание', $headers);
            $priceIndex = array_search('Цена', $headers);
            $unitIndex = array_search('Ед. измерения', $headers);
            
            if ($nameIndex === false) {
                return ['success' => 0, 'failed' => 0, 'error' => 'Невозможно найти колонку с наименованием товара'];
            }
            
            $items = [];
            while (($row = fgetcsv($handle)) !== FALSE) {
                $items[] = [
                    'name' => $row[$nameIndex],
                    'type' => $typeIndex !== false ? $row[$typeIndex] : 'product',
                    'group_id' => $groupIdIndex !== false ? $row[$groupIdIndex] : null,
                    'description' => $descriptionIndex !== false ? $row[$descriptionIndex] : '',
                    'price' => $priceIndex !== false ? $row[$priceIndex] : 0,
                    'unit' => $unitIndex !== false ? $row[$unitIndex] : 'шт.'
                ];
            }
            
            fclose($handle);
            
            return importItems($items);
        } else {
            return ['success' => 0, 'failed' => 0, 'error' => 'Не удалось открыть файл'];
        }
    }
}
?>