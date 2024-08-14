<?php
session_start();
require_once 'check_auth.php';
require_once 'db_connection.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
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
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $update_pro_stmt->error]);
}

$update_pro_stmt->close();
$conn->close();