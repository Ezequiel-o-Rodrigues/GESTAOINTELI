<?php
require_once __DIR__ . '/permissions.php';

function checkAuth() {
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['usuario_logado'])) {
        header('Location: ' . PathConfig::url('login.php'));
        exit;
    }

    if (!in_array(currentUserRole(), ['admin', 'caixa', 'estoque'], true)) {
        http_response_code(403);
        exit('Perfil de acesso inválido.');
    }

    return true;
}

function getUserPermissions() {
    return currentUserRole();
}

function isAdmin() {
    return currentUserRole() === 'admin';
}