<?php
$pageTitle = 'Товары';
require_once 'includes/db_functions.php';
requireLogin();

require_once 'includes/card_functions.php';
require_once 'includes/group_functions.php';
require_once 'includes/export_functions.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create' || $action == 'edit') {
        $name = sanitizeInput($_POST['name']);
        $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        $description = sanitizeInput($_POST['description']);
        $price = (float)str_replace(',', '.', $_POST['price']);
        $unit = sanitizeInput($_POST['unit']);
        
        if (empty($name)) {
            $error = 'Необходимо указать наименование товара';
        } else {
            if ($action == 'create') {
                $id = createItem($name, 'product', $group_id, $description, $price, $unit);
                if ($id) {
                    $_SESSION['message'] = 'Товар успешно создан';
                    $_SESSION['message_type'] = 'success';
                    header("Location: products.php");
                    exit();
                } else {
                    $error = 'Ошибка при создании товара';
                }
            } else {
                $id = (int)$_POST['id'];
                $result = updateItem($id, $name, 'product', $group_id, $description, $price, $unit);
                if ($result) {
                    $_SESSION['message'] = 'Товар успешно обновлен';
                    $_SESSION['message_type'] = 'success';
                    header("Location: products.php");
                    exit();
                } else {
                    $error = 'Ошибка при обновлении товара';
                }
            }
        }
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $result = deleteItem($id);
        if ($result) {
            $_SESSION['message'] = 'Товар успешно удален';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Ошибка при удалении товара';
            $_SESSION['message_type'] = 'danger';
        }
        header("Location: products.php");
        exit();
    } elseif ($action == 'import') {
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
            $file = $_FILES['import_file']['tmp_name'];
            $result = importItemsFromExcel($file);
            
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $success = 'Импорт завершен. Добавлено: ' . $result['success'] . ', не добавлено: ' . $result['failed'];
            }
        } else {
            $error = 'Ошибка при загрузке файла';
        }
    }
}

if ($action == 'export') {
    $filepath = exportItemsToExcel('product');
    if ($filepath) {
        header("Location: $filepath");
        exit();
    } else {
        $_SESSION['message'] = 'Ошибка при экспорте товаров';
        $_SESSION['message_type'] = 'danger';
        header("Location: products.php");
        exit();
    }
} elseif ($action == 'edit') {
    $id = (int)$_GET['id'];
    $item = getItemById($id);
    
    if (!$item || $item['type'] != 'product') {
        $_SESSION['message'] = 'Товар не найден';
        $_SESSION['message_type'] = 'danger';
        header("Location: products.php");
        exit();
    }
}

$group_id = $_GET['group_id'] ?? null;
$products = getAllItems('product', $group_id);
$groups = getAllGroups();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $pageTitle; ?></h1>
    <div class="btn-group">
        <a href="products.php?action=create" class="btn btn-dark"><i class="bi bi-plus-circle"></i> Создать товар</a>
        <button type="button" class="btn btn-dark dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="products.php?action=import">Импорт товаров</a></li>
            <li><a class="dropdown-item" href="products.php?action=export">Экспорт товаров</a></li>
        </ul>
    </div>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
<div class="card mb-4">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <input type="text" id="products-table-search" class="form-control" placeholder="Поиск...">
            </div>
            <div class="col-md-6">
                <select id="group-select" class="form-select">
                    <option value="">Все группы</option>
                    <option value="0" <?php echo $group_id === '0' ? 'selected' : ''; ?>>Без группы</option>
                    <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group['id']; ?>" <?php echo $group_id == $group['id'] ? 'selected' : ''; ?>>
                        <?php echo $group['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
        <div class="text-center py-4">
            <p>Товары не найдены</p>
            <a href="products.php?action=create" class="btn btn-dark">Создать товар</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover data-table" id="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Наименование</th>
                        <th>Группа</th>
                        <th>Цена</th>
                        <th>Ед. изм.</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['group_name'] ?? 'Без группы'; ?></td>
                        <td><?php echo number_format($product['price'], 2, ',', ' '); ?> ₽</td>
                        <td><?php echo $product['unit']; ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-dark" data-bs-toggle="tooltip" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="products.php?action=delete" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="tooltip" title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action == 'create' || $action == 'edit'): ?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title"><?php echo $action == 'create' ? 'Создание товара' : 'Редактирование товара'; ?></h5>
    </div>
    <div class="card-body">
        <form method="post" action="">
            <?php if ($action == 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="name" class="form-label">Наименование*</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo $action == 'edit' ? htmlspecialchars($item['name']) : ''; ?>">
            </div>
            
            <div class="mb-3">
                <label for="group_id" class="form-label">Группа</label>
                <select class="form-select" id="group_id" name="group_id">
                    <option value="">Без группы</option>
                    <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group['id']; ?>" <?php echo $action == 'edit' && $item['group_id'] == $group['id'] ? 'selected' : ''; ?>>
                        <?php echo $group['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $action == 'edit' ? htmlspecialchars($item['description']) : ''; ?></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="price" class="form-label">Цена</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="price" name="price" value="<?php echo $action == 'edit' ? number_format($item['price'], 2, ',', '') : '0,00'; ?>">
                        <span class="input-group-text">₽</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="unit" class="form-label">Единица измерения</label>
                    <input type="text" class="form-control" id="unit" name="unit" value="<?php echo $action == 'edit' ? htmlspecialchars($item['unit']) : 'шт.'; ?>">
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="products.php" class="btn btn-outline-secondary">Отмена</a>
                <button type="submit" class="btn btn-dark">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action == 'import'): ?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Импорт товаров</h5>
    </div>
    <div class="card-body">
        <form method="post" action="products.php?action=import" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="import_file" class="form-label">Выберите файл Excel</label>
                <input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls" required>
                <div class="form-text">Поддерживаемые форматы: .xlsx, .xls</div>
            </div>
            
            <div class="mb-3">
                <h6>Требования к файлу:</h6>
                <ul>
                    <li>Первая строка должна содержать заголовки колонок</li>
                    <li>Обязательные колонки: "Наименование"</li>
                    <li>Дополнительные колонки: "Группа ID", "Описание", "Цена", "Ед. измерения"</li>
                </ul>
                <p>Скачайте <a href="products.php?action=export">шаблон</a> для импорта</p>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="products.php" class="btn btn-outline-secondary">Отмена</a>
                <button type="submit" class="btn btn-dark">Импортировать</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>