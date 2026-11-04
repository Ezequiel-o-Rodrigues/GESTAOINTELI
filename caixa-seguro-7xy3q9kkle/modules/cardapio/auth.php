<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/auth.php';

if (empty($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ' . PathConfig::url('login.php?destino=cardapio'));
    exit;
}

if (strtolower(trim((string) ($_SESSION['usuario_perfil'] ?? ''))) !== 'admin') {
    http_response_code(403);
    exit('Apenas administradores podem acessar o gerenciamento do cardápio.');
}
