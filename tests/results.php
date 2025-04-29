<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Успешно!";
include_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Результаты сохранены!</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">Отлично! Ваши результаты сохранены!</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        Теперь вы можете перейти к новым тестам или вернуться на главную :D
                    </div>
                    
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <a href="/tests/index.php" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-list-alt me-1"></i> К тестам
                        </a>
                        <a href="/" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-home me-1"></i> На главную
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>