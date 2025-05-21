<?php
$pageTitle = 'Регистрация';
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Необходимо заполнить все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } elseif (!validateName($name)) {
        $error = 'Имя может содержать только буквы, пробелы, дефисы и апострофы';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать не менее 6 символов';
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        $user = fetchOne($sql, [$email]);
        
        if ($user) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            $id = registerUser($name, $email, $password);
            
            if ($id) {
                $success = 'Регистрация успешно завершена. Теперь вы можете войти в систему.';
            } else {
                $error = 'Ошибка при регистрации. Пожалуйста, попробуйте еще раз.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="register-container">
    <div class="card">
        <div class="card-header">
            <h3 class="text-center">Регистрация</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-dark">Перейти на страницу входа</a>
            </div>
            <?php else: ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="name" class="form-label">Имя*</label>
                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <div class="form-text">Может содержать буквы любого алфавита, пробелы и дефисы</div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email*</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль*</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Не менее 6 символов</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Подтвердите пароль*</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-dark">Зарегистрироваться</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-footer text-center">
            <p class="mb-0">Уже есть аккаунт? <a href="login.php">Войдите</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>