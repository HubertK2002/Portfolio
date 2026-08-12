<?php
// ============================================================
//  auth.php — proste uwierzytelnianie (sesja + hasło) dla edytora
// ============================================================

function kb_config() {
    static $c = null;
    if ($c === null) {
        $f = __DIR__ . '/config.php';
        $c = file_exists($f) ? require $f : [];
    }
    return $c;
}

function kb_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('kb_sess');
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function kb_configured() {
    $c = kb_config();
    return !empty($c['password_hash']);
}

function kb_is_logged_in() {
    kb_session();
    return !empty($_SESSION['kb_auth']);
}

function kb_login($password) {
    kb_session();
    $c = kb_config();
    if (empty($c['password_hash'])) return false;
    if (password_verify((string)$password, $c['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['kb_auth'] = true;
        return true;
    }
    return false;
}

function kb_logout() {
    kb_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Wymuś logowanie (przekierowanie na login). Dla stron HTML. */
function kb_require_login($loginUrl = 'login.php') {
    if (!kb_is_logged_in()) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? 'edytor.php');
        header('Location: ' . $loginUrl . '?next=' . $next);
        exit;
    }
}

/** Token CSRF */
function kb_csrf() {
    kb_session();
    if (empty($_SESSION['kb_csrf'])) $_SESSION['kb_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['kb_csrf'];
}

function kb_csrf_check($t) {
    kb_session();
    return is_string($t) && !empty($_SESSION['kb_csrf']) && hash_equals($_SESSION['kb_csrf'], $t);
}
