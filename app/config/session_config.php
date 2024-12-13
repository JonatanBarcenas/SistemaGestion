<?php
// config/session_config.php

function initSession() {
    $sessionPath = 'C:/Apache24/tmp';
    if (!file_exists($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function checkLogin() {
    initSession();
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../views/auth/login.php");
        exit();
    }
}

function getUserName() {
    return isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario';
}
?>