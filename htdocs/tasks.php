<?php
/**
 * Главная страница задач.
 *
 * Что делает:
 * - показывает таблицу всех задач;
 * - дает менеджеру форму создания и редактирования;
 * - позволяет менять статус по роли пользователя;
 * - показывает историю действий по выбранной задаче.
 *
 * Как открыть:
 * - http://localhost/diplom/htdocs/tasks.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tasks.php';

requireAuth();

$currentUser = getCurrentUser();
$successMessage = getFlashMessage('success');
$errorMessage = getFlashMessage('error');

$projects = getProjects($pdo);
$executors = getUsersByRole($pdo, 'executor');
$reviewers = getUsersByRole($pdo, 'reviewer');
$filters = getTaskFilters();

$formData = [
    'project_id' => '',
    'title' => '',
    'description' => '',
    'priority' => 'medium',
    'assigned_to' => '',
    'reviewer_id' => '',
    'due_date' => '',
];

$editingTask = null;
$selectedTask = null;

if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $editingTask = getTaskById($pdo, (int) $_GET['edit']);

    if ($editingTask !== null && isManager($currentUser)) {
        $formData = [
            'project_id' => (string) $editingTask['project_id'],
            'title' => $editingTask['title'],
            'description' => $editingTask['description'] ?? '',
            'priority' => $editingTask['priority'],
            'assigned_to' => (string) ($editingTask['assigned_to'] ?? ''),
            'reviewer_id' => (string) ($editingTask['reviewer_id'] ?? ''),
            'due_date' => $editingTask['due_date'] ?? '',
        ];
        $selectedTask = $editingTask;
    } else {
        $editingTask = null;
    }
}

if (isset($_GET['view']) && ctype_digit((string) $_GET['view'])) {
    $selectedTask = getTaskById($pdo, (int) $_GET['view']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_task') {
        if (!isManager($currentUser)) {
            setFlashMessage('error', 'Только менеджер может создавать и редактировать задачи.');
            header('Location: tasks.php');
            exit;
        }

        $taskId = isset($_POST['task_id']) && ctype_digit((string) $_POST['task_id']) ? (int) $_POST['task_id'] : 0;
        $formData = [
            'project_id' => trim($_POST['project_id'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'priority' => trim($_POST['priority'] ?? 'medium'),
            'assigned_to' => trim($_POST['assigned_to'] ?? ''),
            'reviewer_id' => trim($_POST['reviewer_id'] ?? ''),
            'due_date' => trim($_POST['due_date'] ?? ''),
        ];

        $errors = validateTaskForm($formData);

        if ($errors !== []) {
            $errorMessage = implode(' ', $errors);
            if ($taskId > 0) {
                $editingTask = getTaskById($pdo, $taskId);
                $selectedTask = $editingTask;
            }
        } else {
            if ($taskId > 0) {
                updateTask($pdo, $taskId, $formData, (int) $currentUser['id']);
                addTaskHistory($pdo, $taskId, (int) $currentUser['id'], 'Задача была отредактирована менеджером.');
                setFlashMessage('success', 'Задача успешно обновлена.');
                header('Location: tasks.php?view=' . $taskId);
                exit;
            }

            $newTaskId = createTask($pdo, $formData, (int) $currentUser['id']);
            addTaskHistory($pdo, $newTaskId, (int) $currentUser['id'], 'Задача была создана.');
            setFlashMessage('success', 'Задача успешно создана.');
            header('Location: tasks.php?view=' . $newTaskId);
            exit;
        }
    }

    if ($action === 'change_status') {
        $taskId = isset($_POST['task_id']) && ctype_digit((string) $_POST['task_id']) ? (int) $_POST['task_id'] : 0;
        $newStatus = trim($_POST['new_status'] ?? '');
        $commentText = trim($_POST['comment_text'] ?? '');

        $task = $taskId > 0 ? getTaskById($pdo, $taskId) : null;

        if ($task === null) {
            setFlashMessage('error', 'Задача не найдена.');
            header('Location: tasks.php');
            exit;
        }

        $availableOptions = getAvailableStatusOptions($task, $currentUser);

        if (!isset($availableOptions[$newStatus])) {
            setFlashMessage('error', 'У вас нет прав на это изменение статуса.');
            header('Location: tasks.php?view=' . $taskId);
            exit;
        }

        if ($newStatus === 'needs_revision' && $commentText === '') {
            setFlashMessage('error', 'При возврате на доработку нужно добавить комментарий.');
            header('Location: tasks.php?view=' . $taskId);
            exit;
        }

        updateTaskStatus($pdo, $taskId, $newStatus, (int) $currentUser['id']);
        addTaskHistory(
            $pdo,
            $taskId,
            (int) $currentUser['id'],
            'Статус изменен на "' . getStatusLabel($newStatus) . '".',
            $commentText
        );

        setFlashMessage('success', 'Статус задачи обновлен.');
        header('Location: tasks.php?view=' . $taskId);
        exit;
    }
}

$tasks = getTasks($pdo, $filters);

if ($selectedTask !== null) {
    $selectedTask = getTaskById($pdo, (int) $selectedTask['id']);
}

$historyItems = $selectedTask !== null ? getTaskHistory($pdo, (int) $selectedTask['id']) : [];
$filterQueryString = getTaskFilterQueryString($filters);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задачи - <?php echo htmlspecialchars($appConfig['app_name']); ?></title>
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
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Кабинет</a>
                <a href="reports.php" class="btn btn-outline-light btn-sm">Отчетность</a>
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
        <?php if ($successMessage !== null): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h1 class="h3 mb-0">Список задач</h1>
                            <div class="d-flex gap-2">
                                <a href="export_tasks.php?format=excel<?php echo $filterQueryString !== '' ? '&' . htmlspecialchars($filterQueryString) : ''; ?>" class="btn btn-success">Скачать Excel</a>
                                <a href="export_tasks.php?format=word<?php echo $filterQueryString !== '' ? '&' . htmlspecialchars($filterQueryString) : ''; ?>" class="btn btn-outline-dark">Скачать Word</a>
                                <?php if (isManager($currentUser)): ?>
                                    <a href="tasks.php" class="btn btn-primary">Новая задача</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="get" class="row g-3 mb-4">
                            <div class="col-md-6">
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

                            <div class="col-md-6">
                                <label for="project_id_filter" class="form-label">Проект</label>
                                <select class="form-select" id="project_id_filter" name="project_id">
                                    <option value="">Все проекты</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo (int) $project['id']; ?>" <?php echo $filters['project_id'] === (string) $project['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="assigned_to_filter" class="form-label">Исполнитель</label>
                                <select class="form-select" id="assigned_to_filter" name="assigned_to">
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

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-dark">Применить фильтры</button>
                                <a href="tasks.php" class="btn btn-outline-secondary">Сбросить</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Задача</th>
                                        <th>Проект</th>
                                        <th>Статус</th>
                                        <th>Приоритет</th>
                                        <th>Исполнитель</th>
                                        <th>Дедлайн</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($tasks === []): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Пока задач нет.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo (int) $task['id']; ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($task['title']); ?></div>
                                            <div class="small text-muted">Проверяющий: <?php echo htmlspecialchars($task['reviewer_name'] ?? 'не назначен'); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                        <td><span class="badge text-bg-<?php echo htmlspecialchars(getStatusBadgeClass($task['status'])); ?>"><?php echo htmlspecialchars(getStatusLabel($task['status'])); ?></span></td>
                                        <td><span class="badge text-bg-<?php echo htmlspecialchars(getPriorityBadgeClass($task['priority'])); ?>"><?php echo htmlspecialchars(getPriorityLabel($task['priority'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($task['executor_name'] ?? 'не назначен'); ?></td>
                                        <td><?php echo htmlspecialchars($task['due_date'] ?? '-'); ?></td>
                                        <td class="text-end">
                                            <a href="tasks.php?view=<?php echo (int) $task['id']; ?>" class="btn btn-outline-secondary btn-sm">Открыть</a>
                                            <?php if (isManager($currentUser)): ?>
                                                <a href="tasks.php?edit=<?php echo (int) $task['id']; ?>" class="btn btn-outline-primary btn-sm">Изменить</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <?php if (isManager($currentUser)): ?>
                    <div class="card border-0 mb-4">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3"><?php echo $editingTask !== null ? 'Редактирование задачи' : 'Создание задачи'; ?></h2>

                            <form method="post">
                                <input type="hidden" name="action" value="save_task">
                                <input type="hidden" name="task_id" value="<?php echo (int) ($editingTask['id'] ?? 0); ?>">

                                <div class="mb-3">
                                    <label for="project_id" class="form-label">Проект</label>
                                    <select class="form-select" id="project_id" name="project_id">
                                        <option value="">Выберите проект</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo (int) $project['id']; ?>" <?php echo $formData['project_id'] === (string) $project['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($project['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="title" class="form-label">Название задачи</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="priority" class="form-label">Приоритет</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low" <?php echo $formData['priority'] === 'low' ? 'selected' : ''; ?>>Низкий</option>
                                        <option value="medium" <?php echo $formData['priority'] === 'medium' ? 'selected' : ''; ?>>Средний</option>
                                        <option value="high" <?php echo $formData['priority'] === 'high' ? 'selected' : ''; ?>>Высокий</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="assigned_to" class="form-label">Исполнитель</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Не назначен</option>
                                        <?php foreach ($executors as $executor): ?>
                                            <option value="<?php echo (int) $executor['id']; ?>" <?php echo $formData['assigned_to'] === (string) $executor['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($executor['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="reviewer_id" class="form-label">Проверяющий</label>
                                    <select class="form-select" id="reviewer_id" name="reviewer_id">
                                        <option value="">Не назначен</option>
                                        <?php foreach ($reviewers as $reviewer): ?>
                                            <option value="<?php echo (int) $reviewer['id']; ?>" <?php echo $formData['reviewer_id'] === (string) $reviewer['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($reviewer['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Дедлайн</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo htmlspecialchars($formData['due_date']); ?>">
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary"><?php echo $editingTask !== null ? 'Сохранить изменения' : 'Создать задачу'; ?></button>
                                    <?php if ($editingTask !== null): ?>
                                        <a href="tasks.php" class="btn btn-outline-secondary">Отменить редактирование</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Карточка задачи</h2>

                        <?php if ($selectedTask === null): ?>
                            <p class="text-muted mb-0">Выберите задачу в таблице, чтобы посмотреть детали и историю.</p>
                        <?php else: ?>
                            <h3 class="h6"><?php echo htmlspecialchars($selectedTask['title']); ?></h3>
                            <p class="small text-muted mb-3"><?php echo nl2br(htmlspecialchars($selectedTask['description'] ?? 'Описание не заполнено.')); ?></p>

                            <p class="mb-2"><strong>Проект:</strong> <?php echo htmlspecialchars($selectedTask['project_name']); ?></p>
                            <p class="mb-2"><strong>Статус:</strong> <?php echo htmlspecialchars(getStatusLabel($selectedTask['status'])); ?></p>
                            <p class="mb-2"><strong>Приоритет:</strong> <?php echo htmlspecialchars(getPriorityLabel($selectedTask['priority'])); ?></p>
                            <p class="mb-2"><strong>Исполнитель:</strong> <?php echo htmlspecialchars($selectedTask['executor_name'] ?? 'не назначен'); ?></p>
                            <p class="mb-2"><strong>Проверяющий:</strong> <?php echo htmlspecialchars($selectedTask['reviewer_name'] ?? 'не назначен'); ?></p>
                            <p class="mb-3"><strong>Дедлайн:</strong> <?php echo htmlspecialchars($selectedTask['due_date'] ?? 'не указан'); ?></p>

                            <?php $statusOptions = getAvailableStatusOptions($selectedTask, $currentUser); ?>
                            <?php if ($statusOptions !== []): ?>
                                <form method="post" class="border rounded p-3 mb-4 app-muted-surface">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="task_id" value="<?php echo (int) $selectedTask['id']; ?>">

                                    <div class="mb-3">
                                        <label for="new_status" class="form-label">Изменить статус</label>
                                        <select class="form-select" id="new_status" name="new_status">
                                            <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                                                <option value="<?php echo htmlspecialchars($statusValue); ?>"><?php echo htmlspecialchars($statusLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="comment_text" class="form-label">Комментарий</label>
                                        <textarea class="form-control" id="comment_text" name="comment_text" rows="3" placeholder="Например: что проверено или что нужно исправить"></textarea>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <?php if (isset($statusOptions['on_review'])): ?>
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                onclick="window.alert('Это демонстрационная кнопка. Полноценная автоматическая загрузка продукта на GitHub в текущей версии не реализована.');"
                                            >
                                                Загрузить продукт на GitHub
                                            </button>
                                        <?php endif; ?>

                                        <button type="submit" class="btn btn-success w-100">Сохранить статус</button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <h3 class="h6 mb-3">История действий</h3>

                            <?php if ($historyItems === []): ?>
                                <p class="text-muted mb-0">История пока пуста.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($historyItems as $historyItem): ?>
                                        <div class="border rounded p-3">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($historyItem['action_text']); ?></div>
                                            <div class="small text-muted mb-2">
                                                <?php echo htmlspecialchars($historyItem['full_name']); ?>,
                                                <?php echo htmlspecialchars($historyItem['created_at']); ?>
                                            </div>
                                            <?php if (!empty($historyItem['comment_text'])): ?>
                                                <div class="small"><?php echo nl2br(htmlspecialchars($historyItem['comment_text'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/theme.js"></script>
</body>
</html>
