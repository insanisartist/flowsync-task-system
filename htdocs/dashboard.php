<?php
/**
 * Личный кабинет после входа.
 *
 * Что делает:
 * - проверяет, что пользователь вошел в систему;
 * - показывает его имя, роль и подсказку по следующему шагу;
 * - служит базой для будущей страницы задач.
 *
 * Как открыть:
 * - после входа пользователь попадает сюда автоматически.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tasks.php';

requireAuth();

$currentUser = getCurrentUser();
$successMessage = getFlashMessage('success');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php echo htmlspecialchars($appConfig['app_name']); ?></title>
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('flowsync-theme') || 'light');
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body class="app-body">
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar">
        <div class="container">
            <span class="navbar-brand"><?php echo htmlspecialchars($appConfig['app_name']); ?></span>
            <div class="ms-auto d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-light btn-sm theme-toggle" data-theme-toggle>
                    <span data-theme-label class="theme-toggle-label">Светлая тема</span>
                </button>
                <span class="text-white-50 small">
                    <?php echo htmlspecialchars($currentUser['full_name']); ?>
                    (<?php echo htmlspecialchars(getRoleLabel($currentUser['role'])); ?>)
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?php if ($successMessage !== null): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-3">Главная панель</h1>
                        <p class="mb-3">
                            В этом разделе собраны основные переходы по системе:
                            управление задачами, просмотр отчетности и контроль
                            текущего рабочего окружения приложения.
                        </p>
                        <div class="alert alert-info mb-0">
                            Текущее окружение: <strong><?php echo htmlspecialchars((string) ENV_TYPE); ?></strong>
                        </div>
                        <div class="mt-3">
                            <a href="tasks.php" class="btn btn-primary">Перейти к задачам</a>
                            <a href="reports.php" class="btn btn-outline-dark">Открыть отчетность</a>
                            <?php if (isManager($currentUser)): ?>
                                <a href="admin.php" class="btn btn-outline-primary">Админ-панель</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Профиль пользователя</h2>
                        <p class="mb-2"><strong>Имя:</strong> <?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                        <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
                        <p class="mb-0"><strong>Роль:</strong> <?php echo htmlspecialchars(getRoleLabel($currentUser['role'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/theme.js"></script>
</body>
</html>
