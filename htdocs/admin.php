<?php
declare(strict_types=1);

/**
 * Простая административная панель.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tasks.php';
require_once __DIR__ . '/../includes/admin.php';

requireAuth();
requireManager();

$currentUser = getCurrentUser();
$successMessage = getFlashMessage('success');
$errorMessage = getFlashMessage('error');

$userFormData = [
    'full_name' => '',
    'email' => '',
    'password' => '',
    'role' => 'executor',
];

$projectFormData = [
    'name' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $userFormData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'role' => trim($_POST['role'] ?? 'executor'),
        ];

        $errors = validateUserForm($pdo, $userFormData);

        if ($errors === []) {
            createUser($pdo, $userFormData);
            writeAuditLog(
                $pdo,
                (int) $currentUser['id'],
                'user_created',
                'Создан новый пользователь.',
                ['email' => $userFormData['email'], 'role' => $userFormData['role']]
            );
            setFlashMessage('success', 'Новый пользователь успешно добавлен.');
            header('Location: admin.php');
            exit;
        }

        $errorMessage = implode(' ', $errors);
    }

    if ($action === 'create_project') {
        $projectFormData = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];

        $errors = validateProjectForm($pdo, $projectFormData);

        if ($errors === []) {
            createProject($pdo, $projectFormData);
            writeAuditLog(
                $pdo,
                (int) $currentUser['id'],
                'project_created',
                'Создан новый проект.',
                ['name' => $projectFormData['name']]
            );
            setFlashMessage('success', 'Новый проект успешно добавлен.');
            header('Location: admin.php');
            exit;
        }

        $errorMessage = implode(' ', $errors);
    }
}

$stats = getAdminStats($pdo);
$users = getAllUsers($pdo);
$projects = getAllProjects($pdo);
$auditLogs = getAuditLogs($pdo);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - <?php echo htmlspecialchars($appConfig['app_name']); ?></title>
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('flowsync-theme') || 'light');
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body class="app-body">
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand text-decoration-none"><?php echo htmlspecialchars($appConfig['app_name']); ?></a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="tasks.php" class="btn btn-outline-light btn-sm">Задачи</a>
                <a href="reports.php" class="btn btn-outline-light btn-sm">Отчетность</a>
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

    <div class="container py-4">
        <?php if ($successMessage !== null): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <h1 class="h3 mb-1">Административная панель</h1>
            <p class="text-muted mb-0">Раздел для управления сотрудниками, проектами и основными объектами системы.</p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="card border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Пользователи</div><div class="display-6"><?php echo $stats['users_count']; ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Проекты</div><div class="display-6"><?php echo $stats['projects_count']; ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Всего задач</div><div class="display-6"><?php echo $stats['tasks_count']; ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Открытые задачи</div><div class="display-6"><?php echo $stats['open_tasks_count']; ?></div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Добавить пользователя</h2>
                        <form method="post">
                            <input type="hidden" name="action" value="create_user">
                            <div class="mb-3"><label for="full_name" class="form-label">ФИО</label><input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($userFormData['full_name']); ?>"></div>
                            <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userFormData['email']); ?>"></div>
                            <div class="mb-3"><label for="password" class="form-label">Пароль</label><input type="text" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($userFormData['password']); ?>"></div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Роль</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="manager" <?php echo $userFormData['role'] === 'manager' ? 'selected' : ''; ?>>Менеджер</option>
                                    <option value="executor" <?php echo $userFormData['role'] === 'executor' ? 'selected' : ''; ?>>Исполнитель</option>
                                    <option value="reviewer" <?php echo $userFormData['role'] === 'reviewer' ? 'selected' : ''; ?>>Проверяющий</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Добавить пользователя</button>
                        </form>
                    </div>
                </div>

                <div class="card border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Добавить проект</h2>
                        <form method="post">
                            <input type="hidden" name="action" value="create_project">
                            <div class="mb-3"><label for="name" class="form-label">Название проекта</label><input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($projectFormData['name']); ?>"></div>
                            <div class="mb-3"><label for="description" class="form-label">Описание проекта</label><textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($projectFormData['description']); ?></textarea></div>
                            <button type="submit" class="btn btn-dark w-100">Добавить проект</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Сотрудники</h2>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>ID</th><th>ФИО</th><th>Email</th><th>Роль</th><th>Создан</th></tr></thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo (int) $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars(getRoleLabel($user['role'])); ?></td>
                                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Проекты</h2>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>ID</th><th>Название</th><th>Описание</th><th>Задач</th><th>Создан</th></tr></thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><?php echo (int) $project['id']; ?></td>
                                            <td><?php echo htmlspecialchars($project['name']); ?></td>
                                            <td><?php echo htmlspecialchars($project['description'] ?? ''); ?></td>
                                            <td><?php echo (int) $project['tasks_count']; ?></td>
                                            <td><?php echo htmlspecialchars($project['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 mt-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Журнал аудита</h2>
                        <p class="text-muted small">В журнале хранится не более 100 последних действий. При превышении лимита самые старые записи удаляются автоматически.</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>ID</th><th>Тип</th><th>Действие</th><th>Пользователь</th><th>IP</th><th>Дата</th></tr></thead>
                                <tbody>
                                    <?php foreach ($auditLogs as $auditLog): ?>
                                        <tr>
                                            <td><?php echo (int) $auditLog['id']; ?></td>
                                            <td><?php echo htmlspecialchars(getAuditTypeLabel($auditLog['action_type'])); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($auditLog['action_text']); ?></div>
                                                <?php if (!empty($auditLog['context_json'])): ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($auditLog['context_json']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($auditLog['full_name'] ?? 'Гость'); ?></td>
                                            <td><?php echo htmlspecialchars($auditLog['ip_address']); ?></td>
                                            <td><?php echo htmlspecialchars($auditLog['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/theme.js"></script>
</body>
</html>
