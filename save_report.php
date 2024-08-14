<?php
session_start();
require_once 'check_auth.php';
require_once 'db_connection.php';

require_login();

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $report_date = $_POST['report_date'] ?? date('Y-m-d');

    // Validate and sanitize inputs
    $physical_energy = filter_var($_POST['physical_energy'] ?? 100, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 100]]);
    $mental_energy = filter_var($_POST['mental_energy'] ?? 100, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 100]]);
    $emotional_energy = filter_var($_POST['emotional_energy'] ?? 100, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 100]]);
    $why_not_energy = filter_var($_POST['why_not_energy'] ?? 100, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 100]]);

    $physical_comment = trim($_POST['physical_comment'] ?? '');
    $mental_comment = trim($_POST['mental_comment'] ?? '');
    $emotional_comment = trim($_POST['emotional_comment'] ?? '');
    $why_not_comment = trim($_POST['why_not_comment'] ?? '');

    // Pro mode fields
    $focus_of_day = trim($_POST['focus_of_day'] ?? '');
    $thought_of_day = trim($_POST['thought_of_day'] ?? '');
    $what_im_afraid_of = trim($_POST['what_im_afraid_of'] ?? '');
    $what_to_take_from_surroundings = trim($_POST['what_to_take_from_surroundings'] ?? '');
    $world_picture = trim($_POST['world_picture'] ?? '');
    $happiness = trim($_POST['happiness'] ?? '');

    // Check if a report for this date already exists
    $check_sql = "SELECT id FROM energy_reports WHERE user_id = ? AND report_date = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $user_id, $report_date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Update existing report
        $report_id = $check_result->fetch_assoc()['id'];
        $sql = "UPDATE energy_reports SET 
                physical_energy = ?, mental_energy = ?, emotional_energy = ?, why_not_energy = ?,
                physical_comment = ?, mental_comment = ?, emotional_comment = ?, why_not_comment = ?,
                focus_of_day = ?, thought_of_day = ?, what_im_afraid_of = ?,
                what_to_take_from_surroundings = ?, world_picture = ?, happiness = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiissssssssssii", 
            $physical_energy, $mental_energy, $emotional_energy, $why_not_energy,
            $physical_comment, $mental_comment, $emotional_comment, $why_not_comment,
            $focus_of_day, $thought_of_day, $what_im_afraid_of,
            $what_to_take_from_surroundings, $world_picture, $happiness,
            $report_id, $user_id
        );
    } else {
        // Insert new report
        $sql = "INSERT INTO energy_reports 
                (user_id, report_date, physical_energy, mental_energy, emotional_energy, why_not_energy,
                 physical_comment, mental_comment, emotional_comment, why_not_comment,
                 focus_of_day, thought_of_day, what_im_afraid_of,
                 what_to_take_from_surroundings, world_picture, happiness)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiiiisssssssssss", 
            $user_id, $report_date, 
            $physical_energy, $mental_energy, $emotional_energy, $why_not_energy,
            $physical_comment, $mental_comment, $emotional_comment, $why_not_comment,
            $focus_of_day, $thought_of_day, $what_im_afraid_of,
            $what_to_take_from_surroundings, $world_picture, $happiness
        );
    }

    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Report saved successfully'];
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to save report: ' . $stmt->error];
    }

    $stmt->close();
    $check_stmt->close();
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method'];
}

$conn->close();

echo json_encode($response);
?>