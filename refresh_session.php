<?php
session_start();
require_once 'session_functions.php';

header('Content-Type: application/json');

if (should_refresh_session()) {
    refresh_session();
    echo json_encode(['status' => 'success', 'message' => 'Session refreshed']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Session refresh not needed']);
}
?>