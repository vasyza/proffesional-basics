<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Роли в рабочих группах - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .role-card {
            margin-bottom: 30px;
            transition: transform 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .role-header {
            padding: 15px;
            color: #fff;
        }
        
        .role-leader {
            background-color: #dc3545;
        }
        
        .role-analyst {
            background-color: #0d6efd;
        }
        
        .role-developer {
            background-color: #198754;
        }
        
        .role-tester {
            background-color: #6f42c1;
        }
        
        .role-designer {
            background-color: #fd7e14;
        }

        .role-devops {
            background-color: #20c997;
        }

        .role-content {
            background-color: #6c757d;
        }
        
        .role-icon {
            font-size: 2rem;
            margin-right: 15px;
        }
        
        .role-title {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .skill-tag {
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 5px;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            background-color: rgba(255,255,255,0.15);
            color: #fff;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 3px;
            background-color: #0d6efd;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">Портал ИТ-профессий</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/#about">О портале</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/professions.php">Каталог профессий</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/groups.php">Рабочие группы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/group_roles.php">Роли в группах</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Личный кабинет
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="/cabinet.php">Профиль</a></li>
                                <?php if ($userRole == 'admin'): ?>
                                    <li><a class="dropdown-item" href="/admin/index.php">Панель администратора</a></li>
                                <?php elseif ($userRole == 'expert'): ?>
                                    <li><a class="dropdown-item" href="/expert/index.php">Панель эксперта</a></li>
                                <?php elseif ($userRole == 'consultant'): ?>
                                    <li><a class="dropdown-item" href="/consultant/index.php">Панель консультанта</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/auth/logout.php">Выход</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="/auth/login.php">Вход</a>
                        <a class="nav-link" href="/auth/register.php">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row mb-5">
            <div class="col-md-8">
                <h1 class="section-title">Роли в рабочих группах</h1>
                <p class="lead">
                    Рабочие группы студентов на нашем портале предназначены для эффективной совместной работы над проектами.
                    Как и в реальных ИТ-проектах, каждый участник группы может выполнять определенную роль, которая соответствует
                    его навыкам, интересам и карьерным целям.
                </p>
                <p>
                    Разделение на роли помогает:
                </p>
                <ul>
                    <li>Повысить эффективность командной работы</li>
                    <li>Четко распределить ответственность</li>
                    <li>Приобрести опыт работы в конкретной специализации</li>
                    <li>Лучше понять взаимодействие между разными специалистами в ИТ-проектах</li>
                </ul>
                <p>
                    Ниже представлены основные роли, которые могут быть назначены участникам рабочих групп.
                    Один участник может выполнять несколько ролей, особенно в небольших группах.
                </p>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">Хотите создать свою группу?</h5>
                        <p class="card-text">Зарегистрированные пользователи могут создавать рабочие группы и приглашать в них других участников.</p>
                        <?php if ($isLoggedIn): ?>
                            <a href="/groups.php?action=create" class="btn btn-primary">Создать группу</a>
                        <?php else: ?>
                            <a href="/auth/register.php" class="btn btn-outline-primary">Зарегистрироваться</a>
                            <a href="/auth/login.php" class="btn btn-link">Войти в систему</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Руководитель группы -->
            <div class="col-md-6">
                <div class="card role-card">
                    <div class="role-header role-leader d-flex align-items-center">
                        <div class="role-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div>
                            <h2 class="role-title">Руководитель группы</h2>
                            <div class="mt-2">
                                <span class="skill-tag">Лидерство</span>
                                <span class="skill-tag">Планирование</span>
                                <span class="skill-tag">Координация</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Функции и обязанности:</h5>
                        <ul>
                            <li>Координация работы всей группы</li>
                            <li>Постановка задач и контроль их выполнения</li>
                            <li>Планирование этапов работы и сроков</li>
                            <li>Распределение ресурсов</li>
                            <li>Коммуникация с заинтересованными сторонами</li>
                            <li>Мотивация команды</li>
                        </ul>
                        <h5 class="card-title">Необходимые навыки:</h5>
                        <ul>
                            <li>Организаторские способности</li>
                            <li>Коммуникабельность</li>
                            <li>Умение принимать решения</li>
                            <li>Тайм-менеджмент</li>
                            <li>Базовое понимание всех аспектов проекта</li>
                        </ul>
                        <div class="mt-3">
                            <p><strong>Соответствует профессиям:</strong> Project Manager, Team Lead, Scrum Master</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Системный аналитик -->
            <div class="col-md-6">
                <div class="card role-card">
                    <div class="role-header role-analyst d-flex align-items-center">
                        <div class="role-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h2 class="role-title">Системный аналитик</h2>
                            <div class="mt-2">
                                <span class="skill-tag">Анализ</span>
                                <span class="skill-tag">Моделирование</span>
                                <span class="skill-tag">Документация</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Функции и обязанности:</h5>
                        <ul>
                            <li>Сбор и анализ требований</li>
                            <li>Проектирование архитектуры системы</li>
                            <li>Создание документации (ТЗ, пользовательские истории)</li>
                            <li>Разработка моделей данных</li>
                            <li>Проектирование интерфейсов</li>
                            <li>Участие в тестировании</li>
                        </ul>
                        <h5 class="card-title">Необходимые навыки:</h5>
                        <ul>
                            <li>Аналитическое мышление</li>
                            <li>Умение моделировать процессы</li>
                            <li>Понимание жизненного цикла разработки</li>
                            <li>Навыки составления документации</li>
                            <li>Коммуникативные навыки</li>
                        </ul>
                        <div class="mt-3">
                            <p><strong>Соответствует профессиям:</strong> Бизнес-аналитик, Системный аналитик, Product Owner</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Разработчик -->
            <div class="col-md-6">
                <div class="card role-card mt-4">
                    <div class="role-header role-developer d-flex align-items-center">
                        <div class="role-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <div>
                            <h2 class="role-title">Разработчик</h2>
                            <div class="mt-2">
                                <span class="skill-tag">Программирование</span>
                                <span class="skill-tag">Алгоритмы</span>
                                <span class="skill-tag">Код</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Функции и обязанности:</h5>
                        <ul>
                            <li>Разработка программного кода</li>
                            <li>Создание компонентов системы</li>
                            <li>Оптимизация производительности</li>
                            <li>Рефакторинг и поддержка кода</li>
                            <li>Интеграция с внешними системами</li>
                            <li>Исправление ошибок</li>
                        </ul>
                        <h5 class="card-title">Необходимые навыки:</h5>
                        <ul>
                            <li>Знание языков программирования</li>
                            <li>Навыки алгоритмизации</li>
                            <li>Понимание принципов разработки</li>
                            <li>Работа с версионным контролем</li>
                            <li>Знание структур данных</li>
                        </ul>
                        <div class="mt-3">
                            <p><strong>Соответствует профессиям:</strong> Frontend-разработчик, Backend-разработчик, Full-stack разработчик, Mobile-разработчик</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Тестировщик -->
            <div class="col-md-6">
                <div class="card role-card mt-4">
                    <div class="role-header role-tester d-flex align-items-center">
                        <div class="role-icon">
                            <i class="fas fa-bug"></i>
                        </div>
                        <div>
                            <h2 class="role-title">Тестировщик</h2>
                            <div class="mt-2">
                                <span class="skill-tag">Тестирование</span>
                                <span class="skill-tag">Контроль качества</span>
                                <span class="skill-tag">Отладка</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Функции и обязанности:</h5>
                        <ul>
                            <li>Создание тестовых сценариев</li>
                            <li>Ручное и автоматизированное тестирование</li>
                            <li>Поиск и регистрация ошибок</li>
                            <li>Проверка соответствия требованиям</li>
                            <li>Регрессионное тестирование</li>
                            <li>Составление отчетов о тестировании</li>
                        </ul>
                        <h5 class="card-title">Необходимые навыки:</h5>
                        <ul>
                            <li>Внимательность к деталям</li>
                            <li>Логическое мышление</li>
                            <li>Базовые знания программирования</li>
                            <li>Знание методологий тестирования</li>
                            <li>Умение документировать ошибки</li>
                        </ul>
                        <div class="mt-3">
                            <p><strong>Соответствует профессиям:</strong> QA-инженер, Тестировщик, Инженер по тестированию, Автоматизатор тестирования</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Дизайнер -->
            <div class="col-md-6">
                <div class="card role-card mt-4">
                    <div class="role-header role-designer d-flex align-items-center">
                        <div class="role-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div>
                            <h2 class="role-title">Дизайнер</h2>
                            <div class="mt-2">
                                <span class="skill-tag">UI/UX</span>
                                <span class="skill-tag">Графика</span>
                                <span class="skill-tag">Прототипирование</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Функции и обязанности:</h5>
                        <ul>
                            <li>Разработка визуальной концепции</li>
                            <li>Проектирование пользовательского интерфейса</li>
                            <li>Создание прототипов</li>
                            <li>Разработка стилей и компонентов</li>
                            <li>Подготовка графических материалов</li>
                            <li>Обеспечение удобства использования</li>
                        </ul>
                        <h5 class="card-title">Необходимые навыки:</h5>
                        <ul>
                            <li>Владение графическими редакторами</li>
                            <li>Понимание принципов UI/UX</li>
                            <li>Чувство стиля и композиции</li>
                            <li>Знание типографики</li>
                            <li>Умение создавать прототипы</li>
                        </ul>
                        <div class="mt-3">
                            <p><strong>Соответствует профессиям:</strong> UI/UX-дизайнер, Веб-дизайнер, Графический дизайнер, Проектировщик интерфейсов</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- DevOps-инженер -->
            <div class="col-md-6">
                <div class="card role-card mt-4">
                    <div class="role-header role-devops d-flex align-items-center">
                        <div class="role-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div>
                            <h2 class="role-title">DevOps-инженер</h2>
                            <div class="mt-2">
                                <span class="skill-tag">Инфраструктура</span>
                                <span class="skill-tag">Деплой</span>
                                <span class="skill-tag">Автоматизация</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Функции и обязанности:</h5>
                        <ul>
                            <li>Настройка инфраструктуры</li>
                            <li>Автоматизация процессов разработки</li>
                            <li>Настройка CI/CD</li>
                            <li>Мониторинг и оптимизация производительности</li>
                            <li>Обеспечение безопасности</li>
                            <li>Управление серверами и сетями</li>
                        </ul>
                        <h5 class="card-title">Необходимые навыки:</h5>
                        <ul>
                            <li>Знание операционных систем</li>
                            <li>Навыки работы с серверами</li>
                            <li>Понимание сетевых технологий</li>
                            <li>Знание инструментов виртуализации</li>
                            <li>Навыки автоматизации</li>
                        </ul>
                        <div class="mt-3">
                            <p><strong>Соответствует профессиям:</strong> DevOps-инженер, System Administrator, Site Reliability Engineer</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Контент-менеджер -->
            <div class="col-md-6 mx-auto">
                <div class="card role-card mt-4">
                    <div class="role-header role-content d-flex align-items-center">
                        <div class="role-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <h2 class="role-title">Контент-менеджер</h2>
                            <div class="mt-2">
                                <span class="skill-tag">Контент</span>
                                <span class="skill-tag">Копирайтинг</span>
                                <span class="skill-tag">Документация</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Функции и обязанности:</h5>
                        <ul>
                            <li>Создание текстового контента</li>
                            <li>Подготовка справочных материалов</li>
                            <li>Составление пользовательской документации</li>
                            <li>Редактирование и структурирование информации</li>
                            <li>Работа с медиа-контентом</li>
                            <li>SEO-оптимизация текстов</li>
                        </ul>
                        <h5 class="card-title">Необходимые навыки:</h5>
                        <ul>
                            <li>Грамотная письменная речь</li>
                            <li>Навыки копирайтинга</li>
                            <li>Умение структурировать информацию</li>
                            <li>Базовые знания SEO</li>
                            <li>Навыки работы с CMS</li>
                        </ul>
                        <div class="mt-3">
                            <p><strong>Соответствует профессиям:</strong> Контент-менеджер, Технический писатель, Копирайтер</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-md-12">
                <h2 class="section-title">Распределение ролей в группе</h2>
                <p>
                    При создании рабочей группы рекомендуется следующий подход к распределению ролей:
                </p>
                <ol>
                    <li><strong>Оцените навыки и интересы участников.</strong> Каждый должен заниматься тем, что ему интересно и в чем он может развиваться.</li>
                    <li><strong>Учитывайте специфику проекта.</strong> Некоторые проекты могут требовать больше дизайнеров, другие - больше разработчиков.</li>
                    <li><strong>Не бойтесь совмещать роли.</strong> В небольших группах один участник может выполнять несколько ролей.</li>
                    <li><strong>Предусмотрите возможность ротации.</strong> Участники могут меняться ролями для получения разнообразного опыта.</li>
                    <li><strong>Определите руководителя группы.</strong> Эта роль необходима для координации общей работы.</li>
                </ol>
                <p>
                    Помните, что эффективная группа - это не просто набор специалистов, а команда, в которой каждый понимает свою роль и вклад в общий результат.
                </p>
            </div>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <div class="row mt-4 mb-5">
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3>Хотите присоединиться к рабочей группе?</h3>
                        <p>Вы можете присоединиться к существующим группам или создать свою собственную группу.</p>
                        <div class="mt-3">
                            <a href="/groups.php" class="btn btn-primary me-2">Просмотреть группы</a>
                            <a href="/groups.php?action=create" class="btn btn-outline-primary">Создать группу</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Портал ИТ-профессий</h5>
                    <p>Информационная платформа о профессиях в ИТ-сфере, способствующая профессиональной ориентации, выбору карьерного пути и профессиональному развитию.</p>
                </div>
                <div class="col-md-3">
                    <h5>Навигация</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white">Главная</a></li>
                        <li><a href="/professions.php" class="text-white">Каталог профессий</a></li>
                        <li><a href="/groups.php" class="text-white">Рабочие группы</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Контакты</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@it-professions.ru</li>
                        <li><i class="fas fa-phone me-2"></i> +7 (999) 123-45-67</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">© 2025 Портал ИТ-профессий. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
