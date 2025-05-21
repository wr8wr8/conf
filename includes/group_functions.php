<?php
if (!file_exists('includes/db_functions.php')) {
    require_once '../includes/db_functions.php';
} else {
    require_once 'includes/db_functions.php';
}

function createGroup($name, $parent_id = null, $description = '') {
    $sql = "INSERT INTO `groups` (name, parent_id, description) VALUES (?, ?, ?)";
    return insert($sql, [$name, $parent_id, $description]);
}

function updateGroup($id, $name, $parent_id = null, $description = '') {
    $sql = "UPDATE `groups` SET name = ?, parent_id = ?, description = ?, updated_at = NOW() WHERE id = ?";
    return update($sql, [$name, $parent_id, $description, $id]);
}

function deleteGroup($id) {
    $sql = "UPDATE items SET group_id = NULL WHERE group_id = ?";
    update($sql, [$id]);
    
    $sql = "DELETE FROM `groups` WHERE id = ?";
    return delete($sql, [$id]);
}

function getGroupById($id) {
    $sql = "SELECT g.*, p.name as parent_name 
            FROM `groups` g 
            LEFT JOIN `groups` p ON g.parent_id = p.id 
            WHERE g.id = ?";
    return fetchOne($sql, [$id]);
}

function getAllGroups($parent_id = null) {
    $params = [];
    $sql = "SELECT g.*, p.name as parent_name, 
            (SELECT COUNT(*) FROM `groups` WHERE parent_id = g.id) as children_count,
            (SELECT COUNT(*) FROM items WHERE group_id = g.id) as items_count
            FROM `groups` g 
            LEFT JOIN `groups` p ON g.parent_id = p.id 
            WHERE 1=1";
    
    if ($parent_id !== null) {
        $sql .= " AND g.parent_id " . ($parent_id === 0 ? "IS NULL" : "= ?");
        if ($parent_id !== 0) {
            $params[] = $parent_id;
        }
    }
    
    $sql .= " ORDER BY g.name ASC";
    return fetchAll($sql, $params);
}

function getGroupPath($group_id) {
    $path = [];
    $current = getGroupById($group_id);
    
    while ($current) {
        array_unshift($path, $current);
        if ($current['parent_id']) {
            $current = getGroupById($current['parent_id']);
        } else {
            break;
        }
    }
    
    return $path;
}

function getAllGroupsAsTree() {
    $allGroups = getAllGroups();
    $groupsById = [];
    
    foreach ($allGroups as $group) {
        $groupsById[$group['id']] = $group;
        $groupsById[$group['id']]['children'] = [];
    }
    
    $tree = [];
    
    foreach ($groupsById as $id => $group) {
        if ($group['parent_id'] === null) {
            $tree[] = &$groupsById[$id];
        } else {
            $groupsById[$group['parent_id']]['children'][] = &$groupsById[$id];
        }
    }
    
    return $tree;
}
?>