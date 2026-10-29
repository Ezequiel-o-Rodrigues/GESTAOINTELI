<?php
require_once __DIR__ . '/../../caixa-seguro-7xy3q9kkle/config/auth.php';

if (
    !isset($_SESSION['usuario_logado'], $_SESSION['usuario_perfil'])
    || $_SESSION['usuario_logado'] !== true
    || $_SESSION['usuario_perfil'] !== 'admin'
) {
    header('Location: ../caixa-seguro-7xy3q9kkle/login.php');
    exit;
}
