<?php
function refresh_session() {
    if (isset($_COOKIE['user_id'])) {
        $user_id = intval($_COOKIE['user_id']);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['last_activity'] = time();
        $expire = time() + (30 * 24 * 60 * 60); // 30 дней
        setcookie('user_id', $user_id, $expire, '/', '', true, true);
        session_regenerate_id(true); // Обновляем ID сессии
        ini_set('session.gc_maxlifetime', 3600 * 24 * 30); // Обновляем время жизни сессии
        session_set_cookie_params(3600 * 24 * 30);
    }
}

function should_refresh_session() {
    // Проверяем, прошло ли 5 минут с последнего обновления
    return !isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 300);
}

?>