<?php
$pageTitle = 'Панель управления';
require_once 'includes/db_functions.php';
requireLogin();

require_once 'includes/card_functions.php';
require_once 'includes/group_functions.php';
require_once 'includes/order_functions.php';

$productsCount = count(getAllItems('product'));
$servicesCount = count(getAllItems('service'));
$groupsCount = count(getAllGroups());
$ordersCount = count(getAllOrders($_SESSION['user_id']));

$recentOrders = getAllOrders($_SESSION['user_id']);
$recentOrders = array_slice($recentOrders, 0, 5);

require_once 'includes/header.php';
?>

<h1 class="mb-4">Панель управления</h1>

<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="dashboard-icon text-primary">
                    <i class="bi bi-box"></i>
                </div>
                <h5 class="card-title"><?php echo $productsCount; ?></h5>
                <p class="card-text">Товаров</p>
                <a href="products.php" class="btn btn-sm btn-outline-dark">Управление</a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="dashboard-icon text-success">
                    <i class="bi bi-tools"></i>
                </div>
                <h5 class="card-title"><?php echo $servicesCount; ?></h5>
                <p class="card-text">Услуг</p>
                <a href="services.php" class="btn btn-sm btn-outline-dark">Управление</a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="dashboard-icon text-info">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <h5 class="card-title"><?php echo $groupsCount; ?></h5>
                <p class="card-text">Групп</p>
                <a href="groups.php" class="btn btn-sm btn-outline-dark">Управление</a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="dashboard-icon text-warning">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <h5 class="card-title"><?php echo $ordersCount; ?></h5>
                <p class="card-text">Заявок</p>
                <a href="orders.php" class="btn btn-sm btn-outline-dark">Управление</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Статистика заявок</h5>
            </div>
            <div class="card-body">
                <canvas id="orders-chart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Последние заявки</h5>
                <a href="orders.php" class="btn btn-sm btn-outline-dark">Все заявки</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentOrders)): ?>
                <div class="text-center p-4">
                    <p class="mb-0">У вас пока нет заявок</p>
                    <a href="orders.php?action=create" class="btn btn-sm btn-dark mt-2">Создать заявку</a>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentOrders as $order): ?>
                    <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Заявка #<?php echo $order['id']; ?></h6>
                            <small><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></small>
                        </div>
                        <p class="mb-1">Статус: <?php echo ucfirst($order['status']); ?></p>
                        <small>Сумма: <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> ₽</small>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Быстрые действия</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="products.php?action=create" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-plus-circle me-2"></i> Создать товар
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="services.php?action=create" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-plus-circle me-2"></i> Создать услугу
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="groups.php?action=create" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-plus-circle me-2"></i> Создать группу
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="orders.php?action=create" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-plus-circle me-2"></i> Создать заявку
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Импорт/Экспорт</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="products.php?action=import" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-upload me-2"></i> Импорт товаров
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="products.php?action=export" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-download me-2"></i> Экспорт товаров
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="services.php?action=import" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-upload me-2"></i> Импорт услуг
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="services.php?action=export" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-download me-2"></i> Экспорт услуг
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>