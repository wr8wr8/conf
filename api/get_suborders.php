<?php
require_once '../includes/db_functions.php';
requireLogin();

require_once '../includes/task_functions.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode([]);
    exit;
}

$suborders = getSubordersByOrderId($order_id);
echo json_encode($suborders);
?>