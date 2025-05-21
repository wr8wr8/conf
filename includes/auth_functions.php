<?php
if (!file_exists('includes/db_functions.php')) {
    require_once '../includes/db_functions.php';
} else {
    require_once 'includes/db_functions.php';
}

function registerUser($name, $email, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    return insert($sql, [$name, $email, $hashedPassword]);
}

function loginUser($email, $password) {
    $sql = "SELECT id, name, password, status, login_attempts FROM users WHERE email = ?";
    $user = fetchOne($sql, [$email]);
    
    if (!$user) {
        return false;
    }
    
    if ($user['status'] === 'blocked') {
        return ['error' => 'Аккаунт заблокирован. Свяжитесь с администратором.'];
    }
    
    if ($user['login_attempts'] >= 5) {
        $sql = "UPDATE users SET status = 'blocked' WHERE id = ?";
        update($sql, [$user['id']]);
        return ['error' => 'Слишком много попыток входа. Аккаунт временно заблокирован.'];
    }
    
    if (password_verify($password, $user['password'])) {
        $sql = "UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?";
        update($sql, [$user['id']]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        
        return true;
    } else {
        $sql = "UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?";
        update($sql, [$user['id']]);
        
        return false;
    }
}

function logoutUser() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT id, name, email, created_at, last_login FROM users WHERE id = ?";
    return fetchOne($sql, [$_SESSION['user_id']]);
}

function validateName($name) {
    return preg_match('/^[\p{L}\s\-\']+$/u', $name);
}
?>