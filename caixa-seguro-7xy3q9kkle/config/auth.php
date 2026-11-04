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

// Verificar se a sessão expirou (apenas se usuário estiver logado)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
    session_unset();
    session_destroy();
    
    // Se não está na página de login, redirecionar
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $caixaPath = '/caixa-seguro-7xy3q9kkle';
        $adminPath = '/admin-seguro-7xy3q9bk';
        $caixaPosition = strpos($scriptName, $caixaPath);
        $adminPosition = strpos($scriptName, $adminPath);
        $basePath = $caixaPosition !== false
            ? substr($scriptName, 0, $caixaPosition)
            : ($adminPosition !== false ? substr($scriptName, 0, $adminPosition) : '');
        $destinoCardapio = strpos($scriptName, '/modules/cardapio/') !== false
            || ($adminPosition !== false && (
                strpos($scriptName, '/gerenciar_cardapio.php') !== false
                || strpos($scriptName, '/salvar_cardapio.php') !== false
            ))
            ? '&destino=cardapio'
            : '';

        header('Location: ' . rtrim($basePath, '/') . $caixaPath . '/login.php?expired=1' . $destinoCardapio);
        exit;
    }
}

// Atualizar timestamp da última atividade se o usuário estiver logado
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    $_SESSION['last_activity'] = time();
}
?>