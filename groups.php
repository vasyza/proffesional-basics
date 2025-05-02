<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Получение ID группы из URL, если он был передан
$group_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Подключение к базе данных
try {
    $pdo = getDbConnection();
    
    // Если передан ID, показываем детальную информацию о группе
    if ($group_id) {
        $stmt = $pdo->prepare("
            SELECT sg.*
            FROM student_groups sg
            WHERE sg.id = ?
        ");
        $stmt->execute([$group_id]);
        $group = $stmt->fetch();
        
        // Если группа не найдена, перенаправляем на общий список
        if (!$group) {
            header("Location: groups.php");
            exit;
        }
        
        // Получаем участников группы
        $stmt = $pdo->prepare("
            SELECT gm.*, u.name, u.login
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY CASE WHEN gm.role = 'leader' THEN 0 ELSE 1 END
        ");
        $stmt->execute([$group_id]);
        $group_members = $stmt->fetchAll();
        
        // Проверяем, является ли пользователь участником группы
        $is_member = false;
        $user_role_in_group = '';
        if ($isLoggedIn) {
            foreach ($group_members as $member) {
                if ($member['user_id'] == $userId) {
                    $is_member = true;
                    $user_role_in_group = $member['role'];
                    break;
                }
            }
        }
        
    } else {
        // Получаем список всех групп
        $stmt = $pdo->query("
            SELECT sg.*, COUNT(gm.id) as member_count,
            (SELECT COUNT(*) FROM group_members WHERE group_id = sg.id AND role = 'leader') as has_leader
            FROM student_groups sg
            LEFT JOIN group_members gm ON sg.id = gm.group_id
            GROUP BY sg.id
            ORDER BY sg.created_at DESC
        ");
        $groups = $stmt->fetchAll();
        
        // Получаем группы, в которых состоит текущий пользователь
        $user_groups = [];
        if ($isLoggedIn) {
            $stmt = $pdo->prepare("
                SELECT sg.id, sg.name, gm.role
                FROM student_groups sg
                JOIN group_members gm ON sg.id = gm.group_id
                WHERE gm.user_id = ?
            ");
            $stmt->execute([$userId]);
            $user_groups = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    die("Ошибка при подключении к базе данных: " . $e->getMessage());
}

// Подключение заголовка
include 'includes/header.php';
?>

<?php if ($group_id): ?>
    <!-- Детальная информация о группе -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/">Главная</a></li>
                        <li class="breadcrumb-item"><a href="/groups.php">Рабочие группы</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($group['name']); ?></li>
                    </ol>
                </nav>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h1 class="mb-3"><?php echo htmlspecialchars($group['name']); ?></h1>
                        <p class="lead">
                            <?php echo nl2br(htmlspecialchars($group['description'])); ?>
                        </p>
                        <p class="text-muted">
                            <i class="far fa-calendar-alt me-1"></i> Создана: <?php echo date('d.m.Y', strtotime($group['created_at'])); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if ($isLoggedIn): ?>
                            <?php if ($is_member): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-1"></i> Вы участник группы (<?php echo htmlspecialchars($user_role_in_group); ?>)
                                </div>
                                <?php if ($user_role_in_group == 'leader'): ?>
                                    <a href="/group_edit.php?id=<?php echo $group_id; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i> Управление группой
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <form action="/join_group.php" method="post">
                                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-1"></i> Вступить в группу
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <a href="/auth/login.php">Войдите</a> или <a href="/auth/register.php">зарегистрируйтесь</a>, чтобы присоединиться к группе
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title mb-0">Участники группы</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($group_members) > 0): ?>
                            <div class="row">
                                <?php foreach ($group_members as $member): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 24px;">
                                                            <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h5 class="mb-0"><?php echo htmlspecialchars($member['name']); ?></h5>
                                                        <p class="text-muted mb-0">
                                                            <?php 
                                                                $roleText = '';
                                                                switch ($member['role']) {
                                                                    case 'leader': $roleText = 'Руководитель группы'; break;
                                                                    case 'developer': $roleText = 'Разработчик'; break;
                                                                    case 'designer': $roleText = 'Дизайнер'; break;
                                                                    case 'analyst': $roleText = 'Аналитик'; break;
                                                                    case 'tester': $roleText = 'Тестировщик'; break;
                                                                    default: $roleText = $member['role']; break;
                                                                }
                                                                echo $roleText;
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">В этой группе пока нет участников</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Список всех групп -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-4">Рабочие группы студентов</h1>
                <p class="lead">
                    Рабочие группы - это учебные команды для совместного изучения ИТ-профессий, выполнения проектов и обмена опытом.
                </p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($isLoggedIn): ?>
                    <a href="/create_group.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Создать группу
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($isLoggedIn && count($user_groups) > 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h3 class="card-title mb-0">Ваши группы</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($user_groups as $group): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($group['name']); ?></h5>
                                        <p class="card-text">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($group['role']); ?></span>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white border-top-0">
                                        <a href="/groups.php?id=<?php echo $group['id']; ?>" class="btn btn-primary btn-sm">Перейти</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-light">
                <h3 class="card-title mb-0">Все группы</h3>
            </div>
            <div class="card-body">
                <?php if (count($groups) > 0): ?>
                    <div class="row">
                        <?php foreach ($groups as $group): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($group['name']); ?></h5>
                                        <p class="card-text">
                                            <?php 
                                                $desc = htmlspecialchars($group['description']);
                                                echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                            ?>
                                        </p>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">
                                                <i class="fas fa-users me-1"></i> <?php echo $group['member_count']; ?> участников
                                            </span>
                                            <?php if ($group['has_leader']): ?>
                                                <span class="badge bg-success">Есть руководитель</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Нет руководителя</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-top-0">
                                        <a href="/groups.php?id=<?php echo $group['id']; ?>" class="btn btn-primary btn-sm">Подробнее</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Пока не создано ни одной рабочей группы. Будьте первым!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Подключение подвала
include 'includes/footer.php';
?> 