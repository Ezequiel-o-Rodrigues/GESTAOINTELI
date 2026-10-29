<?php
require_once __DIR__ . '/../../caixa-seguro-7xy3q9kkle/config/auth.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../caixa-seguro-7xy3q9kkle/login.php?destino=cardapio');
    exit;
}

if (($_SESSION['usuario_perfil'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acesso negado. Este módulo é exclusivo para administradores.');
}
