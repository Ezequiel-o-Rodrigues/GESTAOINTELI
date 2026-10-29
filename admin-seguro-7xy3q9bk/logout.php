<?php
session_start();

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se deseja matar a sessão, apague também o cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Redireciona para o login
header("Location: index.php");
exit();
?>