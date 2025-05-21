<?php
require_once '../includes/db_functions.php';
requireLogin();

require_once '../includes/export_functions.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID заявки']);
    exit;
}

$filepath = generatePdfOrder($order_id);

if ($filepath) {
    echo json_encode([
        'success' => true,
        'file_url' => $filepath
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при создании PDF файла'
    ]);
}
?>