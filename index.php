<?php
$pageTitle = 'Главная';
require_once 'includes/header.php';
?>

<div class="jumbotron text-center bg-light p-5 rounded-3 mb-4">
    <h1 class="display-4">Система учета</h1>
    <p class="lead">Единая система для управления товарами, услугами и заявками, разработанная WR8</p>
    <?php if (!isLoggedIn()): ?>
    <hr class="my-4">
    <p>Для начала работы с системой необходимо выполнить вход или зарегистрироваться.</p>
    <div class="d-flex justify-content-center gap-2">
        <a class="btn btn-dark btn-lg" href="login.php" role="button">Вход</a>
        <a class="btn btn-outline-dark btn-lg" href="register.php" role="button">Регистрация</a>
    </div>
    <?php else: ?>
    <hr class="my-4">
    <p>Перейдите в панель управления для начала работы.</p>
    <a class="btn btn-dark btn-lg" href="dashboard.php" role="button">Панель управления</a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">Управление товарами и услугами</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Создавайте карточки товаров и услуг, организуйте их в древовидные группы, импортируйте и экспортируйте данные.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">Управление заявками</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Создавайте заявки, добавляйте товары и услуги, группируйте их для удобства и отслеживайте статус выполнения.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">Экспорт в PDF</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Экспортируйте заявки в PDF-файлы для печати чеков с товарами и услугами в табличном виде.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>