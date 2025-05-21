<?php
if (!file_exists('includes/db_functions.php')) {
    require_once '../includes/db_functions.php';
} else {
    require_once 'includes/db_functions.php';
}

function createOrder($user_id, $name = 'Заявка', $client = null) {
    $sql = "INSERT INTO orders (user_id, name, client) VALUES (?, ?, ?)";
    return insert($sql, [$user_id, $name, $client]);
}

function updateOrderStatus($order_id, $status) {
    $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    return update($sql, [$status, $order_id]);
}

function updateOrder($id, $name, $client, $status) {
    $sql = "UPDATE orders SET name = ?, client = ?, status = ?, updated_at = NOW() WHERE id = ?";
    return update($sql, [$name, $client, $status, $id]);
}

function updateOrderTotal($order_id) {
    $sql = "SELECT SUM(price * quantity) as total FROM order_items WHERE order_id = ?";
    $result = fetchOne($sql, [$order_id]);
    $total = $result['total'] ?? 0;
    
    $sql = "UPDATE orders SET total_amount = ?, updated_at = NOW() WHERE id = ?";
    return update($sql, [$total, $order_id]);
}

function deleteOrder($order_id) {
    $sql = "DELETE FROM tasks WHERE order_id = ? OR suborder_id IN (SELECT id FROM suborders WHERE order_id = ?)";
    delete($sql, [$order_id, $order_id]);
    
    $sql = "DELETE FROM suborders WHERE order_id = ?";
    delete($sql, [$order_id]);
    
    $sql = "DELETE FROM order_item_groups WHERE order_id = ?";
    delete($sql, [$order_id]);
    
    $sql = "DELETE FROM order_items WHERE order_id = ?";
    delete($sql, [$order_id]);
    
    $sql = "DELETE FROM orders WHERE id = ?";
    return delete($sql, [$order_id]);
}

function getOrderById($order_id) {
    $sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
    return fetchOne($sql, [$order_id]);
}

function getAllOrders($user_id = null) {
    $params = [];
    $sql = "SELECT o.*, u.name FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
    
    if ($user_id) {
        $sql .= " AND o.user_id = ?";
        $params[] = $user_id;
    }
    
    $sql .= " ORDER BY o.created_at DESC";
    return fetchAll($sql, $params);
}

function getFilteredOrders($user_id = null, $client = null, $status = null) {
    $params = [];
    $sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
    
    if ($user_id) {
        $sql .= " AND o.user_id = ?";
        $params[] = $user_id;
    }
    
    if ($client) {
        $sql .= " AND o.client LIKE ?";
        $params[] = "%$client%";
    }
    
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY o.created_at DESC";
    return fetchAll($sql, $params);
}

function getOrderClients() {
    $sql = "SELECT DISTINCT client FROM orders WHERE client IS NOT NULL AND client != '' ORDER BY client ASC";
    $result = fetchAll($sql);
    $clients = [];
    
    foreach ($result as $row) {
        $clients[] = $row['client'];
    }
    
    return $clients;
}

function addItemToOrder($order_id, $item_id, $quantity, $price, $group_name = null) {
    $sql = "INSERT INTO order_items (order_id, item_id, quantity, price, group_name) VALUES (?, ?, ?, ?, ?)";
    $result = insert($sql, [$order_id, $item_id, $quantity, $price, $group_name]);
    
    if ($result) {
        updateOrderTotal($order_id);
    }
    
    return $result;
}

function updateOrderItem($id, $quantity, $price) {
    $sql = "UPDATE order_items SET quantity = ?, price = ? WHERE id = ?";
    $result = update($sql, [$quantity, $price, $id]);
    
    if ($result) {
        $sql = "SELECT order_id FROM order_items WHERE id = ?";
        $order = fetchOne($sql, [$id]);
        updateOrderTotal($order['order_id']);
    }
    
    return $result;
}

function removeItemFromOrder($id) {
    $sql = "SELECT order_id FROM order_items WHERE id = ?";
    $order = fetchOne($sql, [$id]);
    
    $sql = "DELETE FROM order_items WHERE id = ?";
    $result = delete($sql, [$id]);
    
    if ($result && $order) {
        updateOrderTotal($order['order_id']);
    }
    
    return $result;
}

function getOrderItems($order_id) {
    $sql = "SELECT oi.*, i.name, i.type, i.unit 
            FROM order_items oi 
            JOIN items i ON oi.item_id = i.id 
            WHERE oi.order_id = ? 
            ORDER BY oi.group_name, i.name";
    return fetchAll($sql, [$order_id]);
}

function createOrderItemGroup($order_id, $name, $parent_id = null) {
    $sql = "INSERT INTO order_item_groups (order_id, name, parent_id) VALUES (?, ?, ?)";
    return insert($sql, [$order_id, $name, $parent_id]);
}

function updateOrderItemGroup($id, $name) {
    $sql = "UPDATE order_item_groups SET name = ? WHERE id = ?";
    return update($sql, [$name, $id]);
}

function deleteOrderItemGroup($id) {
    $sql = "UPDATE order_items SET group_name = NULL WHERE group_name = (SELECT name FROM order_item_groups WHERE id = ?)";
    update($sql, [$id]);
    
    $sql = "DELETE FROM order_item_groups WHERE id = ?";
    return delete($sql, [$id]);
}

function getOrderItemGroups($order_id) {
    $sql = "SELECT * FROM order_item_groups WHERE order_id = ? ORDER BY name";
    return fetchAll($sql, [$order_id]);
}
?>