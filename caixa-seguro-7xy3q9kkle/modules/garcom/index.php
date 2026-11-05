<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../includes/permissions.php';
requireModuleAccess('garcom');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/menu-images.php';

$produtos = [];
$categoriasProduto = [];
$erroProdutos = '';
$base_path = rtrim(PathConfig::url(), '/') . '/';

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare(
        'SELECT p.*, c.nome AS categoria_nome
         FROM produtos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.ativo = 1
         ORDER BY c.nome, p.nome'
    );
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($produtos as $produto) {
        $categoriasProduto[(string) $produto['categoria_id']] = [
            'id' => (int) $produto['categoria_id'],
            'nome' => $produto['categoria_nome']
        ];
    }
} catch (PDOException $e) {
    error_log('Erro ao carregar produtos no módulo Garçom: ' . $e->getMessage());
    $erroProdutos = 'Não foi possível carregar os produtos. Atualize a página ou avise o administrador.';
}

$usar_mapa_mesas = false;
$erro_config_mapa_mesas = false;
try {
    $stmt_mapa = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'usar_mapa_mesas'");
    $stmt_mapa->execute();
    $usar_mapa_mesas = $stmt_mapa->fetchColumn() === '1';
} catch (PDOException $e) {
    error_log('Erro ao carregar configuração do mapa de mesas no módulo Garçom: ' . $e->getMessage());
    $erro_config_mapa_mesas = true;
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
                <span>Comandas abertas</span>
                <span id="ordersCount" class="waiter-count">0</span>
            </button>
            <a class="waiter-help-link" href="<?= htmlspecialchars(PathConfig::modules('ajuda/'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-question-circle" aria-hidden="true"></i> Ajuda
            </a>
            <span><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Garçom', ENT_QUOTES, 'UTF-8') ?></span>
            <a href="<?= htmlspecialchars(PathConfig::url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Sair</a>
        </div>
    </header>

    <div id="ordersBackdrop" class="waiter-sidebar-backdrop" hidden></div>
    <aside id="ordersSidebar" class="waiter-sidebar" role="dialog" aria-modal="true" aria-label="Comandas abertas para atendimento conjunto" aria-hidden="true" inert>
        <div class="waiter-sidebar-heading">
            <div>
                <p class="waiter-eyebrow">EM ATENDIMENTO</p>
                <h2>Comandas abertas</h2>
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
        <div id="waiterMessage" class="waiter-message" role="status" aria-live="polite"></div>

        <section id="ordersDashboard" class="waiter-dashboard" aria-labelledby="dashboardTitle">
            <div class="waiter-dashboard-heading">
                <div>
                    <p class="waiter-eyebrow">ATENDIMENTO COOPERATIVO</p>
                    <h2 id="dashboardTitle">Comandas abertas</h2>
                    <p class="waiter-help"><?= $erro_config_mapa_mesas
                        ? 'Não foi possível carregar a configuração do atendimento. Avise o administrador antes de abrir comandas.'
                        : ($usar_mapa_mesas
                            ? 'Para abrir uma nova comanda, selecione uma mesa livre no Mapa de Mesas abaixo.'
                            : 'Todos os garçons podem visualizar e atender qualquer comanda aberta.')
                    ?></p>
                </div>
                <button class="waiter-button waiter-button--primary" type="button" id="openCreateOrder" <?= ($usar_mapa_mesas || $erro_config_mapa_mesas) ? 'hidden' : '' ?>>
                    <i class="bi bi-plus-circle" aria-hidden="true"></i> Abrir comanda
                </button>
            </div>
            <div id="dashboardOrders" class="waiter-dashboard-orders" aria-live="polite">
                <p class="waiter-empty">Carregando comandas...</p>
            </div>
        </section>

        <dialog id="createOrderDialog" class="waiter-create-dialog" aria-labelledby="create-title">
            <form id="newOrderForm" class="waiter-create-form">
                <div class="waiter-dialog-heading">
                    <div>
                        <p class="waiter-eyebrow">NOVO ATENDIMENTO</p>
                        <h2 id="create-title">Abrir comanda</h2>
                    </div>
                    <button class="waiter-icon-button" type="button" id="closeCreateOrder" aria-label="Fechar">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="waiter-help">Informe o nome do cliente ou o número da mesa. Basta preencher um dos campos.</p>
                <p id="createOrderError" class="waiter-form-error" role="alert" hidden></p>
                <label>
                    <span>Nome do cliente</span>
                    <input type="text" name="cliente_nome" maxlength="120" autocomplete="off" placeholder="Ex.: Ana">
                </label>
                <label>
                    <span>Número da mesa</span>
                    <input type="number" name="mesa_numero" min="1" max="99999999999999999999" inputmode="numeric" placeholder="Ex.: 12">
                </label>
                <div class="waiter-dialog-actions">
                    <button class="waiter-button waiter-button--secondary" type="button" id="cancelCreateOrder">Cancelar</button>
                    <button class="waiter-button waiter-button--primary" type="submit">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i> Abrir comanda
                    </button>
                </div>
            </form>
        </dialog>

        <section id="orderDetail" class="waiter-order-detail" aria-labelledby="activeOrderTitle" hidden>
            <div class="waiter-detail-toolbar">
                <button type="button" class="waiter-button waiter-button--secondary" id="backToOrders">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar às comandas
                </button>
            </div>
            <div class="waiter-layout">
                <section id="activeOrder" class="waiter-card waiter-workspace" aria-labelledby="activeOrderTitle">
                    <div class="waiter-section-heading">
                        <div>
                            <p class="waiter-eyebrow">COMANDA EM EDIÇÃO</p>
                            <h2 id="activeOrderTitle">Selecione uma comanda</h2>
                            <p id="activeOrderDetail" class="waiter-help"></p>
                        </div>
                    </div>

                    <div id="orderItems" class="waiter-items">
                        <p class="waiter-empty">Nenhum item adicionado.</p>
                    </div>
                    <div class="waiter-order-total">
                        <span>Total parcial</span>
                        <strong id="orderTotal">R$ 0,00</strong>
                    </div>
                    <p class="waiter-help">A finalização da comanda é feita exclusivamente pelo caixa.</p>
                </section>

                <section class="waiter-card waiter-products-section" aria-labelledby="products-title">
                    <div class="waiter-section-heading">
                        <div>
                            <p class="waiter-eyebrow">CARDÁPIO</p>
                            <h2 id="products-title">Adicionar produtos</h2>
                        </div>
                    </div>
                    <div class="waiter-filters">
                        <label class="waiter-search" for="productSearch">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="productSearch" type="search" placeholder="Buscar produto..." autocomplete="off">
                        </label>
                        <label class="waiter-category-filter" for="productCategory">
                            <i class="bi bi-funnel" aria-hidden="true"></i>
                            <select id="productCategory">
                                <option value="">Todas categorias</option>
                                <?php foreach ($categoriasProduto as $categoria): ?>
                                    <option value="<?= (int) $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div id="productsCount" class="waiter-products-count" aria-live="polite"></div>
                    <?php if ($erroProdutos !== ''): ?>
                        <p class="waiter-error" role="alert"><?= htmlspecialchars($erroProdutos, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php elseif (!$produtos): ?>
                        <p class="waiter-empty">Nenhum produto ativo cadastrado.</p>
                    <?php else: ?>
                        <div id="productCategoryGroups" class="waiter-product-category-groups">
                            <?php foreach ($categoriasProduto as $categoria): ?>
                                <?php
                                    $produtosDaCategoria = array_filter(
                                        $produtos,
                                        static fn($produto) => (int) $produto['categoria_id'] === $categoria['id']
                                    );
                                ?>
                                <?php if ($produtosDaCategoria): ?>
                                    <section class="waiter-product-category" data-category-group="<?= (int) $categoria['id'] ?>">
                                        <h3 class="waiter-category-divider">
                                            <span><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </h3>
                                        <div class="waiter-products">
                                            <?php foreach ($produtosDaCategoria as $produto): ?>
                                                <?php
                                                    $imagemProduto = menuResolveImageSource(
                                                        $produto['imagem'] ?? '',
                                                        $produto['imagem_url'] ?? '',
                                                        __DIR__ . '/../../../public/images/menu',
                                                        $base_path . '../public/images/menu/'
                                                    );
                                                ?>
                                                <article
                                                    class="waiter-product"
                                                    data-product-card
                                                    data-product-name="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-product-category="<?= (int) $produto['categoria_id'] ?>"
                                                >
                                                    <img
                                                        class="waiter-product-image"
                                                        src="<?= htmlspecialchars($imagemProduto, ENT_QUOTES, 'UTF-8') ?>"
                                                        alt=""
                                                        loading="lazy"
                                                        onerror="this.onerror=null;this.src='<?= htmlspecialchars($base_path . '../public/images/menu/default.png', ENT_QUOTES, 'UTF-8') ?>'"
                                                    >
                                                    <div class="waiter-product-info">
                                                        <h3><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                                        <p class="waiter-product-price">R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?></p>
                                                        <small>Estoque: <?= (int) $produto['estoque_atual'] ?></small>
                                                    </div>
                                                    <button
                                                        class="waiter-button waiter-button--add"
                                                        type="button"
                                                        data-add-product="<?= (int) $produto['id'] ?>"
                                                        aria-label="Adicionar <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?> à comanda"
                                                    ><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Adicionar</span></button>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <p id="productsEmpty" class="waiter-empty" hidden>Nenhum produto encontrado com esses filtros.</p>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>

    <script>
        window.garcomConfig = {
            ordersUrl:    <?= json_encode(PathConfig::api('garcom_comandas.php')) ?>,
            itemsUrl:     <?= json_encode(PathConfig::api('garcom_itens.php')) ?>,
            removeItemUrl:<?= json_encode(PathConfig::api('garcom_remover_item.php')) ?>,
            mapaMesasUrl: <?= json_encode(PathConfig::api('mapa_mesas.php')) ?>,
            novaComandaUrl: <?= json_encode(PathConfig::api('nova_comanda.php')) ?>,
            usarMapaMesas: <?= $usar_mapa_mesas ? 'true' : 'false' ?>,
            erroConfigMapaMesas: <?= $erro_config_mapa_mesas ? 'true' : 'false' ?>
        };
    </script>
    <?php if ($usar_mapa_mesas): ?>
    <style>
        #mapaMesas { padding: 16px; }
        .mapa-canvas { position: relative; width: 100%; min-height: 400px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; overflow: auto; }
        .mesa-btn { position: absolute; width: 72px; height: 72px; border-radius: 10px; border: 2px solid; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; transition: transform .15s, box-shadow .15s; background: white; }
        .mesa-btn:hover { transform: scale(1.08); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
        .mesa-btn.livre   { border-color: #16a34a; color: #16a34a; }
        .mesa-btn.ocupada { border-color: #dc2626; color: #dc2626; background: #fef2f2; }
        .mesa-btn small { font-size: 0.65rem; font-weight: 400; }
        #mesaMultiDialog { background: rgba(0,0,0,.5); position: fixed; inset: 0; z-index: 999; display: flex; align-items: center; justify-content: center; }
        #mesaMultiDialog[hidden] { display: none; }
        #mesaMultiDialog .inner { background: white; border-radius: 12px; padding: 24px; min-width: 280px; max-width: 360px; }
    </style>
    <section id="mapaMesas" aria-label="Mapa de mesas">
        <div class="waiter-dashboard-heading">
            <div>
                <p class="waiter-eyebrow">SALÃO</p>
                <h2>Mapa de Mesas</h2>
            </div>
            <button class="waiter-button waiter-button--secondary" type="button" id="btnAtualizarMapa">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Atualizar
            </button>
        </div>
        <div class="mapa-canvas" id="mapaCanvas" aria-live="polite"></div>
    </section>
    <div id="mesaMultiDialog" hidden role="dialog" aria-modal="true" aria-labelledby="mesaMultiTitle">
        <div class="inner">
            <h3 id="mesaMultiTitle" style="margin-bottom:12px">Selecione a comanda</h3>
            <div id="mesaMultiLista"></div>
            <button class="waiter-button waiter-button--secondary" style="margin-top:12px;width:100%" onclick="document.getElementById('mesaMultiDialog').hidden=true">Cancelar</button>
        </div>
    </div>
    <?php endif; ?>
    <script src="<?= htmlspecialchars(PathConfig::modules('garcom/garcom.js'), ENT_QUOTES, 'UTF-8') ?>?v=<?= filemtime(__DIR__ . '/garcom.js') ?>"></script>
</body>
</html>
