<?php
session_start();
include 'db_connection.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, email, password, email_activation_status FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['email_activation_status'] == 'activated') {
                $_SESSION['user_id'] = $user['id'];
                
                // Установка куки для сохранения авторизации на 30 дней
                setcookie('user_id', $user['id'], time() + (30 * 24 * 60 * 60), "/", "", true, true);
                
                // Вызов скрипта create_missing_reports.php
                $create_reports_url = "http://" . $_SERVER['HTTP_HOST'] . "/create_missing_reports.php?user_id=" . $user['id'];
                $response = file_get_contents($create_reports_url);
                error_log("Create reports response: " . $response);  // Логирование ответа
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Please activate your account first. Check your email for the activation link.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Energy Diary</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <style>
        body {
            background-color: white;
        }
        .login-container {
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
        .login-wrapper {
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

    <div class="login-wrapper">
        <div class="login-container">
            <h1 class="text-center">Login</h1>

            <form action="login.php" method="POST" class="mt-4 w-100">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            <div class="text-center mt-3">
                <a href="register.php">Register</a> | <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center mt-3">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>