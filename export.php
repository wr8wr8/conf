<?php
$pageTitle = 'Экспорт';
require_once 'includes/db_functions.php';
requireLogin();

require_once 'includes/export_functions.php';

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action == 'pdf' && $id > 0) {
    $filepath = generatePdfOrder($id);
    
    if ($filepath) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $_SESSION['message'] = 'Ошибка при создании PDF файла';
        $_SESSION['message_type'] = 'danger';
    }
} elseif ($action == 'xlsx') {
    $type = $_GET['type'] ?? null;
    
    $filepath = exportItemsToExcel($type);
    
    if ($filepath) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $_SESSION['message'] = 'Ошибка при создании Excel файла';
        $_SESSION['message_type'] = 'danger';
    }
}

header('Location: dashboard.php');
exit;
?>