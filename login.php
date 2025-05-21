<?php
$pageTitle = 'Вход';
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Необходимо заполнить все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } else {
        $result = loginUser($email, $password);
        
        if (is_array($result) && isset($result['error'])) {
            $error = $result['error'];
        } elseif ($result === true) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <div class="card">
        <div class="card-header">
            <h3 class="text-center">Вход в систему</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-dark">Войти</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center">
            <p class="mb-0">Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>