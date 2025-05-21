<?php
$pageTitle = 'Заявки';
require_once 'includes/db_functions.php';
requireLogin();

require_once 'includes/order_functions.php';
require_once 'includes/card_functions.php';
require_once 'includes/export_functions.php';
require_once 'includes/task_functions.php';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create') {
        $name = sanitizeInput($_POST['name'] ?? 'Заявка');
        $client = sanitizeInput($_POST['client'] ?? '');
        
        $id = createOrder($_SESSION['user_id'], $name, $client);
        if ($id) {
            header("Location: orders.php?action=edit&id=$id");
            exit();
        } else {
            $error = 'Ошибка при создании заявки';
        }
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitizeInput($_POST['name'] ?? 'Заявка');
        $client = sanitizeInput($_POST['client'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        
        $result = updateOrder($id, $name, $client, $status);
        if ($result) {
            $_SESSION['message'] = 'Заявка успешно обновлена';
            $_SESSION['message_type'] = 'success';
            header("Location: orders.php?action=edit&id=$id");
            exit();
        } else {
            $error = 'Ошибка при обновлении заявки';
        }
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $result = deleteOrder($id);
        if ($result) {
            $_SESSION['message'] = 'Заявка успешно удалена';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Ошибка при удалении заявки';
            $_SESSION['message_type'] = 'danger';
        }
        header("Location: orders.php");
        exit();
    } elseif ($action == 'add_item') {
        $order_id = (int)$_POST['order_id'];
        $item_id = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        $price = (float)str_replace(',', '.', $_POST['price']);
        $group_name = !empty($_POST['group_name']) ? sanitizeInput($_POST['group_name']) : null;
        
        if ($quantity <= 0) {
            $error = 'Количество должно быть больше нуля';
        } else {
            $item = getItemById($item_id);
            if (!$item) {
                $error = 'Товар или услуга не найдены';
            } else {
                $result = addItemToOrder($order_id, $item_id, $quantity, $price, $group_name);
                if ($result) {
                    $_SESSION['message'] = 'Элемент добавлен в заявку';
                    $_SESSION['message_type'] = 'success';
                    header("Location: orders.php?action=edit&id=$order_id");
                    exit();
                } else {
                    $error = 'Ошибка при добавлении элемента в заявку';
                }
            }
        }
    } elseif ($action == 'update_item') {
        $id = (int)$_POST['id'];
        $order_id = (int)$_POST['order_id'];
        $quantity = (int)$_POST['quantity'];
        $price = (float)str_replace(',', '.', $_POST['price']);
        
        if ($quantity <= 0) {
            $error = 'Количество должно быть больше нуля';
        } else {
            $result = updateOrderItem($id, $quantity, $price);
            if ($result) {
                $_SESSION['message'] = 'Элемент заявки обновлен';
                $_SESSION['message_type'] = 'success';
                header("Location: orders.php?action=edit&id=$order_id");
                exit();
            } else {
                $error = 'Ошибка при обновлении элемента заявки';
            }
        }
    } elseif ($action == 'remove_item') {
        $id = (int)$_POST['id'];
        $order_id = (int)$_POST['order_id'];
        
        $result = removeItemFromOrder($id);
        if ($result) {
            $_SESSION['message'] = 'Элемент удален из заявки';
            $_SESSION['message_type'] = 'success';
            header("Location: orders.php?action=edit&id=$order_id");
            exit();
        } else {
            $error = 'Ошибка при удалении элемента из заявки';
        }
    } elseif ($action == 'add_group') {
        $order_id = (int)$_POST['order_id'];
        $name = sanitizeInput($_POST['name']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        if (empty($name)) {
            $error = 'Необходимо указать название группы';
        } else {
            $result = createOrderItemGroup($order_id, $name, $parent_id);
            if ($result) {
                $_SESSION['message'] = 'Группа добавлена в заявку';
                $_SESSION['message_type'] = 'success';
                header("Location: orders.php?action=edit&id=$order_id");
                exit();
            } else {
                $error = 'Ошибка при добавлении группы в заявку';
            }
        }
    } elseif ($action == 'filter') {
        $client = sanitizeInput($_POST['client'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? '');
        
        header("Location: orders.php?client=" . urlencode($client) . "&status=" . urlencode($status));
        exit();
    } elseif ($action == 'create_suborder') {
        $order_id = (int)$_POST['order_id'];
        $name = sanitizeInput($_POST['name']);
        
        if (empty($name)) {
            $error = 'Необходимо указать название подзаявки';
        } else {
            $id = createSuborder($order_id, $name);
            if ($id) {
                $_SESSION['message'] = 'Подзаявка успешно создана';
                $_SESSION['message_type'] = 'success';
                header("Location: orders.php?action=edit&id=$order_id&tab=suborders");
                exit();
            } else {
                $error = 'Ошибка при создании подзаявки';
            }
        }
    }
}

if ($action == 'export_pdf') {
    $id = (int)$_GET['id'];
    $filepath = generatePdfOrder($id);
    
    if ($filepath) {
        header("Location: $filepath");
        exit();
    } else {
        $_SESSION['message'] = 'Ошибка при экспорте заявки в PDF';
        $_SESSION['message_type'] = 'danger';
        header("Location: orders.php?action=view&id=$id");
        exit();
    }
} elseif ($action == 'edit' || $action == 'view') {
    $id = (int)$_GET['id'];
    $order = getOrderById($id);
    
    if (!$order) {
        $_SESSION['message'] = 'Заявка не найдена';
        $_SESSION['message_type'] = 'danger';
        header("Location: orders.php");
        exit();
    }
    
    $orderItems = getOrderItems($id);
    $orderGroups = getOrderItemGroups($id);
    $suborders = getSubordersByOrderId($id);
    
    if ($action == 'edit') {
        $products = getAllItems('product');
        $services = getAllItems('service');
    }
}

$client_filter = $_GET['client'] ?? '';
$status_filter = $_GET['status'] ?? '';

if (!empty($client_filter) || !empty($status_filter)) {
    $orders = getFilteredOrders($_SESSION['user_id'], $client_filter, $status_filter);
} else {
    $orders = getAllOrders($_SESSION['user_id']);
}

$clients = getOrderClients();
$statuses = ['draft' => 'Черновик', 'submitted' => 'Отправлена', 'processed' => 'В обработке', 'completed' => 'Завершена', 'cancelled' => 'Отменена'];

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $pageTitle; ?></h1>
    <?php if ($action != 'list'): ?>
    <a href="orders.php" class="btn btn-outline-secondary">Назад к списку</a>
    <?php endif; ?>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="input-group">
            <button class="btn btn-dark" type="button" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                <i class="bi bi-plus-circle"></i> Новая заявка
            </button>
        </div>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Фильтр по клиенту <?php echo !empty($client_filter) ? '<span class="badge bg-primary">+ ' . htmlspecialchars($client_filter) . '</span>' : ''; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="width: 300px;">
                <form method="post" action="orders.php?action=filter">
                    <div class="mb-3">
                        <label for="client_filter" class="form-label">Клиент</label>
                        <select class="form-select" id="client_filter" name="client">
                            <option value="">Все клиенты</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client); ?>" <?php echo $client_filter == $client ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-dark">Применить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Фильтр по статусу <?php echo !empty($status_filter) ? '<span class="badge bg-primary">+ ' . ($statuses[$status_filter] ?? ucfirst($status_filter)) . '</span>' : ''; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item <?php echo empty($status_filter) ? 'active' : ''; ?>" href="orders.php<?php echo !empty($client_filter) ? '?client=' . urlencode($client_filter) : ''; ?>">Все статусы</a>
                <?php foreach ($statuses as $value => $label): ?>
                <a class="dropdown-item <?php echo $status_filter == $value ? 'active' : ''; ?>" href="orders.php?status=<?php echo $value; ?><?php echo !empty($client_filter) ? '&client=' . urlencode($client_filter) : ''; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <input type="text" id="orders-table-search" class="form-control" placeholder="Поиск...">
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
        <div class="text-center py-4">
            <p>У вас пока нет заявок</p>
            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                <i class="bi bi-plus-circle"></i> Создать заявку
            </button>
        </div>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($orders as $order): ?>
            <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($order['name']); ?></h5>
                        <small>Создана: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-dark rounded-pill me-3"><?php echo $order['client'] ? htmlspecialchars($order['client']) : 'Нет клиента'; ?></span>
                        <span class="badge rounded-pill bg-<?php
                            switch ($order['status']) {
                                case 'draft': echo 'secondary'; break;
                                case 'submitted': echo 'primary'; break;
                                case 'processed': echo 'info'; break;
                                case 'completed': echo 'success'; break;
                                case 'cancelled': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                        ?> me-3"><?php echo $statuses[$order['status']] ?? ucfirst($order['status']); ?></span>
                        <div class="btn-group">
                            <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="orders.php?action=edit&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal<?php echo $order['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </a>
            
            <div class="modal fade" id="deleteOrderModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Удаление заявки</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <div class="modal-body">
                            <p>Вы действительно хотите удалить заявку "<?php echo htmlspecialchars($order['name']); ?>"?</p>
                            <p class="text-danger">Внимание! Будут удалены все связанные элементы, подзаявки и задачи.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <form method="post" action="orders.php?action=delete">
                                <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="createOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Создать заявку</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form method="post" action="orders.php?action=create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Название заявки</label>
                        <input type="text" class="form-control" id="name" name="name" value="Заявка">
                    </div>
                    <div class="mb-3">
                        <label for="client" class="form-label">Клиент</label>
                        <input type="text" class="form-control" id="client" name="client" list="clients-list">
                        <datalist id="clients-list">
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client); ?>">
                            <?php endforeach; ?>
                        </datalist>
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

<?php elseif ($action == 'view'): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?php echo htmlspecialchars($order['name']); ?> #<?php echo $order['id']; ?></h5>
        <div class="btn-group">
            <a href="orders.php?action=edit&id=<?php echo $order['id']; ?>" class="btn btn-outline-dark">Редактировать</a>
            <a href="orders.php?action=export_pdf&id=<?php echo $order['id']; ?>" class="btn btn-outline-dark">Экспорт в PDF</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Клиент:</strong> <?php echo $order['client'] ? htmlspecialchars($order['client']) : 'Нет клиента'; ?></p>
                <p><strong>Дата создания:</strong> <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                <p><strong>Статус:</strong> <span class="badge bg-<?php
                    switch ($order['status']) {
                        case 'draft': echo 'secondary'; break;
                        case 'submitted': echo 'primary'; break;
                        case 'processed': echo 'info'; break;
                        case 'completed': echo 'success'; break;
                        case 'cancelled': echo 'danger'; break;
                        default: echo 'secondary';
                    }
                ?>"><?php echo $statuses[$order['status']] ?? ucfirst($order['status']); ?></span></p>
            </div>
            <div class="col-md-6 text-md-end">
                <p><strong>Общая сумма:</strong> <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> ₽</p>
            </div>
        </div>
        
        <?php if (!empty($suborders)): ?>
        <h6 class="mb-3">Подзаявки:</h6>
        <div class="list-group mb-4">
            <?php foreach ($suborders as $suborder): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-folder-plus me-2"></i>
                        <?php echo htmlspecialchars($suborder['name']); ?>
                    </div>
                    <span class="badge rounded-pill bg-<?php
                        switch ($suborder['status']) {
                            case 'draft': echo 'secondary'; break;
                            case 'submitted': echo 'primary'; break;
                            case 'processed': echo 'info'; break;
                            case 'completed': echo 'success'; break;
                            case 'cancelled': echo 'danger'; break;
                            default: echo 'secondary';
                        }
                    ?>"><?php echo $statuses[$suborder['status']] ?? ucfirst($suborder['status']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($orderItems)): ?>
        <div class="alert alert-info">В заявке пока нет товаров и услуг</div>
        <?php else: ?>
        <h6 class="mb-3">Товары и услуги в заявке:</h6>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Наименование</th>
                        <th>Тип</th>
                        <th>Количество</th>
                        <th>Цена</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $currentGroup = '';
                    $counter = 1;
                    $totalAmount = 0;
                    
                    foreach ($orderItems as $item): 
                        if ($item['group_name'] && $item['group_name'] != $currentGroup):
                            $currentGroup = $item['group_name'];
                    ?>
                    <tr>
                        <td colspan="6" class="table-secondary"><strong><?php echo $item['group_name']; ?></strong></td>
                    </tr>
                    <?php endif; 
                        $sum = $item['price'] * $item['quantity'];
                        $totalAmount += $sum;
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo $item['name']; ?></td>
                        <td><?php echo $item['type'] == 'product' ? 'Товар' : 'Услуга'; ?></td>
                        <td><?php echo $item['quantity'] . ' ' . $item['unit']; ?></td>
                        <td><?php echo number_format($item['price'], 2, ',', ' '); ?> ₽</td>
                        <td><?php echo number_format($sum, 2, ',', ' '); ?> ₽</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end"><strong>Итого:</strong></td>
                        <td><strong><?php echo number_format($totalAmount, 2, ',', ' '); ?> ₽</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer">
        <a href="orders.php" class="btn btn-outline-secondary">Назад к списку</a>
    </div>
</div>

<?php elseif ($action == 'edit'): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Редактирование заявки #<?php echo $order['id']; ?></h5>
        <div class="btn-group">
            <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-outline-dark">Просмотр</a>
            <a href="orders.php?action=export_pdf&id=<?php echo $order['id']; ?>" class="btn btn-outline-dark">Экспорт в PDF</a>
        </div>
    </div>
    <div class="card-body">
        <form method="post" action="orders.php?action=edit" class="mb-4">
            <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="name" class="form-label">Название заявки</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($order['name']); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="client" class="form-label">Клиент</label>
                    <input type="text" class="form-control" id="client" name="client" value="<?php echo htmlspecialchars($order['client'] ?? ''); ?>" list="clients-list">
                    <datalist id="clients-list">
                        <?php foreach ($clients as $client): ?>
                        <option value="<?php echo htmlspecialchars($client); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft" <?php echo $order['status'] == 'draft' ? 'selected' : ''; ?>>Черновик</option>
                        <option value="submitted" <?php echo $order['status'] == 'submitted' ? 'selected' : ''; ?>>Отправлена</option>
                        <option value="processed" <?php echo $order['status'] == 'processed' ? 'selected' : ''; ?>>В обработке</option>
                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Завершена</option>
                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменена</option>
                    </select>
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-dark">Сохранить изменения</button>
            </div>
        </form>
        
        <ul class="nav nav-tabs mb-3" id="orderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo !isset($_GET['tab']) || $_GET['tab'] != 'suborders' ? 'active' : ''; ?>" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab" aria-controls="items" aria-selected="true">Товары и услуги</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] == 'suborders' ? 'active' : ''; ?>" id="suborders-tab" data-bs-toggle="tab" data-bs-target="#suborders" type="button" role="tab" aria-controls="suborders" aria-selected="false">Подзаявки</button>
            </li>
        </ul>
        
        <div class="tab-content" id="orderTabsContent">
            <div class="tab-pane fade <?php echo !isset($_GET['tab']) || $_GET['tab'] != 'suborders' ? 'show active' : ''; ?>" id="items" role="tabpanel" aria-labelledby="items-tab">
                <div class="row">
                    <div class="col-lg-8">
                        <?php if (empty($orderItems)): ?>
                        <div class="alert alert-info">В заявке пока нет товаров и услуг</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>№</th>
                                        <th>Наименование</th>
                                        <th>Тип</th>
                                        <th>Количество</th>
                                        <th>Цена</th>
                                        <th>Сумма</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $currentGroup = '';
                                    $counter = 1;
                                    $totalAmount = 0;
                                    
                                    foreach ($orderItems as $item): 
                                        if ($item['group_name'] && $item['group_name'] != $currentGroup):
                                            $currentGroup = $item['group_name'];
                                    ?>
                                    <tr>
                                        <td colspan="7" class="table-secondary"><strong><?php echo $item['group_name']; ?></strong></td>
                                    </tr>
                                    <?php endif; 
                                        $sum = $item['price'] * $item['quantity'];
                                        $totalAmount += $sum;
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['type'] == 'product' ? 'Товар' : 'Услуга'; ?></td>
                                        <td>
                                            <form method="post" action="orders.php?action=update_item" class="d-flex">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control form-control-sm" style="width: 70px;">
                                                <span class="ms-1"><?php echo $item['unit']; ?></span>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="price" value="<?php echo number_format($item['price'], 2, ',', ''); ?>" class="form-control form-control-sm" style="width: 90px;">
                                                    <span class="input-group-text">₽</span>
                                                </div>
                                            </td>
                                            <td><?php echo number_format($sum, 2, ',', ' '); ?> ₽</td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="submit" class="btn btn-sm btn-outline-dark" data-bs-toggle="tooltip" title="Сохранить">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                            </form>
                                                    <form method="post" action="orders.php?action=remove_item" class="d-inline">
                                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="tooltip" title="Удалить">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end"><strong>Итого:</strong></td>
                                        <td><strong><?php echo number_format($totalAmount, 2, ',', ' '); ?> ₽</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">Добавить товар/услугу</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="orders.php?action=add_item" id="add-order-item-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="item_id" class="form-label">Товар/Услуга*</label>
                                        <select class="form-select" id="item_id" name="item_id" required>
                                            <option value="">Выберите товар или услугу</option>
                                            <?php if (!empty($products)): ?>
                                            <optgroup label="Товары">
                                                <?php foreach ($products as $product): ?>
                                                <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                                                    <?php echo $product['name']; ?> (<?php echo number_format($product['price'], 2, ',', ' '); ?> ₽)
                                                </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($services)): ?>
                                            <optgroup label="Услуги">
                                                <?php foreach ($services as $service): ?>
                                                <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                                    <?php echo $service['name']; ?> (<?php echo number_format($service['price'], 2, ',', ' '); ?> ₽)
                                                </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="quantity" class="form-label">Количество*</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="price" class="form-label">Цена*</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="price" name="price" value="0,00" required>
                                                <span class="input-group-text">₽</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="group_name" class="form-label">Группа</label>
                                        <select class="form-select" id="group_name" name="group_name">
                                            <option value="">Без группы</option>
                                            <?php foreach ($orderGroups as $group): ?>
                                            <option value="<?php echo $group['name']; ?>">
                                                <?php echo $group['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-dark">
                                            <i class="bi bi-plus-circle"></i> Добавить
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">Добавить группу</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="orders.php?action=add_group">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Название группы*</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="parent_id" class="form-label">Родительская группа</label>
                                        <select class="form-select" id="parent_id" name="parent_id">
                                            <option value="">Корневая группа</option>
                                            <?php foreach ($orderGroups as $group): ?>
                                            <option value="<?php echo $group['id']; ?>">
                                                <?php echo $group['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-dark">
                                            <i class="bi bi-plus-circle"></i> Добавить группу
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'suborders' ? 'show active' : ''; ?>" id="suborders" role="tabpanel" aria-labelledby="suborders-tab">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#createSuborderModal">
                            <i class="bi bi-plus-circle"></i> Добавить подзаявку
                        </button>
                    </div>
                </div>
                
                <?php if (empty($suborders)): ?>
                <div class="alert alert-info">У этой заявки пока нет подзаявок</div>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($suborders as $suborder): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-folder-plus me-2"></i>
                                <?php echo htmlspecialchars($suborder['name']); ?>
                            </div>
                            <div>
                                <span class="badge rounded-pill bg-<?php
                                    switch ($suborder['status']) {
                                        case 'draft': echo 'secondary'; break;
                                        case 'submitted': echo 'primary'; break;
                                        case 'processed': echo 'info'; break;
                                        case 'completed': echo 'success'; break;
                                        case 'cancelled': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>"><?php echo $statuses[$suborder['status']] ?? ucfirst($suborder['status']); ?></span>
                                <div class="btn-group btn-group-sm ms-2">
                                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#editSuborderModal<?php echo $suborder['id']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSuborderModal<?php echo $suborder['id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal fade" id="editSuborderModal<?php echo $suborder['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Редактировать подзаявку</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                </div>
                                <form method="post" action="tasks.php?action=edit_suborder">
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?php echo $suborder['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="name<?php echo $suborder['id']; ?>" class="form-label">Название подзаявки*</label>
                                            <input type="text" class="form-control" id="name<?php echo $suborder['id']; ?>" name="name" value="<?php echo htmlspecialchars($suborder['name']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="status<?php echo $suborder['id']; ?>" class="form-label">Статус*</label>
                                            <select class="form-select" id="status<?php echo $suborder['id']; ?>" name="status" required>
                                                <option value="draft" <?php echo $suborder['status'] == 'draft' ? 'selected' : ''; ?>>Черновик</option>
                                                <option value="submitted" <?php echo $suborder['status'] == 'submitted' ? 'selected' : ''; ?>>Отправлена</option>
                                                <option value="processed" <?php echo $suborder['status'] == 'processed' ? 'selected' : ''; ?>>В обработке</option>
                                                <option value="completed" <?php echo $suborder['status'] == 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                                <option value="cancelled" <?php echo $suborder['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменена</option>
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
                    
                    <div class="modal fade" id="deleteSuborderModal<?php echo $suborder['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Удаление подзаявки</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Вы действительно хотите удалить подзаявку "<?php echo htmlspecialchars($suborder['name']); ?>"?</p>
                                    <p class="text-danger">Внимание! Будут удалены все связанные задачи.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                    <form method="post" action="tasks.php?action=delete_suborder">
                                        <input type="hidden" name="id" value="<?php echo $suborder['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Удалить</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="orders.php" class="btn btn-outline-secondary">Назад к списку</a>
    </div>
</div>

<div class="modal fade" id="createSuborderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Создать подзаявку</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form method="post" action="orders.php?action=create_suborder">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="suborder_name" class="form-label">Название подзаявки*</label>
                        <input type="text" class="form-control" id="suborder_name" name="name" required>
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
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('item_id');
    const priceInput = document.getElementById('price');
    
    if (itemSelect && priceInput) {
        itemSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price) {
                priceInput.value = price.toString().replace('.', ',');
            }
        });
    }
    
    const searchInput = document.getElementById('orders-table-search');
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