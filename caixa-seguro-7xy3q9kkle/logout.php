<?php
require_once __DIR__ . '/config/auth.php';

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para login
header('Location: login.php?logout=1');
exit;