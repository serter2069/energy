<?php
session_start();
include 'db_connection.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT email_activation_status FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['email_activation_status'] === 'activated') {
        header("Location: dashboard.php");
        exit();
    }
}

$error_messages = [];
$success_message = "";

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];

    if (strlen($password) < 8) {
        $error_messages[] = "Password must be at least 8 characters long.";
    } else {
        $sql = "SELECT * FROM users WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if ($user['email_activation_status'] === 'activated') {
                $success_message = "Your account has already been activated.";
                header("Location: dashboard.php");
                exit();
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ?, token = NULL, email_activation_status = 'activated' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $user['id']);
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $user['id'];
                    $success_message = "Your account has been activated successfully.";
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_messages[] = "Failed to activate account. Please try again.";
                }
            }
        } else {
            error_log("Invalid token: " . $token);
            error_log("SQL query: " . $sql);
            error_log("SQL error: " . $stmt->error);
            $error_messages[] = "Invalid or expired activation token.";
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Account - Energy Diary</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .password-status { font-size: 0.9em; margin-top: 5px; color: #dc3545; }
    </style>
</head>

<body><?php include 'header.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Activate Account</h2>
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
                                <a href="dashboard.php" class="btn btn-primary mt-3">Go to Dashboard</a>
                            </div>
                        <?php else: ?>
                            <form action="activate.php?token=<?php echo urlencode($token); ?>" method="post" id="activationForm">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                <div class="form-group">
                                    <label for="password">Set Your Password:</label>
                                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                                    <div class="password-status" id="passwordStatus"></div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block" id="activateButton">Activate Account</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            function validateForm() {
                var password = $('#password').val();

                if (password.length >= 8) {
                    $('#activateButton').prop('disabled', false);
                    $('#passwordStatus').html('');
                } else {
                    $('#activateButton').prop('disabled', true);
                    $('#passwordStatus').html('Password must be at least 8 characters long.');
                }
            }

            $('#password').on('input', validateForm);

            $('#activationForm').on('submit', function(e) {
                var password = $('#password').val();
                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long.');
                }
            });
        });
    </script>
</body>
</html>