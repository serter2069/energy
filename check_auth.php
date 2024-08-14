<?php
// check_auth.php

function is_logged_in() {
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

function require_login() {
    // If user is not logged in, redirect to login page
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}
?>