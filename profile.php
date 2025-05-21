<?php
$pageTitle = 'Профиль';
require_once 'includes/auth_functions.php';
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profile') {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        
        if (empty($name) || empty($email)) {
            $error = 'Имя и Email не могут быть пустыми';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный email адрес';
        } elseif (!validateName($name)) {
            $error = 'Имя может содержать только буквы, пробелы, дефисы и апострофы';
        } else {
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $existingUser = fetchOne($sql, [$email, $user['id']]);
            
            if ($existingUser) {
                $error = 'Пользователь с таким email уже существует';
            } else {
                $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                $result = update($sql, [$name, $email, $user['id']]);
                
                if ($result) {
                    $_SESSION['name'] = $name;
                    $success = 'Профиль успешно обновлен';
                    $user['name'] = $name;
                    $user['email'] = $email;
                } else {
                    $error = 'Ошибка при обновлении профиля';
                }
            }
        }
    } elseif ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Необходимо заполнить все поля';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Новые пароли не совпадают';
        } elseif (strlen($new_password) < 6) {
            $error = 'Пароль должен содержать не менее 6 символов';
        } else {
            $sql = "SELECT password FROM users WHERE id = ?";
            $result = fetchOne($sql, [$user['id']]);
            
            if (!$result || !password_verify($current_password, $result['password'])) {
                $error = 'Текущий пароль введен неверно';
            } else {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $result = update($sql, [$new_password_hash, $user['id']]);
                
                if ($result) {
                    $success = 'Пароль успешно изменен';
                } else {
                    $error = 'Ошибка при изменении пароля';
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Профиль пользователя</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Имя:</strong> <?php echo $user['name']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                        <p><strong>Последний вход:</strong> <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Неизвестно'; ?></p>
                    </div>
                </div>
                
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="edit-profile-tab" data-bs-toggle="tab" data-bs-target="#edit-profile" type="button" role="tab" aria-controls="edit-profile" aria-selected="true">Изменить профиль</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="change-password-tab" data-bs-toggle="tab" data-bs-target="#change-password" type="button" role="tab" aria-controls="change-password" aria-selected="false">Изменить пароль</button>
                    </li>
                </ul>
                
                <div class="tab-content p-3 border border-top-0 rounded-bottom" id="profileTabsContent">
                    <div class="tab-pane fade show active" id="edit-profile" role="tabpanel" aria-labelledby="edit-profile-tab">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Имя</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                                <div class="form-text">Может содержать буквы любого алфавита, пробелы и дефисы</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email адрес</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-dark">Сохранить изменения</button>
                        </form>
                    </div>
                    
                    <div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Текущий пароль</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Новый пароль</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Не менее 6 символов</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Подтверждение нового пароля</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-dark">Изменить пароль</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>