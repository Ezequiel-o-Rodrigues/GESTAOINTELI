<?php
require_once __DIR__ . '/../../caixa-seguro-7xy3q9kkle/config/auth.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../caixa-seguro-7xy3q9kkle/login.php?destino=cardapio');
    exit;
}

$usuarioId = filter_var($_SESSION['usuario_id'] ?? null, FILTER_VALIDATE_INT);
if (!$usuarioId || $usuarioId < 1) {
    http_response_code(403);
    exit('Sessão inválida. Saia do sistema, entre novamente e tente acessar o módulo.');
}

require_once __DIR__ . '/../../caixa-seguro-7xy3q9kkle/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('Conexão indisponível.');
    }

    $stmt = $db->prepare('SELECT perfil, ativo FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Falha ao validar permissão do gerenciador de cardápio: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível validar sua permissão agora. Tente novamente mais tarde.');
}

if (!$usuario || (int) $usuario['ativo'] !== 1) {
    http_response_code(403);
    exit('Esta conta está inativa ou não existe mais. Entre em contato com um administrador.');
}

$perfil = strtolower(trim((string) $usuario['perfil']));
if (!in_array($perfil, ['admin', 'administrador'], true)) {
    http_response_code(403);
    exit('Acesso restrito: esta conta precisa ter perfil de administrador para editar o cardápio.');
}
