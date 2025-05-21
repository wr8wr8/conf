<?php
if (!file_exists('includes/db_functions.php')) {
    require_once '../includes/db_functions.php';
} else {
    require_once 'includes/db_functions.php';
}

function createSuborder($order_id, $name = 'Подзаявка') {
    $sql = "INSERT INTO suborders (order_id, name) VALUES (?, ?)";
    return insert($sql, [$order_id, $name]);
}

function updateSuborder($id, $name, $status) {
    $sql = "UPDATE suborders SET name = ?, status = ?, updated_at = NOW() WHERE id = ?";
    return update($sql, [$name, $status, $id]);
}

function deleteSuborder($id) {
    $sql = "DELETE FROM tasks WHERE suborder_id = ?";
    delete($sql, [$id]);
    
    $sql = "DELETE FROM suborders WHERE id = ?";
    return delete($sql, [$id]);
}

function getSuborderById($id) {
    $sql = "SELECT s.*, o.name as order_name 
            FROM suborders s 
            JOIN orders o ON s.order_id = o.id 
            WHERE s.id = ?";
    return fetchOne($sql, [$id]);
}

function getSubordersByOrderId($order_id) {
    $sql = "SELECT * FROM suborders WHERE order_id = ? ORDER BY created_at DESC";
    return fetchAll($sql, [$order_id]);
}

function getAllSuborders() {
    $sql = "SELECT s.*, o.name as order_name, o.client as client 
            FROM suborders s 
            JOIN orders o ON s.order_id = o.id 
            ORDER BY s.created_at DESC";
    return fetchAll($sql);
}

function createTask($name, $order_id = null, $suborder_id = null, $description = '') {
    if (!$order_id && !$suborder_id) {
        return false;
    }
    
    $sql = "INSERT INTO tasks (name, order_id, suborder_id, description) VALUES (?, ?, ?, ?)";
    return insert($sql, [$name, $order_id, $suborder_id, $description]);
}

function updateTask($id, $name, $status, $description = '') {
    $sql = "UPDATE tasks SET name = ?, status = ?, description = ?, updated_at = NOW() WHERE id = ?";
    return update($sql, [$name, $status, $description, $id]);
}

function deleteTask($id) {
    $sql = "DELETE FROM tasks WHERE id = ?";
    return delete($sql, [$id]);
}

function getTaskById($id) {
    $sql = "SELECT t.*, o.name as order_name, s.name as suborder_name 
            FROM tasks t 
            LEFT JOIN orders o ON t.order_id = o.id 
            LEFT JOIN suborders s ON t.suborder_id = s.id 
            WHERE t.id = ?";
    return fetchOne($sql, [$id]);
}

function getTasksByOrderId($order_id) {
    $sql = "SELECT t.*, o.name as order_name, NULL as suborder_name 
            FROM tasks t 
            JOIN orders o ON t.order_id = o.id 
            WHERE t.order_id = ? AND t.suborder_id IS NULL
            ORDER BY t.created_at DESC";
    return fetchAll($sql, [$order_id]);
}

function getTasksBySuborderId($suborder_id) {
    $sql = "SELECT t.*, NULL as order_name, s.name as suborder_name 
            FROM tasks t 
            JOIN suborders s ON t.suborder_id = s.id 
            WHERE t.suborder_id = ?
            ORDER BY t.created_at DESC";
    return fetchAll($sql, [$suborder_id]);
}

function getAllTasks() {
    $sql = "SELECT t.*, 
            COALESCE(o.name, '') as order_name, 
            COALESCE(s.name, '') as suborder_name,
            COALESCE(o.client, '') as client,
            CASE 
                WHEN t.suborder_id IS NOT NULL THEN 2
                WHEN t.order_id IS NOT NULL THEN 1
                ELSE 0
            END as level
            FROM tasks t 
            LEFT JOIN orders o ON t.order_id = o.id 
            LEFT JOIN suborders s ON t.suborder_id = s.id 
            ORDER BY COALESCE(o.id, 0), COALESCE(s.id, 0), t.created_at DESC";
    return fetchAll($sql);
}

function getTasksHierarchy() {
    $result = [];
    
    $orders = getAllOrders();
    foreach ($orders as $order) {
        $order['level'] = 0;
        $order['type'] = 'order';
        $result[] = $order;
        
        $suborders = getSubordersByOrderId($order['id']);
        foreach ($suborders as $suborder) {
            $suborder['level'] = 1;
            $suborder['type'] = 'suborder';
            $suborder['parent_id'] = $order['id'];
            $result[] = $suborder;
            
            $tasks = getTasksBySuborderId($suborder['id']);
            foreach ($tasks as $task) {
                $task['level'] = 2;
                $task['type'] = 'task';
                $task['parent_id'] = $suborder['id'];
                $result[] = $task;
            }
        }
        
        $tasks = getTasksByOrderId($order['id']);
        foreach ($tasks as $task) {
            $task['level'] = 1;
            $task['type'] = 'task';
            $task['parent_id'] = $order['id'];
            $result[] = $task;
        }
    }
    
    return $result;
}
?>