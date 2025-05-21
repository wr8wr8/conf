<?php
require_once '../includes/db_functions.php';
requireLogin();

header('Content-Type: application/json');

$lastDays = 7;
$user_id = $_SESSION['user_id'];

$labels = [];
$counts = [];

for ($i = $lastDays - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d.m', strtotime($date));
    
    $sql = "SELECT COUNT(*) as count FROM orders 
            WHERE user_id = ? AND DATE(created_at) = ?";
    $result = fetchOne($sql, [$user_id, $date]);
    
    $counts[] = $result['count'] ?? 0;
}

echo json_encode([
    'labels' => $labels,
    'counts' => $counts
]);
?>