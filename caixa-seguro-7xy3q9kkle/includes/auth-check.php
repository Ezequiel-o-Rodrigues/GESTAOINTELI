<?php
require_once __DIR__ . '/../config/auth.php';

function checkAuth() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_logado'])) {
        header('Location: /gestaointeli-jnr/login.php');
        exit;
    }
    
    return true;
}

function getUserPermissions() {
    return $_SESSION['usuario_perfil'] ?? 'usuario';
}

function isAdmin() {
    return ($_SESSION['usuario_perfil'] ?? '') === 'admin';
}