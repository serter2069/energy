<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Изменено с $_POST['token'] на $_POST['restore_token']
    $restore_token = $_POST['restore_token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters.";
    } elseif ($password != $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Проверка, существует ли токен в базе данных
        $sql = "SELECT id FROM users WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $restore_token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            // Обновление пароля и удаление токена
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, token = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                // Автоматическая авторизация пользователя
                $_SESSION['user_id'] = $user_id;
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        } else {
            $error_message = "Invalid token or the token has expired.";
        }
        $stmt->close();
        $conn->close();
    }
}

if (!isset($password_reset) && !isset($error_message)) {
    if (!isset($_GET['token'])) {
        header("Location: login.php");
        exit();
    }
    $restore_token = $_GET['token'];
    $sql = "SELECT id FROM users WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $restore_token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $error_message = "Invalid token.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: white;
        }
        .reset-password-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: auto;
            text-align: center;
        }
        .alert {
            margin-top: 20px;
        }
        .form-control {
            width: 100%;
        }
        .reset-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-group {
            width: 100%;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="reset-wrapper">
        <div class="reset-password-container">
            <h1 class="text-center">Reset Password</h1>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
            <?php elseif (isset($password_reset)): ?>
                <div class="alert alert-success text-center">
                    <h4 class="alert-heading">Password Reset Successfully!</h4>
                    <p>Your password has been reset. You are now logged in and will be redirected shortly.</p>
                </div>
            <?php else: ?>
                <form action="reset_password.php" method="POST" class="mt-4 w-100" id="resetPasswordForm">
                    <!-- Изменено с $token на $restore_token -->
                    <input type="hidden" name="restore_token" value="<?php echo htmlspecialchars($restore_token); ?>">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="8">
                        <small id="passwordHelp" class="form-text text-muted">Password must be at least 8 characters long.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                        <small id="confirmPasswordHelp" class="form-text text-muted">Please re-enter your password.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" id="resetPasswordButton">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const resetButton = document.getElementById('resetPasswordButton');
        const passwordHelp = document.getElementById('passwordHelp');
        const confirmPasswordHelp = document.getElementById('confirmPasswordHelp');

        function validatePasswords() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            let isValid = true;

            // Проверка длины пароля
            if (password.length < 8) {
                passwordHelp.textContent = 'Password must be at least 8 characters long.';
                passwordHelp.classList.add('text-danger');
                isValid = false;
            } else {
                passwordHelp.textContent = 'Password is valid.';
                passwordHelp.classList.remove('text-danger');
                passwordHelp.classList.add('text-success');
            }

            // Проверка совпадения паролей
            if (password !== confirmPassword) {
                confirmPasswordHelp.textContent = 'Passwords do not match.';
                confirmPasswordHelp.classList.add('text-danger');
                isValid = false;
            } else if (confirmPassword.length > 0) {
                confirmPasswordHelp.textContent = 'Passwords match.';
                confirmPasswordHelp.classList.remove('text-danger');
                confirmPasswordHelp.classList.add('text-success');
            } else {
                confirmPasswordHelp.textContent = 'Please re-enter your password.';
                confirmPasswordHelp.classList.remove('text-danger', 'text-success');
            }

            // Активация/деактивация кнопки
            resetButton.disabled = !isValid;
        }

        passwordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);

        // Начальная валидация
        validatePasswords();
    });
    </script>
</body>
</html>