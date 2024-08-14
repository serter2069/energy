<?php
session_start();
session_destroy();

// Удаление куки user_id
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, "/"); // Устанавливаем куку с прошедшим временем
}

header("Location: login.php");
exit();
?>
