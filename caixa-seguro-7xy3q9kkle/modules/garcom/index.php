<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../includes/permissions.php';
requireModuleAccess('garcom');
require_once __DIR__ . '/../../config/database.php';

$produtos = [];
$erroProdutos = '';

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare(
        'SELECT id, nome, preco, estoque_atual
         FROM produtos
         WHERE ativo = 1
         ORDER BY nome'
    );
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao carregar produtos no módulo Garçom: ' . $e->getMessage());
    $erroProdutos = 'Não foi possível carregar os produtos. Atualize a página ou avise o administrador.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>Garçom | GestãoInteli</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(PathConfig::modules('garcom/garcom.css'), ENT_QUOTES, 'UTF-8') ?>?v=<?= filemtime(__DIR__ . '/garcom.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="waiter-app">
    <header class="waiter-header">
        <div>
            <p class="waiter-eyebrow">ESPETINHO DO JÚNIOR</p>
            <h1>Atendimento</h1>
        </div>
        <div class="waiter-account">
            <button type="button" class="waiter-orders-toggle" id="toggleOrders" aria-expanded="false" aria-controls="ordersSidebar">
                <i class="bi bi-list-ul" aria-hidden="true"></i>
                <span>Minhas comandas</span>
                <span id="ordersCount" class="waiter-count">0</span>
            </button>
            <span><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Garçom', ENT_QUOTES, 'UTF-8') ?></span>
            <a href="<?= htmlspecialchars(PathConfig::url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Sair</a>
        </div>
    </header>

    <div id="ordersBackdrop" class="waiter-sidebar-backdrop" hidden></div>
    <aside id="ordersSidebar" class="waiter-sidebar" role="dialog" aria-modal="true" aria-label="Minhas comandas abertas" aria-hidden="true" inert>
        <div class="waiter-sidebar-heading">
            <div>
                <p class="waiter-eyebrow">EM ATENDIMENTO</p>
                <h2>Minhas comandas</h2>
            </div>
            <button type="button" id="closeOrders" class="waiter-sidebar-close" aria-label="Fechar lista de comandas">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <label class="waiter-orders-search" for="searchOrders">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="searchOrders" type="search" placeholder="Cliente ou mesa..." autocomplete="off">
        </label>
        <div id="ordersList" class="waiter-orders-list" aria-live="polite">
            <p class="waiter-empty">Carregando comandas...</p>
        </div>
    </aside>

    <main class="waiter-main">
        <section class="waiter-card waiter-create" aria-labelledby="create-title">
            <div class="waiter-section-heading">
                <div>
                    <p class="waiter-eyebrow">NOVO ATENDIMENTO</p>
                    <h2 id="create-title">Abrir comanda</h2>
                </div>
            </div>
            <p class="waiter-help">Informe o nome do cliente ou o número da mesa. Basta preencher um dos campos.</p>
            <form id="newOrderForm" class="waiter-create-form">
                <label>
                    <span>Nome do cliente</span>
                    <input type="text" name="cliente_nome" maxlength="120" autocomplete="off" placeholder="Ex.: Ana">
                </label>
                <label>
                    <span>Número da mesa</span>
                    <input type="number" name="mesa_numero" min="1" max="99999999999999999999" inputmode="numeric" placeholder="Ex.: 12">
                </label>
                <button class="waiter-button waiter-button--primary" type="submit">
                    <i class="bi bi-plus-circle" aria-hidden="true"></i> Abrir comanda
                </button>
            </form>
        </section>

        <div id="waiterMessage" class="waiter-message" role="status" aria-live="polite"></div>

        <div class="waiter-layout">
            <section id="activeOrder" class="waiter-card waiter-workspace" aria-labelledby="activeOrderTitle" hidden>
                <div class="waiter-section-heading">
                    <div>
                        <p class="waiter-eyebrow">COMANDA SELECIONADA</p>
                        <h2 id="activeOrderTitle">Selecione uma comanda</h2>
                        <p id="activeOrderDetail" class="waiter-help"></p>
                    </div>
                </div>

                <div class="waiter-item-section">
                    <h3>Itens da comanda</h3>
                    <div id="orderItems" class="waiter-items">
                        <p class="waiter-empty">Nenhum item adicionado.</p>
                    </div>
                    <div class="waiter-order-total">
                        <span>Total parcial</span>
                        <strong id="orderTotal">R$ 0,00</strong>
                    </div>
                </div>

                <div class="waiter-item-section">
                    <div class="waiter-section-heading waiter-products-heading">
                        <div>
                            <h3>Adicionar produtos</h3>
                            <p class="waiter-help">A finalização da comanda é feita exclusivamente pelo caixa.</p>
                        </div>
                    </div>
                    <label class="waiter-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input id="productSearch" type="search" placeholder="Buscar produto..." autocomplete="off">
                    </label>
                    <?php if ($erroProdutos !== ''): ?>
                        <p class="waiter-error" role="alert"><?= htmlspecialchars($erroProdutos, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php elseif (!$produtos): ?>
                        <p class="waiter-empty">Nenhum produto ativo cadastrado.</p>
                    <?php else: ?>
                        <div class="waiter-products">
                            <?php foreach ($produtos as $produto): ?>
                                <article class="waiter-product" data-product-card data-product-name="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                    <div>
                                        <h4><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></h4>
                                        <p>R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?></p>
                                        <small>Estoque: <?= (int) $produto['estoque_atual'] ?></small>
                                    </div>
                                    <button
                                        class="waiter-button waiter-button--add"
                                        type="button"
                                        data-add-product="<?= (int) $produto['id'] ?>"
                                        aria-label="Adicionar <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                    ><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Adicionar</span></button>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        window.garcomConfig = {
            ordersUrl: <?= json_encode(PathConfig::api('garcom_comandas.php')) ?>,
            itemsUrl: <?= json_encode(PathConfig::api('garcom_itens.php')) ?>,
            removeItemUrl: <?= json_encode(PathConfig::api('garcom_remover_item.php')) ?>
        };
    </script>
    <script src="<?= htmlspecialchars(PathConfig::modules('garcom/garcom.js'), ENT_QUOTES, 'UTF-8') ?>?v=<?= filemtime(__DIR__ . '/garcom.js') ?>"></script>
</body>
</html>
