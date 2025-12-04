<?php
if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 86400 * 7; // 7 days
    session_set_cookie_params($lifetime);
    session_start();
}

