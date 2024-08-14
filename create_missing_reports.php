<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Начало логирования
file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Скрипт запущен\n", FILE_APPEND);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'db_connection.php';

function createReport($conn, $user_id, $date) {
    $sql = "INSERT INTO energy_reports (user_id, report_date, physical_energy, mental_energy, emotional_energy, why_not_energy)
            VALUES (?, ?, 100, 100, 100, 100)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date);
    $result = $stmt->execute();
    $stmt->close();
    
    file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Отчет создан для пользователя $user_id на дату $date. Результат: " . ($result ? 'Успешно' : 'Ошибка') . "\n", FILE_APPEND);
    
    return $result;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Неверный ID пользователя: $user_id\n", FILE_APPEND);
    die("Неверный ID пользователя");
}

// Получаем часовой пояс пользователя
$timezone_sql = "SELECT timezone FROM users WHERE id = ?";
$timezone_stmt = $conn->prepare($timezone_sql);
$timezone_stmt->bind_param("i", $user_id);
$timezone_stmt->execute();
$timezone_result = $timezone_stmt->get_result();
$user_timezone = $timezone_result->fetch_assoc()['timezone'];
$timezone_stmt->close();

file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Часовой пояс пользователя: $user_timezone\n", FILE_APPEND);

// Устанавливаем часовой пояс
try {
    $user_timezone_string = str_replace('UTC', '', $user_timezone);
    if ($user_timezone_string[0] === '+' || $user_timezone_string[0] === '-') {
        $user_timezone = new DateTimeZone('Etc/GMT' . ($user_timezone_string[0] === '+' ? '-' : '+') . substr($user_timezone_string, 1));
    } else {
        $user_timezone = new DateTimeZone('UTC');
    }
} catch (Exception $e) {
    file_put_contents('timezone_error.log', date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", FILE_APPEND);
    $user_timezone = new DateTimeZone('UTC');
}

$utc_timezone = new DateTimeZone('UTC');

// Получаем текущую дату и вчерашнюю дату в часовом поясе пользователя
$now = new DateTime('now', $user_timezone);
$today = $now->format('Y-m-d');
$yesterday = $now->modify('-1 day')->format('Y-m-d');

file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Сегодня: $today, Вчера: $yesterday\n", FILE_APPEND);

// Проверяем наличие отчетов на сегодня и вчера
$sql = "SELECT report_date FROM energy_reports WHERE user_id = ? AND (report_date = ? OR report_date = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $today, $yesterday);
$stmt->execute();
$result = $stmt->get_result();

$existing_dates = [];
while ($row = $result->fetch_assoc()) {
    $existing_dates[] = $row['report_date'];
}
$stmt->close();

file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Существующие даты: " . implode(', ', $existing_dates) . "\n", FILE_APPEND);

// Создаем отчеты, если их нет
$reports_created = false;

if (!in_array($today, $existing_dates)) {
    if (createReport($conn, $user_id, $today)) {
        echo "Создан отчет на сегодня ({$today}) в часовом поясе пользователя<br>";
        $reports_created = true;
    }
}

if (!in_array($yesterday, $existing_dates)) {
    if (createReport($conn, $user_id, $yesterday)) {
        echo "Создан отчет на вчера ({$yesterday}) в часовом поясе пользователя<br>";
        $reports_created = true;
    }
}

if (!$reports_created) {
    echo "Отчеты на сегодня и вчера уже существуют в часовом поясе пользователя";
    file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Новые отчеты не созданы\n", FILE_APPEND);
}

$conn->close();

// Завершение логирования
file_put_contents('create_missing_reports.log', date('[Y-m-d H:i:s] ') . "Скрипт завершен\n", FILE_APPEND);
?>