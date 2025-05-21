<?php
$pageTitle = 'Задачи';
require_once 'includes/db_functions.php';
requireLogin();

require_once 'includes/task_functions.php';
require_once 'includes/order_functions.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create' || $action == 'edit') {
        $name = sanitizeInput($_POST['name']);
        $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $suborder_id = !empty($_POST['suborder_id']) ? (int)$_POST['suborder_id'] : null;
        $status = sanitizeInput($_POST['status']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($name)) {
            $error = 'Необходимо указать название задачи';
        } elseif (!$order_id && !$suborder_id) {
            $error = 'Необходимо выбрать заявку или подзаявку';
        } else {
            if ($action == 'create') {
                $id = createTask($name, $order_id, $suborder_id, $description);
                if ($id) {
                    $_SESSION['message'] = 'Задача успешно создана';
                    $_SESSION['message_type'] = 'success';
                    header("Location: tasks.php");
                    exit();
                } else {
                    $error = 'Ошибка при создании задачи';
                }
            } else {
                $id = (int)$_POST['id'];
                $result = updateTask($id, $name, $status, $description);
                if ($result) {
                    $_SESSION['message'] = 'Задача успешно обновлена';
                    $_SESSION['message_type'] = 'success';
                    header("Location: tasks.php");
                    exit();
                } else {
                    $error = 'Ошибка при обновлении задачи';
                }
            }
        }
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $result = deleteTask($id);
        if ($result) {
            $_SESSION['message'] = 'Задача успешно удалена';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Ошибка при удалении задачи';
            $_SESSION['message_type'] = 'danger';
        }
        header("Location: tasks.php");
        exit();
    } elseif ($action == 'edit_suborder') {
        $id = (int)$_POST['id'];
        $name = sanitizeInput($_POST['name']);
        $status = sanitizeInput($_POST['status']);
        
        if (empty($name)) {
            $error = 'Необходимо указать название подзаявки';
        } else {
            $result = updateSuborder($id, $name, $status);
            if ($result) {
                $_SESSION['message'] = 'Подзаявка успешно обновлена';
                $_SESSION['message_type'] = 'success';
                header("Location: tasks.php");
                exit();
            } else {
                $error = 'Ошибка при обновлении подзаявки';
            }
        }
    } elseif ($action == 'delete_suborder') {
        $id = (int)$_POST['id'];
        $result = deleteSuborder($id);
        if ($result) {
            $_SESSION['message'] = 'Подзаявка успешно удалена';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Ошибка при удалении подзаявки';
            $_SESSION['message_type'] = 'danger';
        }
        header("Location: tasks.php");
        exit();
    }
}

if ($action == 'edit') {
    $id = (int)$_GET['id'];
    $task = getTaskById($id);
    
    if (!$task) {
        $_SESSION['message'] = 'Задача не найдена';
        $_SESSION['message_type'] = 'danger';
        header("Location: tasks.php");
        exit();
    }
} elseif ($action == 'edit_suborder') {
    $id = (int)$_GET['id'];
    $suborder = getSuborderById($id);
    
    if (!$suborder) {
        $_SESSION['message'] = 'Подзаявка не найдена';
        $_SESSION['message_type'] = 'danger';
        header("Location: tasks.php");
        exit();
    }
}

$hierarchyData = getTasksHierarchy();
$orders = getAllOrders();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $pageTitle; ?></h1>
    <a href="#" data-bs-toggle="modal" data-bs-target="#taskModal" class="btn btn-dark">
        <i class="bi bi-plus-circle"></i> Создать задачу
    </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <input type="text" id="tasks-search" class="form-control" placeholder="Поиск...">
    </div>
    <div class="card-body">
        <?php if (empty($hierarchyData)): ?>
        <div class="text-center py-4">
            <p>Нет заявок и задач</p>
            <a href="orders.php?action=create" class="btn btn-dark">Создать заявку</a>
        </div>
        <?php else: ?>
        <div class="list-group">
            <?php 
            $prevLevel = -1;
            $openLevels = [];
            
            foreach ($hierarchyData as $item): 
                $level = $item['level'];
                $type = $item['type'];
                $levelClass = '';
                $statusClass = '';
                
                switch ($level) {
                    case 0: $levelClass = ''; break;
                    case 1: $levelClass = 'ms-4'; break;
                    case 2: $levelClass = 'ms-5'; break;
                }
                
                if ($type == 'order' || $type == 'suborder') {
                    switch ($item['status']) {
                        case 'draft': $statusClass = 'bg-secondary text-white'; break;
                        case 'submitted': $statusClass = 'bg-primary text-white'; break;
                        case 'processed': $statusClass = 'bg-info text-dark'; break;
                        case 'completed': $statusClass = 'bg-success text-white'; break;
                        case 'cancelled': $statusClass = 'bg-danger text-white'; break;
                    }
                } else {
                    switch ($item['status']) {
                        case 'pending': $statusClass = 'bg-secondary text-white'; break;
                        case 'in_progress': $statusClass = 'bg-primary text-white'; break;
                        case 'completed': $statusClass = 'bg-success text-white'; break;
                        case 'cancelled': $statusClass = 'bg-danger text-white'; break;
                    }
                }
            ?>
            <div class="list-group-item <?php echo $levelClass; ?> <?php echo $statusClass; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php if ($type == 'order'): ?>
                        <i class="bi bi-folder me-2"></i>
                        <a href="orders.php?action=view&id=<?php echo $item['id']; ?>" class="text-reset">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        <?php elseif ($type == 'suborder'): ?>
                        <i class="bi bi-folder-plus me-2"></i>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#editSuborderModal<?php echo $item['id']; ?>" class="text-reset">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        <?php else: ?>
                        <i class="bi bi-check-square me-2"></i>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#editTaskModal<?php echo $item['id']; ?>" class="text-reset">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($type == 'order'): ?>
                        <span class="badge bg-dark rounded-pill"><?php echo $item['client'] ? htmlspecialchars($item['client']) : 'Нет клиента'; ?></span>
                        <span class="badge rounded-pill <?php echo $statusClass; ?>">
                            <?php
                                switch ($item['status']) {
                                    case 'draft': echo 'Черновик'; break;
                                    case 'submitted': echo 'Отправлено'; break;
                                    case 'processed': echo 'В обработке'; break;
                                    case 'completed': echo 'Завершено'; break;
                                    case 'cancelled': echo 'Отменено'; break;
                                    default: echo ucfirst($item['status']);
                                }
                            ?>
                        </span>
                        <?php elseif ($type == 'suborder'): ?>
                        <span class="badge rounded-pill <?php echo $statusClass; ?>">
                            <?php
                                switch ($item['status']) {
                                    case 'draft': echo 'Черновик'; break;
                                    case 'submitted': echo 'Отправлено'; break;
                                    case 'processed': echo 'В обработке'; break;
                                    case 'completed': echo 'Завершено'; break;
                                    case 'cancelled': echo 'Отменено'; break;
                                    default: echo ucfirst($item['status']);
                                }
                            ?>
                        </span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#editSuborderModal<?php echo $item['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSuborderModal<?php echo $item['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <span class="badge rounded-pill <?php echo $statusClass; ?>">
                            <?php
                                switch ($item['status']) {
                                    case 'pending': echo 'Ожидание'; break;
                                    case 'in_progress': echo 'В процессе'; break;
                                    case 'completed': echo 'Завершено'; break;
                                    case 'cancelled': echo 'Отменено'; break;
                                    default: echo ucfirst($item['status']);
                                }
                            ?>
                        </span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#editTaskModal<?php echo $item['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteTaskModal<?php echo $item['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($type == 'task'): ?>
            <div class="modal fade" id="editTaskModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Редактировать задачу</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <form method="post" action="tasks.php?action=edit">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="name<?php echo $item['id']; ?>" class="form-label">Название задачи*</label>
                                    <input type="text" class="form-control" id="name<?php echo $item['id']; ?>" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status<?php echo $item['id']; ?>" class="form-label">Статус*</label>
                                    <select class="form-select" id="status<?php echo $item['id']; ?>" name="status" required>
                                        <option value="pending" <?php echo $item['status'] == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                        <option value="in_progress" <?php echo $item['status'] == 'in_progress' ? 'selected' : ''; ?>>В процессе</option>
                                        <option value="completed" <?php echo $item['status'] == 'completed' ? 'selected' : ''; ?>>Завершено</option>
                                        <option value="cancelled" <?php echo $item['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description<?php echo $item['id']; ?>" class="form-label">Описание</label>
                                    <textarea class="form-control" id="description<?php echo $item['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="submit" class="btn btn-dark">Сохранить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="deleteTaskModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Удаление задачи</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <div class="modal-body">
                            <p>Вы действительно хотите удалить задачу "<?php echo htmlspecialchars($item['name']); ?>"?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <form method="post" action="tasks.php?action=delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($type == 'suborder'): ?>
            <div class="modal fade" id="editSuborderModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Редактировать подзаявку</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <form method="post" action="tasks.php?action=edit_suborder">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="name<?php echo $item['id']; ?>" class="form-label">Название подзаявки*</label>
                                    <input type="text" class="form-control" id="name<?php echo $item['id']; ?>" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status<?php echo $item['id']; ?>" class="form-label">Статус*</label>
                                    <select class="form-select" id="status<?php echo $item['id']; ?>" name="status" required>
                                        <option value="draft" <?php echo $item['status'] == 'draft' ? 'selected' : ''; ?>>Черновик</option>
                                        <option value="submitted" <?php echo $item['status'] == 'submitted' ? 'selected' : ''; ?>>Отправлено</option>
                                        <option value="processed" <?php echo $item['status'] == 'processed' ? 'selected' : ''; ?>>В обработке</option>
                                        <option value="completed" <?php echo $item['status'] == 'completed' ? 'selected' : ''; ?>>Завершено</option>
                                        <option value="cancelled" <?php echo $item['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="submit" class="btn btn-dark">Сохранить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="deleteSuborderModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Удаление подзаявки</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <div class="modal-body">
                            <p>Вы действительно хотите удалить подзаявку "<?php echo htmlspecialchars($item['name']); ?>"?</p>
                            <p class="text-danger">Внимание! Будут удалены все связанные задачи.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <form method="post" action="tasks.php?action=delete_suborder">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Создать задачу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form method="post" action="tasks.php?action=create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Название задачи*</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent" class="form-label">Привязка*</label>
                        <select class="form-select" id="parent" required>
                            <option value="">Выберите тип привязки</option>
                            <option value="order">К заявке</option>
                            <option value="suborder">К подзаявке</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 d-none" id="orderSelect">
                        <label for="order_id" class="form-label">Заявка*</label>
                        <select class="form-select" id="order_id" name="order_id">
                            <option value="">Выберите заявку</option>
                            <?php foreach ($orders as $order): ?>
                            <option value="<?php echo $order['id']; ?>"><?php echo htmlspecialchars($order['name']); ?> (<?php echo $order['client'] ? htmlspecialchars($order['client']) : 'Нет клиента'; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3 d-none" id="suborderSelect">
                        <label for="suborder_id" class="form-label">Подзаявка*</label>
                        <select class="form-select" id="suborder_id" name="suborder_id">
                            <option value="">Сначала выберите заявку</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Статус*</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Ожидание</option>
                            <option value="in_progress">В процессе</option>
                            <option value="completed">Завершено</option>
                            <option value="cancelled">Отменено</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-dark">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const parentSelect = document.getElementById('parent');
    const orderSelect = document.getElementById('orderSelect');
    const suborderSelect = document.getElementById('suborderSelect');
    const orderId = document.getElementById('order_id');
    const suborderId = document.getElementById('suborder_id');
    
    parentSelect.addEventListener('change', function() {
        const value = this.value;
        
        if (value === 'order') {
            orderSelect.classList.remove('d-none');
            suborderSelect.classList.add('d-none');
            orderId.required = true;
            suborderId.required = false;
            suborderId.value = '';
        } else if (value === 'suborder') {
            orderSelect.classList.remove('d-none');
            suborderSelect.classList.remove('d-none');
            orderId.required = true;
            suborderId.required = true;
        } else {
            orderSelect.classList.add('d-none');
            suborderSelect.classList.add('d-none');
            orderId.required = false;
            suborderId.required = false;
            orderId.value = '';
            suborderId.value = '';
        }
    });
    
    orderId.addEventListener('change', function() {
        const orderId = this.value;
        
        if (orderId && parentSelect.value === 'suborder') {
            fetch('api/get_suborders.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">Выберите подзаявку</option>';
                    
                    if (data.length > 0) {
                        data.forEach(function(suborder) {
                            options += `<option value="${suborder.id}">${suborder.name}</option>`;
                        });
                    } else {
                        options += '<option value="" disabled>Нет подзаявок для этой заявки</option>';
                    }
                    
                    suborderId.innerHTML = options;
                })
                .catch(error => console.error('Error:', error));
        }
    });
    
    const searchInput = document.getElementById('tasks-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchVal = this.value.toLowerCase();
            const items = document.querySelectorAll('.list-group-item');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchVal)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>