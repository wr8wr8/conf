<?php
$pageTitle = 'Группы';
require_once 'includes/db_functions.php';
requireLogin();

require_once 'includes/group_functions.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create' || $action == 'edit') {
        $name = sanitizeInput($_POST['name']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $description = sanitizeInput($_POST['description']);
        
        if (empty($name)) {
            $error = 'Необходимо указать наименование группы';
        } else {
            if ($action == 'create') {
                $id = createGroup($name, $parent_id, $description);
                if ($id) {
                    $_SESSION['message'] = 'Группа успешно создана';
                    $_SESSION['message_type'] = 'success';
                    header("Location: groups.php");
                    exit();
                } else {
                    $error = 'Ошибка при создании группы';
                }
            } else {
                $id = (int)$_POST['id'];
                
                if ($id == $parent_id) {
                    $error = 'Группа не может быть родительской для самой себя';
                } else {
                    $group = getGroupById($id);
                    $parent = $parent_id ? getGroupById($parent_id) : null;
                    
                    if ($parent) {
                        $path = getGroupPath($parent_id);
                        $isCircularRef = false;
                        
                        foreach ($path as $ancestorGroup) {
                            if ($ancestorGroup['id'] == $id) {
                                $isCircularRef = true;
                                break;
                            }
                        }
                        
                        if ($isCircularRef) {
                            $error = 'Нельзя создавать циклические ссылки в структуре групп';
                        }
                    }
                    
                    if (empty($error)) {
                        $result = updateGroup($id, $name, $parent_id, $description);
                        if ($result) {
                            $_SESSION['message'] = 'Группа успешно обновлена';
                            $_SESSION['message_type'] = 'success';
                            header("Location: groups.php");
                            exit();
                        } else {
                            $error = 'Ошибка при обновлении группы';
                        }
                    }
                }
            }
        }
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $result = deleteGroup($id);
        if ($result) {
            $_SESSION['message'] = 'Группа успешно удалена';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Ошибка при удалении группы';
            $_SESSION['message_type'] = 'danger';
        }
        header("Location: groups.php");
        exit();
    }
}

if ($action == 'edit') {
    $id = (int)$_GET['id'];
    $group = getGroupById($id);
    
    if (!$group) {
        $_SESSION['message'] = 'Группа не найдена';
        $_SESSION['message_type'] = 'danger';
        header("Location: groups.php");
        exit();
    }
}

$parent_id = $_GET['parent_id'] ?? null;
$groups = getAllGroups($parent_id);
$allGroups = getAllGroups();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $pageTitle; ?></h1>
    <a href="groups.php?action=create" class="btn btn-dark"><i class="bi bi-plus-circle"></i> Создать группу</a>
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
                <input type="text" id="groups-table-search" class="form-control" placeholder="Поиск...">
            </div>
            <div class="col-md-6">
                <select id="group-select" class="form-select">
                    <option value="">Все группы</option>
                    <option value="0" <?php echo $parent_id === '0' ? 'selected' : ''; ?>>Корневые группы</option>
                    <?php foreach ($allGroups as $g): ?>
                    <option value="<?php echo $g['id']; ?>" <?php echo $parent_id == $g['id'] ? 'selected' : ''; ?>>
                        <?php echo $g['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($groups)): ?>
        <div class="text-center py-4">
            <p>Группы не найдены</p>
            <a href="groups.php?action=create" class="btn btn-dark">Создать группу</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover data-table" id="groups-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Наименование</th>
                        <th>Родительская группа</th>
                        <th>Подгруппы</th>
                        <th>Товары/Услуги</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?php echo $group['id']; ?></td>
                        <td><?php echo $group['name']; ?></td>
                        <td><?php echo $group['parent_name'] ?? 'Корневая группа'; ?></td>
                        <td>
                            <?php if ($group['children_count'] > 0): ?>
                            <a href="groups.php?parent_id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-dark">
                                Подгруппы (<?php echo $group['children_count']; ?>)
                            </a>
                            <?php else: ?>
                            <span class="text-muted">Нет подгрупп</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($group['items_count'] > 0): ?>
                            <div class="btn-group">
                                <a href="products.php?group_id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-dark">Товары</a>
                                <a href="services.php?group_id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-dark">Услуги</a>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Нет элементов</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="groups.php?action=edit&id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-dark" data-bs-toggle="tooltip" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="groups.php?action=delete" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
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
        <h5 class="card-title"><?php echo $action == 'create' ? 'Создание группы' : 'Редактирование группы'; ?></h5>
    </div>
    <div class="card-body">
        <form method="post" action="">
            <?php if ($action == 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="name" class="form-label">Наименование*</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo $action == 'edit' ? htmlspecialchars($group['name']) : ''; ?>">
            </div>
            
            <div class="mb-3">
                <label for="parent_id" class="form-label">Родительская группа</label>
                <select class="form-select" id="parent_id" name="parent_id">
                    <option value="">Корневая группа</option>
                    <?php foreach ($allGroups as $g): ?>
                        <?php if ($action == 'edit' && $g['id'] == $group['id']) continue; ?>
                        <option value="<?php echo $g['id']; ?>" <?php echo $action == 'edit' && $group['parent_id'] == $g['id'] ? 'selected' : ''; ?>>
                            <?php echo $g['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $action == 'edit' ? htmlspecialchars($group['description']) : ''; ?></textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="groups.php" class="btn btn-outline-secondary">Отмена</a>
                <button type="submit" class="btn btn-dark">Сохранить</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>