
<?php
// set_password.php
session_start();
include 'db_connection.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $sql = "UPDATE users SET password = ? WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bind_param('ss', $hashed_password, $token);

        if ($stmt->execute()) {
            $success = "Password updated successfully.";
            $sql = "UPDATE users SET token = NULL WHERE token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $token);
            $stmt->execute();

            // Перенаправление на explore.php после успешного обновления пароля
            header("Location: explore.php");
            exit();
        } else {
            $error = "Error updating password: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #f4f4f9 0%, #d4d4f7 100%);
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .header {
            background: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        .header ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }
        .header ul li {
            margin: 0 15px;
        }
        .header ul li a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }
        .header ul li a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 800px;
            margin: 120px auto 20px;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .submit-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background: #007bff;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-button:hover {
            background: #0056b3;
        }
    </style>
</head>

    
    <body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h1>Set New Password</h1>
        <?php if (!empty($error)): ?>
            <p style="color: red; text-align: center;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: green; text-align: center;"><?php echo $success; ?></p>
        <?php else: ?>
            <form action="set_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="submit-button">Set Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
