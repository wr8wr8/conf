<?php
session_start();

require_once 'config.php';

function executeQuery($sql, $params = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    return $stmt;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $rows;
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

function insert($sql, $params = []) {
    global $conn;
    $stmt = executeQuery($sql, $params);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

function update($sql, $params = []) {
    global $conn;
    $stmt = executeQuery($sql, $params);
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affectedRows;
}

function delete($sql, $params = []) {
    global $conn;
    $stmt = executeQuery($sql, $params);
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affectedRows;
}

function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}
?>