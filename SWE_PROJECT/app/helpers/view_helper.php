<?php
// View helper functions

function showMessage() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['type']) && $_SESSION['type'] == 'admin';
    }
}

function getBasePath() {
    return '../';
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        // project base is two levels up from script (e.g. /SWE_PROJECT)
        $projectBase = rtrim(dirname(dirname($script)), '\/');
        $publicBase = $projectBase . '/public';
        if ($path === '' || $path === null) {
            return $publicBase;
        }
        return $publicBase . '/' . ltrim($path, '/');
    }
}

if (!function_exists('pages_url')) {
    function pages_url($path = '') {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $projectBase = rtrim(dirname(dirname($script)), '\/');
        $pagesBase = $projectBase . '/pages';
        if ($path === '' || $path === null) {
            return $pagesBase;
        }
        return $pagesBase . '/' . ltrim($path, '/');
    }
}
?>

