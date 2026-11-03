<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';

function currentUserRole() {
    return strtolower(trim((string) ($_SESSION['usuario_perfil'] ?? '')));
}

function canAccessModule($module) {
    $role = currentUserRole();
    $permissions = [
        'admin' => ['admin', 'caixa', 'estoque', 'relatorios'],
        'caixa' => ['caixa', 'relatorios'],
        'estoque' => ['estoque', 'relatorios'],
    ];

    return in_array($module, $permissions[$role] ?? [], true);
}

function requireModuleAccess($module) {
    if (empty($_SESSION['usuario_logado']) || empty($_SESSION['usuario_id'])) {
        header('Location: ' . PathConfig::url('login.php'));
        exit;
    }

    if (canAccessModule($module)) {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="pt-BR"><meta charset="utf-8"><title>Acesso negado</title><body style="font:16px system-ui,sans-serif;max-width:640px;margin:12vh auto;padding:24px;color:#172033"><h1>Acesso não autorizado</h1><p>Seu perfil não tem permissão para acessar este módulo.</p><a href="' . htmlspecialchars(PathConfig::url('index.php'), ENT_QUOTES, 'UTF-8') . '">Voltar ao início</a></body></html>';
    exit;
}
?>
