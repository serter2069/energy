<?php
session_start();
require_once 'check_auth.php';
include 'db_connection.php';
require 'vendor/autoload.php';

// Проверка авторизации
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = '';
$messageSent = false;
$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Валидация email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email address.";
    } else {
        // Проверка существования email в базе данных
        $sql_check = "SELECT * FROM users WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $user = $result_check->fetch_assoc();
            if ($user['email_activation_status'] == 'not_activated') {
                // Генерация нового токена активации
                $token = bin2hex(random_bytes(16));
                $sql_update = "UPDATE users SET token = ? WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ss", $token, $email);
                if ($stmt_update->execute()) {
                    // Отправка активационного email с использованием PHPMailer
                    $activation_link = "https://energydiary.terekhovsergei.life/activate.php?token=$token";
                    $subject = "Activate Your Account";
                    $message = "Click the following link to activate your account: <a href='$activation_link'>$activation_link</a>";

                    $mail = new PHPMailer(true);
                    try {
                        // Настройки сервера
                        $mail->isSMTP();
                        $mail->Host = $mail_host;
                        $mail->SMTPAuth = true;
                        $mail->Username = $mail_username;
                        $mail->Password = $mail_password;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = $mail_port;

                        // Получатели
                        $mail->setFrom($mail_from, 'Energy Diary');
                        $mail->addAddress($email);

                        // Контент письма
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body    = $message;

                        $mail->send();
                        $success_message = "Activation email has been sent again.";
                        $messageSent = true;
                    } catch (Exception $e) {
                        $error_message = "Failed to send activation email. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error_message = "Error updating activation token: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                // Генерация токена сброса пароля
                $token = bin2hex(random_bytes(16));
                $sql = "UPDATE users SET token = ? WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $token, $email);

                if ($stmt->execute()) {
                    $reset_link = "https://energydiary.terekhovsergei.life/reset_password.php?token=$token";
                    $subject = "Password Reset";
                    $message = "
                        <html>
                        <head>
                            <title>Password Reset</title>
                        </head>
                        <body>
                            <p>Click this link to reset your password: <a href='$reset_link'>$reset_link</a></p>
                            <p>If you didn't request a password reset, please ignore this email.</p>
                        </body>
                        </html>";

                    $mail = new PHPMailer(true);
                    try {
                        // Настройки сервера
                        $mail->isSMTP();
                        $mail->Host = $mail_host;
                        $mail->SMTPAuth = true;
                        $mail->Username = $mail_username;
                        $mail->Password = $mail_password;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = $mail_port;

                        // Получатели
                        $mail->setFrom($mail_from, 'Energy Diary');
                        $mail->addAddress($email);

                        // Контент письма
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body    = $message;

                        if ($mail->send()) {
                            $success_message = "Password reset email has been sent.";
                            $messageSent = true;
                        } else {
                            $error_message = "Failed to send reset email.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Failed to send reset email. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error_message = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error_message = "No account found with this email.";
        }

        $stmt_check->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <style>
        body {
            background-color: white;
        }
        .forgot-password-container {
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
        .forgot-password-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-group {
            width: 100%;
        }
        .btn-link {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 10px;
            background-color: transparent;
            border: none;
            color: #007bff;
            text-decoration: underline;
        }
        .btn-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="forgot-password-wrapper">
        <div class="forgot-password-container">
            <h1 class="text-center">Forgot Password</h1>

            <?php if (!$messageSent): ?>
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form action="forgot_password.php" method="POST" class="mt-4 w-100">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </form>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn-link">Back to Login</a>
                </div>
            <?php else: ?>
                <div class="alert alert-success text-center mt-3">
                    <h4 class="alert-heading">Email Sent!</h4>
                    <p><?php echo $success_message; ?></p>
                    <hr>
                    <p class="mb-0"><a href="login.php" class="btn-link">Back to Login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>