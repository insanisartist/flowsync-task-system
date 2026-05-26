<?php
/**
 * Страница входа в систему.
 *
 * Что делает:
 * - показывает форму входа;
 * - проверяет email и пароль;
 * - после успешного входа отправляет пользователя в личный кабинет.
 *
 * Как открыть:
 * - http://localhost/diplom/htdocs/login.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

requireGuest();

$errorMessage = null;
$successMessage = getFlashMessage('success');
$errorFlash = getFlashMessage('error');

if ($errorFlash !== null) {
    $errorMessage = $errorFlash;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMessage = 'Введите email и пароль.';
    } elseif (attemptLogin($pdo, $email, $password)) {
        setFlashMessage('success', 'Вы успешно вошли в систему.');
        header('Location: dashboard.php');
        exit;
    } else {
        $errorMessage = 'Неверный email или пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - <?php echo htmlspecialchars($appConfig['app_name']); ?></title>
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('flowsync-theme') || 'light');
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body class="app-body">
    <div class="container py-5 app-login-shell">
        <div class="app-login-topbar">
            <button type="button" class="btn btn-outline-secondary theme-toggle" data-theme-toggle>
                <span data-theme-label class="theme-toggle-label">Светлая тема</span>
            </button>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-3 text-center">Вход в систему</h1>
                        <p class="text-muted text-center mb-4">Введите учетные данные сотрудника для входа в систему управления задачами.</p>

                        <?php if ($successMessage !== null): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                        <?php endif; ?>

                        <?php if ($errorMessage !== null): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    placeholder="manager@example.com"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="123456"
                                >
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Войти</button>
                        </form>

                        <hr class="my-4">

                        <div class="small text-muted">
                            <div>Тестовый менеджер: `manager@example.com` / `123456`</div>
                            <div>Тестовый исполнитель: `executor@example.com` / `123456`</div>
                            <div>Тестовый проверяющий: `reviewer@example.com` / `123456`</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/theme.js"></script>
</body>
</html>
