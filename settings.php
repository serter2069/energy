<?php
session_start();
require_once 'check_auth.php';
require_once 'db_connection.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Функция для получения списка основных часовых поясов
function getMainTimezones() {
    return [
        'UTC-12:00' => ['Baker Island'],
        'UTC-11:00' => ['Niue', 'American Samoa'],
        'UTC-10:00' => ['Hawaii', 'Cook Islands'],
        'UTC-09:00' => ['Alaska'],
        'UTC-08:00' => ['Los Angeles', 'Vancouver'],
        'UTC-07:00' => ['Denver', 'Phoenix'],
        'UTC-06:00' => ['Chicago', 'Mexico City'],
        'UTC-05:00' => ['New York', 'Toronto'],
        'UTC-04:00' => ['Santiago', 'Halifax'],
        'UTC-03:00' => ['São Paulo', 'Buenos Aires'],
        'UTC-02:00' => ['Fernando de Noronha'],
        'UTC-01:00' => ['Azores', 'Cape Verde'],
        'UTC+00:00' => ['London', 'Lisbon'],
        'UTC+01:00' => ['Berlin', 'Paris'],
        'UTC+02:00' => ['Athens', 'Cairo'],
        'UTC+03:00' => ['Moscow', 'Istanbul'],
        'UTC+04:00' => ['Dubai', 'Baku'],
        'UTC+05:00' => ['Karachi', 'Tashkent'],
        'UTC+05:30' => ['Mumbai', 'New Delhi'],
        'UTC+06:00' => ['Dhaka', 'Almaty'],
        'UTC+07:00' => ['Bangkok', 'Jakarta'],
        'UTC+08:00' => ['Singapore', 'Beijing'],
        'UTC+09:00' => ['Tokyo', 'Seoul'],
        'UTC+10:00' => ['Sydney', 'Melbourne'],
        'UTC+11:00' => ['Solomon Islands'],
        'UTC+12:00' => ['Auckland', 'Fiji'],
    ];
}

// Функция для обработки AJAX-запросов на обновление Pro Mode настроек
function handleAjaxRequest($conn, $user_id) {
    $pro_mode = isset($_POST['pro_mode']) ? 1 : 0;
    $pro_focus_of_day = isset($_POST['pro_focus_of_day']) ? 1 : 0;
    $pro_thought_of_day = isset($_POST['pro_thought_of_day']) ? 1 : 0;
    $pro_what_im_afraid_of = isset($_POST['pro_what_im_afraid_of']) ? 1 : 0;
    $pro_what_to_take_from_surroundings = isset($_POST['pro_what_to_take_from_surroundings']) ? 1 : 0;
    $pro_world_picture = isset($_POST['pro_world_picture']) ? 1 : 0;
    $pro_happiness = isset($_POST['pro_happiness']) ? 1 : 0;

    $update_pro_sql = "UPDATE users SET 
                       pro_mode = ?, 
                       pro_focus_of_day = ?, 
                       pro_thought_of_day = ?, 
                       pro_what_im_afraid_of = ?,
                       pro_what_to_take_from_surroundings = ?,
                       pro_world_picture = ?,
                       pro_happiness = ?
                       WHERE id = ?";
    $update_pro_stmt = $conn->prepare($update_pro_sql);
    $update_pro_stmt->bind_param("iiiiiiii", $pro_mode, $pro_focus_of_day, $pro_thought_of_day, $pro_what_im_afraid_of,
                                 $pro_what_to_take_from_surroundings, $pro_world_picture, $pro_happiness, $user_id);

    if ($update_pro_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save settings']);
    }
    $update_pro_stmt->close();
    exit;
}

// Обработка AJAX-запросов
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_update_pro_settings'])) {
    handleAjaxRequest($conn, $user_id);
}

// Получение информации профиля
$sql = "SELECT email, timezone, pro_mode, pro_focus_of_day, pro_thought_of_day, pro_what_im_afraid_of, 
               pro_what_to_take_from_surroundings, pro_world_picture, pro_happiness 
        FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Обработка формы изменения пароля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        // Проверка текущего пароля
        $check_pwd_sql = "SELECT password FROM users WHERE id = ?";
        $check_pwd_stmt = $conn->prepare($check_pwd_sql);
        $check_pwd_stmt->bind_param("i", $user_id);
        $check_pwd_stmt->execute();
        $pwd_result = $check_pwd_stmt->get_result()->fetch_assoc();

        if (password_verify($current_password, $pwd_result['password'])) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pwd_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_pwd_stmt = $conn->prepare($update_pwd_sql);
            $update_pwd_stmt->bind_param("si", $hashed_new_password, $user_id);

            if ($update_pwd_stmt->execute()) {
                $success = "Password changed successfully.";
            } else {
                $error = "Failed to change password. Please try again.";
            }
            $update_pwd_stmt->close();
        } else {
            $error = "Current password is incorrect.";
        }
        $check_pwd_stmt->close();
    }
}

// Обработка формы изменения часового пояса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_timezone'])) {
    $new_timezone = $_POST['timezone'];
    $update_timezone_sql = "UPDATE users SET timezone = ? WHERE id = ?";
    $update_timezone_stmt = $conn->prepare($update_timezone_sql);
    $update_timezone_stmt->bind_param("si", $new_timezone, $user_id);
    if ($update_timezone_stmt->execute()) {
        $success = "Timezone updated successfully.";
        $user['timezone'] = $new_timezone;
    } else {
        $error = "Failed to update timezone. Please try again.";
    }
    $update_timezone_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Energy Diary</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <style>
        .content-wrapper {
            min-height: calc(100vh - 60px);
            padding-bottom: 80px;
        }
        .select2-container {
            width: 100% !important;
        }
        /* Обновленные стили для Pro Mode тумблера */
        .pro-mode-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .pro-mode-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        /* Стили для больших чекбоксов */
        .custom-checkbox .form-check-input {
            width: 1.5em;
            height: 1.5em;
            margin-top: 0.25em;
        }
        .custom-checkbox .form-check-label {
            padding-left: 0.5em;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="content-wrapper">
        <div class="container mt-5">
            <h1 class="mb-4">Settings</h1>
            
            <div id="alertContainer"></div>
            
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Profile Information</h2>
                            <div class="mb-3">
                                <label class="form-label">Email:</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Timezone Settings</h2>
                            <form action="settings.php" method="post">
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Select Your Timezone:</label>
                                    <select name="timezone" id="timezone" class="form-select">
                                        <?php
                                        $mainTimezones = getMainTimezones();
                                        foreach ($mainTimezones as $offset => $cities) {
                                            $cityList = implode(', ', $cities);
                                            $selected = ($offset == $user['timezone']) ? 'selected' : '';
                                            echo "<option value=\"$offset\" $selected>$offset - $cityList</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" name="change_timezone" class="btn btn-primary">Update Timezone</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Change Password</h2>
                            <form action="settings.php" method="post" id="passwordForm">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password:</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password:</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password:</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Pro Mode Settings</h2>
                            <form id="proSettingsForm">
                                <div class="mb-4">
                                    <label class="pro-mode-switch">
                                        <input type="checkbox" id="pro_mode" name="pro_mode" <?php echo $user['pro_mode'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <label for="pro_mode" class="form-check-label ms-2">Enable Pro Mode</label>
                                </div>
                                <div id="pro_settings" <?php echo $user['pro_mode'] ? '' : 'style="display:none;"'; ?>>
                                    <div class="mb-3 form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" id="pro_focus_of_day" name="pro_focus_of_day" <?php echo $user['pro_focus_of_day'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pro_focus_of_day">Focus of the Day</label>
                                    </div>
                                    <div class="mb-3 form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" id="pro_thought_of_day" name="pro_thought_of_day" <?php echo $user['pro_thought_of_day'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pro_thought_of_day">Thought of the Day</label>
                                    </div>
                                    <div class="mb-3 form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" id="pro_what_im_afraid_of" name="pro_what_im_afraid_of" <?php echo $user['pro_what_im_afraid_of'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pro_what_im_afraid_of">What I'm Afraid Of</label>
                                    </div>
                                    <div class="mb-3 form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" id="pro_what_to_take_from_surroundings" name="pro_what_to_take_from_surroundings" <?php echo $user['pro_what_to_take_from_surroundings'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pro_what_to_take_from_surroundings">What to Take from Surroundings</label>
                                    </div>
                                    <div class="mb-3 form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" id="pro_world_picture" name="pro_world_picture" <?php echo $user['pro_world_picture'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pro_world_picture">World Picture</label>
                                    </div>
                                    <div class="mb-3 form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" id="pro_happiness" name="pro_happiness" <?php echo $user['pro_happiness'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pro_happiness">Happiness</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#timezone').select2({
                placeholder: "Search for a timezone...",
                allowClear: true,
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }

                    if (typeof data.text === 'undefined') {
                        return null;
                    }

                    var searchTerm = params.term.toLowerCase();
                    var dataText = data.text.toLowerCase();

                    if (dataText.indexOf(searchTerm) > -1) {
                        return data;
                    }

                    return null;
                }
            });

            $('#passwordForm').on('submit', function(e) {
                var newPassword = $('#new_password').val();
                var confirmPassword = $('#confirm_password').val();
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    showAlert('New passwords do not match.', 'danger');
                }
            });

            $('#pro_mode, #pro_settings input[type="checkbox"]').change(function() {
                if ($(this).attr('id') === 'pro_mode') {
                    if ($(this).is(':checked')) {
                        $('#pro_settings').slideDown(300);
                    } else {
                        $('#pro_settings').slideUp(300);
                    }
                }
                saveProSettings();
            });

            function saveProSettings() {
                $.ajax({
                    url: 'settings.php',
                    method: 'POST',
                    data: $('#proSettingsForm').serialize() + '&ajax_update_pro_settings=1',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert('Pro settings saved successfully', 'success');
                        } else {
                            showAlert('Error saving pro settings: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('Error saving pro settings', 'danger');
                    }
                });
            }

            function showAlert(message, type) {
                var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                                message +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                '</div>';
                $('#alertContainer').html(alertHtml);
                
                // Автоматически скрыть уведомление через 5 секунд
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 5000);
            }

            <?php if (!empty($error)): ?>
                showAlert('<?php echo addslashes($error); ?>', 'danger');
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                showAlert('<?php echo addslashes($success); ?>', 'success');
            <?php endif; ?>
        });
    </script>
</body>
</html>