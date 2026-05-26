<?php
/**
 * Страница отчетности по задачам.
 *
 * Что делает:
 * - показывает сводные показатели по задачам;
 * - использует те же фильтры, что и страница задач;
 * - позволяет скачать Excel и Word по текущей выборке.
 *
 * Как открыть:
 * - http://localhost/diplom/htdocs/reports.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tasks.php';

requireAuth();

$currentUser = getCurrentUser();
$projects = getProjects($pdo);
$executors = getUsersByRole($pdo, 'executor');
$filters = getTaskFilters();
$tasks = getTasks($pdo, $filters);
$filterQueryString = getTaskFilterQueryString($filters);

$totalTasks = count($tasks);
$closedTasks = 0;
$reviewTasks = 0;
$revisionTasks = 0;
$highPriorityTasks = 0;

foreach ($tasks as $task) {
    if ($task['status'] === 'closed') {
        $closedTasks++;
    }

    if ($task['status'] === 'on_review') {
        $reviewTasks++;
    }

    if ($task['status'] === 'needs_revision') {
        $revisionTasks++;
    }

    if ($task['priority'] === 'high') {
        $highPriorityTasks++;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчетность - <?php echo htmlspecialchars($appConfig['app_name']); ?></title>
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
                <?php if (isManager($currentUser)): ?>
                    <a href="admin.php" class="btn btn-outline-light btn-sm">Админ</a>
                <?php endif; ?>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Отчетность по задачам</h1>
                <p class="text-muted mb-0">Сводная страница для просмотра показателей и выгрузки отчетов.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="export_tasks.php?format=excel<?php echo $filterQueryString !== '' ? '&' . htmlspecialchars($filterQueryString) : ''; ?>" class="btn btn-success">Скачать Excel</a>
                <a href="export_tasks.php?format=word<?php echo $filterQueryString !== '' ? '&' . htmlspecialchars($filterQueryString) : ''; ?>" class="btn btn-outline-dark">Скачать Word</a>
            </div>
        </div>

        <div class="card border-0 mb-4">
            <div class="card-body p-4">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Статус</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Все статусы</option>
                            <option value="created" <?php echo $filters['status'] === 'created' ? 'selected' : ''; ?>>Создана</option>
                            <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                            <option value="on_review" <?php echo $filters['status'] === 'on_review' ? 'selected' : ''; ?>>На проверке</option>
                            <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Утверждена</option>
                            <option value="needs_revision" <?php echo $filters['status'] === 'needs_revision' ? 'selected' : ''; ?>>На доработке</option>
                            <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Закрыта</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="project_id" class="form-label">Проект</label>
                        <select class="form-select" id="project_id" name="project_id">
                            <option value="">Все проекты</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo (int) $project['id']; ?>" <?php echo $filters['project_id'] === (string) $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="assigned_to" class="form-label">Исполнитель</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">Все исполнители</option>
                            <?php foreach ($executors as $executor): ?>
                                <option value="<?php echo (int) $executor['id']; ?>" <?php echo $filters['assigned_to'] === (string) $executor['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($executor['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="date_from" class="form-label">Дедлайн от</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Дедлайн до</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>

                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-dark">Применить фильтры</button>
                        <a href="reports.php" class="btn btn-outline-secondary">Сбросить</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Всего задач</div>
                        <div class="display-6"><?php echo $totalTasks; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Закрыто</div>
                        <div class="display-6"><?php echo $closedTasks; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-2">На проверке</div>
                        <div class="display-6"><?php echo $reviewTasks; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Высокий приоритет</div>
                        <div class="display-6"><?php echo $highPriorityTasks; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Сигналы для контроля</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-1">Задачи на доработке</div>
                            <div class="fs-4 text-danger"><?php echo $revisionTasks; ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-1">Открытые задачи</div>
                            <div class="fs-4 text-primary"><?php echo $totalTasks - $closedTasks; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Таблица для отчета</h2>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Задача</th>
                                <th>Проект</th>
                                <th>Статус</th>
                                <th>Приоритет</th>
                                <th>Исполнитель</th>
                                <th>Проверяющий</th>
                                <th>Дедлайн</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($tasks === []): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">По выбранным фильтрам задач не найдено.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo (int) $task['id']; ?></td>
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                <td><?php echo htmlspecialchars(getStatusLabel($task['status'])); ?></td>
                                <td><?php echo htmlspecialchars(getPriorityLabel($task['priority'])); ?></td>
                                <td><?php echo htmlspecialchars($task['executor_name'] ?? 'не назначен'); ?></td>
                                <td><?php echo htmlspecialchars($task['reviewer_name'] ?? 'не назначен'); ?></td>
                                <td><?php echo htmlspecialchars($task['due_date'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/theme.js"></script>
</body>
</html>
