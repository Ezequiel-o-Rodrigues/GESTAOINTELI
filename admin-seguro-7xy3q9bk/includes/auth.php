<?php
session_start();

// Tempo máximo de inatividade (30 minutos)
define('INACTIVITY_LIMIT', 1800);

// Verifica se o usuário está logado
if (!isset($_SESSION['admin_logado'])) {
    error_log("Tentativa de acesso não autorizado - IP: " . $_SERVER['REMOTE_ADDR']);
    sleep(2); // Atraso para dificultar ataques de força bruta
    header('Location: ../index.php');
    exit();
}

// Verifica tempo de inatividade
if (isset($_SESSION['ultimo_acesso'])) {
    $inativo = time() - $_SESSION['ultimo_acesso'];
    if ($inativo > INACTIVITY_LIMIT) {
        session_destroy();
        header('Location: ../index.php?erro=inativo');
        exit();
    }
}

// Atualiza o tempo de acesso
$_SESSION['ultimo_acesso'] = time();



?>