<?php
if (!file_exists('includes/db_functions.php')) {
    require_once '../includes/db_functions.php';
} else {
    require_once 'includes/db_functions.php';
}

function createItem($name, $type, $group_id, $description, $price, $unit) {
    $sql = "INSERT INTO items (name, type, group_id, description, price, unit) VALUES (?, ?, ?, ?, ?, ?)";
    return insert($sql, [$name, $type, $group_id, $description, $price, $unit]);
}

function updateItem($id, $name, $type, $group_id, $description, $price, $unit) {
    $sql = "UPDATE items SET name = ?, type = ?, group_id = ?, description = ?, price = ?, unit = ?, updated_at = NOW() WHERE id = ?";
    return update($sql, [$name, $type, $group_id, $description, $price, $unit, $id]);
}

function deleteItem($id) {
    $sql = "DELETE FROM items WHERE id = ?";
    return delete($sql, [$id]);
}

function getItemById($id) {
    $sql = "SELECT i.*, g.name as group_name FROM items i LEFT JOIN `groups` g ON i.group_id = g.id WHERE i.id = ?";
    return fetchOne($sql, [$id]);
}

function getAllItems($type = null, $group_id = null) {
    $params = [];
    $sql = "SELECT i.*, g.name as group_name FROM items i LEFT JOIN `groups` g ON i.group_id = g.id WHERE 1=1";
    
    if ($type) {
        $sql .= " AND i.type = ?";
        $params[] = $type;
    }
    
    if ($group_id) {
        $sql .= " AND i.group_id = ?";
        $params[] = $group_id;
    }
    
    $sql .= " ORDER BY i.name ASC";
    return fetchAll($sql, $params);
}

function importItems($items) {
    $success = 0;
    $failed = 0;
    
    foreach ($items as $item) {
        $name = $item['name'] ?? '';
        $type = $item['type'] ?? 'product';
        $group_id = $item['group_id'] ?? null;
        $description = $item['description'] ?? '';
        $price = $item['price'] ?? 0;
        $unit = $item['unit'] ?? 'шт.';
        
        if (empty($name)) {
            $failed++;
            continue;
        }
        
        $result = createItem($name, $type, $group_id, $description, $price, $unit);
        
        if ($result) {
            $success++;
        } else {
            $failed++;
        }
    }
    
    return ['success' => $success, 'failed' => $failed];
}

function exportItems($type = null) {
    $items = getAllItems($type);
    
    $data = [];
    foreach ($items as $item) {
        $data[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'type' => $item['type'],
            'group_id' => $item['group_id'],
            'group_name' => $item['group_name'],
            'description' => $item['description'],
            'price' => $item['price'],
            'unit' => $item['unit']
        ];
    }
    
    return $data;
}
?>