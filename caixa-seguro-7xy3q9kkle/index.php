<?php
// Configurar timezone do PHP para Brasília
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/config/auth.php';

// Se não estiver logado, redireciona para login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true
    || !in_array(strtolower(trim((string) ($_SESSION['usuario_perfil'] ?? ''))), ['admin', 'caixa', 'estoque', 'garcom'], true)) {
    header('Location: login.php');
    exit;
}

if (strtolower(trim((string) ($_SESSION['usuario_perfil'] ?? ''))) === 'garcom') {
    header('Location: modules/garcom/');
    exit;
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/header.php';

$urlGerenciarCardapio = PathConfig::modules('cardapio/');
?>
<div
    id="react-app"
    data-screen="dashboard"
    data-role="<?= htmlspecialchars(currentUserRole(), ENT_QUOTES, 'UTF-8') ?>"
    data-first-name="<?= htmlspecialchars(explode(' ', trim($_SESSION['usuario_nome'] ?? 'Equipe'))[0], ENT_QUOTES, 'UTF-8') ?>"
    data-cash-url="<?= htmlspecialchars(PathConfig::modules('caixa/'), ENT_QUOTES, 'UTF-8') ?>"
    data-stock-url="<?= htmlspecialchars(PathConfig::modules('estoque/'), ENT_QUOTES, 'UTF-8') ?>"
    data-reports-url="<?= htmlspecialchars(PathConfig::modules('relatorios/'), ENT_QUOTES, 'UTF-8') ?>"
    data-admin-url="<?= htmlspecialchars(PathConfig::modules('admin/'), ENT_QUOTES, 'UTF-8') ?>"
    data-menu-url="<?= htmlspecialchars($urlGerenciarCardapio, ENT_QUOTES, 'UTF-8') ?>"
></div>
<?php
require_once __DIR__ . '/includes/footer.php';
?>