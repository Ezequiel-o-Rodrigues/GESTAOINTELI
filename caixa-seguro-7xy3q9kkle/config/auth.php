<?php
// Configurações básicas de sessão - versão simplificada
session_name('gestaointeli_sessao');
session_set_cookie_params([
    'lifetime' => 28800, // 8 horas
    'path' => '/',
    'domain' => '',
    'secure' => true,    // HTTPS ativado 
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEBUG: Log da sessão
error_log("Sessão iniciada: " . session_id());
error_log("Dados da sessão: " . print_r($_SESSION, true));

// Verificar se a sessão expirou (apenas se usuário estiver logado)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
    error_log("Sessão expirada");
    session_unset();
    session_destroy();
    
    // Se não está na página de login, redirecionar
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: /login.php?expired=1');
        exit;
    }
}

// Atualizar timestamp da última atividade se o usuário estiver logado
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    $_SESSION['last_activity'] = time();
    error_log("Atividade atualizada para usuário: " . $_SESSION['usuario_nome']);
}
?>