<?php
session_start();
include 'db_connection.php';

// Проверка наличия куки user_id
if (isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = intval($_COOKIE['user_id']);
    header("Location: explore.php");
    exit();
}

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $_SESSION['user_id'] = $user_id;
    setcookie('user_id', $user_id, time() + (30 * 24 * 60 * 60), "/");
    header("Location: explore.php");
    exit();
}

// Получение списка пользователей
$query = "SELECT id, email FROM users";
$result = $conn->query($query);
$users = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test User Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f0f0f0; }
        .container { max-width: 400px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        select, button { width: 100%; padding: 10px; margin-top: 10px; }
        button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Select User to Login (Test Only)</h2>
        <form method="post">
            <select name="user_id" id="user_id">
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>">
                        <?php echo htmlspecialchars($user['id'] . ' - ' . $user['email']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Login as Selected User</button>
        </form>
    </div>
</body>
</html>