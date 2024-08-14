<?php
session_start();
include 'db_connection.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error_messages = [];
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $error_messages[] = "Email is required.";
    } else {
        $sql_check = "SELECT * FROM users WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $user = $result_check->fetch_assoc();
            if ($user && $user['email_activation_status'] == 'not_activated') {
                $token = bin2hex(random_bytes(16));
                $sql_update = "UPDATE users SET token = ? WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ss", $token, $email);
                if ($stmt_update->execute()) {
                    if (sendActivationEmail($email, $token)) {
                        $success_message = "Activation email has been sent again.";
                    } else {
                        $error_messages[] = "Failed to send activation email.";
                    }
                } else {
                    $error_messages[] = "Error updating token: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $error_messages[] = "This email is already registered. <a href='login.php'>Log in</a>";
            }
        } else {
            $token = bin2hex(random_bytes(16));

            $sql_insert = "INSERT INTO users (email, token, email_activation_status) VALUES (?, ?, 'not_activated')";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ss", $email, $token);
            if ($stmt_insert->execute()) {
                if (sendActivationEmail($email, $token)) {
                    $success_message = "Registration successful. Please check your email to activate your account.";
                    
                    // Получаем ID нового пользователя
                    $new_user_id = $stmt_insert->insert_id;
                    
                    // Вызов скрипта create_missing_reports.php
                    $create_reports_url = "http://" . $_SERVER['HTTP_HOST'] . "/create_missing_reports.php?user_id=" . $new_user_id;
                    $response = file_get_contents($create_reports_url);
                    error_log("Create reports response: " . $response);  // Логирование ответа
                } else {
                    $error_messages[] = "Failed to send activation email.";
                }
            } else {
                $error_messages[] = "Error: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

function sendActivationEmail($email, $token) {
    global $mail_host, $mail_port, $mail_username, $mail_password, $mail_from;

    $activation_link = "https://energydiary.terekhovsergei.life/activate.php?token=$token";
    $subject = "Activate Your Energy Diary Account";
    $message = "
        <html>
        <head>
            <title>Activate Your Energy Diary Account</title>
        </head>
        <body>
            <h2>Welcome to Energy Diary!</h2>
            <p>Thank you for registering. To activate your account, please click the link below:</p>
            <p><a href='$activation_link'>$activation_link</a></p>
            <p>If the link doesn't work, copy and paste it into your browser's address bar.</p>
            <p>If you didn't register for an Energy Diary account, please ignore this email.</p>
        </body>
        </html>
    ";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $mail_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $mail_username;
        $mail->Password   = $mail_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mail_port;

        $mail->setFrom($mail_from, 'Energy Diary');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Energy Diary</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
 
    <style>
       
        .register-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin: auto;
        }
        .register-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="register-wrapper">
        <div class="register-container">
            <h1 class="text-center mb-4">Register</h1>
            <?php if (!empty($error_messages)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($error_messages as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success text-center">
                    <p><?php echo $success_message; ?></p>
                    <p>Please check your email for the activation link.</p>
                </div>
            <?php else: ?>
                <form action="register.php" method="post">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">Already have an account? Log in</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>