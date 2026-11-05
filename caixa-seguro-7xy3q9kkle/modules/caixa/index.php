<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../includes/permissions.php';
requireModuleAccess('caixa');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/menu-images.php';

$base_path = rtrim(PathConfig::url(), '/') . '/';

// INICIALIZAR VARIÁVEIS COM VALORES PADRÃO
$produtos_por_categoria = [];
$categorias = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Buscar TODOS os produtos ativos com suas categorias
    $query = "SELECT p.*, c.nome as categoria_nome, c.id as categoria_id 
              FROM produtos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.ativo = 1 
              ORDER BY c.nome, p.nome";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar produtos por categoria
    foreach ($produtos as $produto) {
        $categoria_id = $produto['categoria_id'];
        if (!isset($produtos_por_categoria[$categoria_id])) {
            $produtos_por_categoria[$categoria_id] = [
                'categoria_nome' => $produto['categoria_nome'],
                'produtos' => []
            ];
        }
        $produtos_por_categoria[$categoria_id]['produtos'][] = $produto;
    }

    // BUSCAR CATEGORIAS PARA O SELECT
    $query_categorias = "SELECT * FROM categorias ORDER BY nome";
    $stmt_categorias = $db->prepare($query_categorias);
    $stmt_categorias->execute();
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Log do erro sem quebrar a aplicação
    error_log("Erro ao carregar dados: " . $e->getMessage());
    $produtos_por_categoria = [];
    $categorias = [];
}
  // BUSCAR GARÇONS ATIVOS
    $query_garcons = "SELECT id, nome, codigo FROM garcons WHERE ativo = 1 ORDER BY codigo";
    $stmt_garcons = $db->prepare($query_garcons);
    $stmt_garcons->execute();
    $garcons = $stmt_garcons->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caixa - Sistema Restaurante</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(PathConfig::url('css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            height: 100vh;
        }

        /* CABEÇALHO */
        .mini-header {
            background: #2c3e50;
            color: white;
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 32px;
        }

        .mini-header h1 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .mini-header > a {
            color: white;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background 0.2s ease;
        }

        .mini-header > a:hover,
        .mini-header > a:focus-visible {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-voltar {
            background: #34495e;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
        }

        /* COMANDA HORIZONTAL - NOVO LAYOUT */
        .comanda-horizontal {
            background: white;
            border-bottom: 2px solid #3498db;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            height: 70px;
            flex-shrink: 0;
        }

        .comanda-info-horizontal {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 120px;
        }

        .comanda-numero {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1rem;
        }

        .itens-comanda-horizontal {
            flex: 1;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 5px 0;
            min-height: 50px;
            align-items: center;
        }

        .item-comanda-horizontal {
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 20px;
            padding: 6px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .item-nome {
            font-weight: 600;
            color: #2c3e50;
        }

        .item-quantidade {
            background: #3498db;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .item-quantidade:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        .item-preco {
            font-weight: bold;
            color: #27ae60;
        }

        .btn-remover,
        .btn-remover-item {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            min-width: 64px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0 8px;
        }

        .btn-remover:hover,
        .btn-remover-item:hover {
            background: #c0392b;
        }

        .total-comanda {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1rem;
            min-width: 100px;
            text-align: right;
        }

        .empty-comanda {
            color: #95a5a6;
            font-style: italic;
            font-size: 0.85rem;
        }

        /* BOTÕES DE AÇÃO */
        .botoes-comanda {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            height: 32px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        /* CONTEÚDO PRINCIPAL - MAIS ESPAÇO PARA PRODUTOS */
        .conteudo-principal {
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        /* FILTROS */
        .filtros-container {
            background: white;
            padding: 8px 12px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 150px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.85rem;
            height: 32px;
        }

        .categoria-filtro {
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.85rem;
            background: white;
            height: 32px;
            min-width: 120px;
        }

        .contador-produtos {
            color: #7f8c8d;
            font-size: 0.8rem;
            white-space: nowrap;
        }

       /* PRODUTOS - NOVO ESTILO COM CORES POR CATEGORIA */
.produtos-scroll-container {
    flex: 1;
    overflow-y: auto;
    padding: 5px;
    height: 100%;
}

/* Remove os títulos de categoria com fundo azul */
.categoria-produtos {
    margin-bottom: 8px;
}

.categoria-titulo {
    display: none; /* Esconde os títulos azuis */
}

.produtos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
    margin-bottom: 10px;
}

.produto-card {
    padding: 8px;
    border: 0.5px solid; /* Borda mais espessa para o efeito tabela periódica */
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.5s ease;
    background: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 90px;
    position: relative;
    overflow: hidden;
}

/* Efeito de brilho suave no hover */
.produto-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    filter: brightness(1.05);
}

/* Mini indicador de categoria no topo */
.produto-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: currentColor;
    opacity: 0.7;
}

.produto-nome {
    font-weight: 600;
    font-size: 0.75rem;
    line-height: 1.2;
    margin-bottom: 4px;
    color: #000000ff;
    z-index: 1;
}

.produto-preco {
    font-weight: bold;
    font-size: 0.8rem;
    margin: 3px 0;
    z-index: 1;
}

.produto-estoque {
    font-size: 0.7rem;
    z-index: 1;
    color: #007a1bff;
}

.estoque-baixo {
    font-weight: bold;
    color: #ff1900ff;
}

/* CORES POR CATEGORIA - Inspirado na Tabela Periódica */
/* Alimenticio - Verde (como metais alcalinos) */
.produto-card[data-produto-categoria="1"] {
    border-color: #128040ff;
    background: linear-gradient(135deg, #006e0062, #0e8b239c);
    color: #27ae60;
}

.produto-card[data-produto-categoria="1"] .produto-preco {
    color: #229954;
}

/* Bebidas não alcoólicas - Azul (como gases nobres) */
.produto-card[data-produto-categoria="3"] {
    border-color: #3498db;
    background: linear-gradient(135deg, #023a8386, #abccf8ad);
    color: #3498db;
}

.produto-card[data-produto-categoria="3"] .produto-preco {
    color: #2980b9;
}

/* Bebidas alcoólicas - Vermelho/Laranja (como metais de transição) */
.produto-card[data-produto-categoria="4"] {
    border-color: #ff1900ff;
    background: linear-gradient(135deg, #8a040485, #fadbd8);
    color: #e74c3c;
}

.produto-card[data-produto-categoria="4"] .produto-preco {
    color: #cb4335;
}

/* Diversos - Roxo (como lantanídeos) */
.produto-card[data-produto-categoria="5"] {
    border-color: #8e44ad;
    background: linear-gradient(135deg, #faf8ff, #e8daef);
    color: #8e44ad;
}

.produto-card[data-produto-categoria="5"] .produto-preco {
    color: #7d3c98;
}

/* Estoque baixo - destaque adicional */
.produto-card.estoque-baixo {
    animation: pulse-alert 10s infinite;
    border-width: 3px;
}

@keyframes pulse-alert {
    0% { border-color: currentColor; }
    50% { border-color: #e74c3c; }
    100% { border-color: currentColor; }
}


        /* SCROLL HORIZONTAL PARA COMANDA */
        .itens-comanda-horizontal::-webkit-scrollbar {
            height: 4px;
        }

        .itens-comanda-horizontal::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .itens-comanda-horizontal::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .comanda-horizontal {
                flex-direction: column;
                height: auto;
                padding: 8px;
                gap: 8px;
            }
            
            .itens-comanda-horizontal {
                order: 2;
                width: 100%;
            }
            
            .botoes-comanda {
                order: 1;
                width: 100%;
                justify-content: space-between;
            }
            
            .produtos-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }

        .btn-garcom {
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .btn-garcom:hover {
            background: #e3f2fd;
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }

        .btn-garcom:active {
            transform: translateY(0);
        }
    </style>
    <link rel="stylesheet" href="<?= htmlspecialchars(PathConfig::modules('caixa/caixa.css'), ENT_QUOTES, 'UTF-8') ?>?v=<?= filemtime(__DIR__ . '/caixa.css') ?>">
</head>
<body class="cash-register">
    <!-- CABEÇALHO -->
    <div id="react-app" data-screen="cash-header" data-home-url="<?= htmlspecialchars(PathConfig::url('index.php'), ENT_QUOTES, 'UTF-8') ?>" data-help-url="<?= htmlspecialchars(PathConfig::modules('ajuda/'), ENT_QUOTES, 'UTF-8') ?>"></div>

    <!-- COMANDA HORIZONTAL - NOVA POSIÇÃO -->
    <div class="comanda-horizontal">
        <div class="comanda-info-horizontal">
            <span class="comanda-numero" id="numero-comanda">--</span>
            <button type="button" class="btn btn-orders" id="toggle-open-orders" aria-expanded="false" aria-controls="open-orders-panel">
                <i class="bi bi-list-ul" aria-hidden="true"></i> Comandas
            </button>
            <button class="btn btn-primary" id="btn-nova-comanda-topo" onclick="novaComanda()"><i class="bi bi-plus-circle" aria-hidden="true"></i> Nova comanda</button>
            <span id="contexto-comanda" class="cash-order-context" aria-live="polite"></span>
        </div>
        
        <div class="itens-comanda-horizontal" id="itens-comanda">
            <div class="empty-comanda">Selecione uma comanda para iniciar</div>
        </div>

        <div id="open-orders-backdrop" class="open-orders-backdrop" hidden></div>
        <aside id="open-orders-panel" class="open-orders-panel" role="dialog" aria-modal="true" aria-label="Comandas abertas" aria-hidden="true" inert>
            <div class="open-orders-heading">
                <div>
                    <p class="waiter-eyebrow">ATENDIMENTOS</p>
                    <h2>Comandas abertas</h2>
                </div>
                <button type="button" id="close-open-orders" class="open-orders-close" aria-label="Fechar lista de comandas">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <label class="open-orders-search" for="search-open-orders">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="search-open-orders" type="search" placeholder="Cliente ou mesa..." autocomplete="off">
            </label>
            <button type="button" class="cash-button cash-button--primary open-orders-new" onclick="novaComanda()">
                <i class="bi bi-plus-circle" aria-hidden="true"></i> Nova comanda
            </button>
            <div id="open-orders-list" class="open-orders-list" aria-live="polite">
                <p class="open-orders-empty">Carregando comandas...</p>
            </div>
        </aside>

        <div class="botoes-comanda">
            <span class="total-comanda" id="total-comanda">
                R$ 0,00
            </span>
            <button class="btn btn-success" onclick="finalizarComanda()" id="btn-finalizar" disabled>
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Finalizar venda
            </button>
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL - MAIS ESPAÇO PARA PRODUTOS -->
    <div class="conteudo-principal">
        <div class="alert alert-info" role="status">
            Caixa, administração e garçons podem lançar produtos. A comissão do item é atribuída apenas quando lançado por um garçom autenticado.
        </div>
        <div class="filtros-container">
            <label class="cash-filter-search" for="search-produto">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="text" id="search-produto" class="search-input" placeholder="Buscar produto...">
            </label>
            <label class="cash-filter-category" for="filtro-categoria">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <select id="filtro-categoria" class="categoria-filtro">
                <option value="">Todas categorias</option>
                <?php foreach($categorias as $categoria): ?>
                <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                <?php endforeach; ?>
                </select>
            </label>
            <span id="contador-produtos" class="contador-produtos"></span>
        </div>

        <!-- Na parte dos produtos (mantenha tudo igual, só observe o data-categoria) -->
<div class="produtos-scroll-container" id="produtos-container">
    <?php if (!empty($produtos_por_categoria)): ?>
        <?php foreach($produtos_por_categoria as $categoria_id => $categoria_data): ?>
        <div class="categoria-produtos" data-categoria="<?= $categoria_id ?>">
            <!-- O título da categoria será escondido pelo CSS -->
            <div class="categoria-titulo" style="display: none;">
                <?= htmlspecialchars($categoria_data['categoria_nome']) ?>
                <span class="contador-categoria">(<?= count($categoria_data['produtos']) ?>)</span>
            </div>
            <div class="produtos-grid">
                <?php foreach($categoria_data['produtos'] as $produto): ?>
                <?php
                    $imagemProduto = menuResolveImageSource(
                        $produto['imagem'] ?? '',
                        $produto['imagem_url'] ?? '',
                        __DIR__ . '/../../../public/images/menu',
                        $base_path . '../public/images/menu/'
                    );
                ?>
                <div class="produto-card" 
                     data-produto-id="<?= $produto['id'] ?>"
                     data-produto-nome="<?= htmlspecialchars($produto['nome']) ?>"
                     data-produto-preco="<?= $produto['preco'] ?>"
                     data-produto-categoria="<?= $categoria_id ?>" 
                     data-produto-estoque="<?= $produto['estoque_atual'] ?>"
                     role="button"
                     tabindex="0"
                     aria-label="Adicionar <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?> à comanda"
                     onclick="adicionarProduto(<?= (int) $produto['id'] ?>, <?= htmlspecialchars(json_encode($produto['nome'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= json_encode((float) $produto['preco']) ?>)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <img
                        class="produto-imagem"
                        src="<?= htmlspecialchars($imagemProduto, ENT_QUOTES, 'UTF-8') ?>"
                        alt=""
                        loading="lazy"
                        onerror="this.onerror=null;this.src='<?= htmlspecialchars($base_path . '../public/images/menu/default.png', ENT_QUOTES, 'UTF-8') ?>'"
                    >
                    <div class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></div>
                    <div class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                    <div class="produto-estoque <?= $produto['estoque_atual'] <= $produto['estoque_minimo'] ? 'estoque-baixo' : '' ?>">
                        Est: <?= $produto['estoque_atual'] ?>
                    </div>
                    <span class="cash-product-add"><i class="bi bi-cart-plus" aria-hidden="true"></i> Adicionar</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #7f8c8d;">
            <h3>Nenhum produto cadastrado</h3>
            <p>Cadastre produtos no sistema primeiro</p>
        </div>
    <?php endif; ?>
</div>
        <!-- JavaScript -->
    <script>
          // Passar variáveis PHP para JavaScript - VERIFICAR SE JÁ EXISTE
    window.appConfig = Object.assign({
            comandaAtualId: null,
            basePath: <?= json_encode($base_path) ?>,
            apiBase: <?= json_encode(rtrim(PathConfig::api(), '/') . '/') ?>,
            createOrderUrl: <?= json_encode(PathConfig::modules('caixa/criar_comanda.php')) ?>,
            openOrdersUrl: <?= json_encode(PathConfig::api('comandas_abertas.php')) ?>,
            mapaMesasUrl: <?= json_encode(PathConfig::api('mapa_mesas.php')) ?>,
            novaComandaUrl: <?= json_encode(PathConfig::api('nova_comanda.php')) ?>
        }, window.appConfig || {});
    window.appConfig.erroConfigMapaMesas = false;
    window.appConfig.usarMapaMesas = <?php
        $usar_mapa_mesas_caixa = false;
        $erro_config_mapa_mesas_caixa = false;
        try {
            $s = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'usar_mapa_mesas'");
            $s->execute();
            $usar_mapa_mesas_caixa = $s->fetchColumn() === '1';
        } catch (Exception $e) {
            error_log('Erro ao carregar configuração do mapa de mesas no caixa: ' . $e->getMessage());
            $erro_config_mapa_mesas_caixa = true;
        }
        echo $usar_mapa_mesas_caixa ? 'true' : 'false';
    ?>;
    window.appConfig.erroConfigMapaMesas = <?= $erro_config_mapa_mesas_caixa ? 'true' : 'false' ?>;
    </script>   

        <!-- Modal para definir se a nova comanda será aberta pelo caixa ou por um garçom. -->
    <div id="modalGarcom" class="cash-modal" role="dialog" aria-modal="true" aria-labelledby="modalGarcomTitle" aria-hidden="true" style="display: none;">
        <div class="cash-modal-card">
            <h2 id="modalGarcomTitle" class="cash-modal-title"><i class="bi bi-person-badge" aria-hidden="true"></i> Como identificar a comanda?</h2>
            <p class="cash-modal-description">Este vínculo não define comissão. Cada item será atribuído ao garçom autenticado que o lançar.</p>
            
            <div id="gridGarcons" class="cash-waiter-grid">
                <button type="button" class="btn-garcom btn-caixa" id="btnAbrirComandaCaixa">
                    <span class="cash-waiter-code"><i class="bi bi-cash-register" aria-hidden="true"></i> Caixa</span>
                    <span class="cash-waiter-name">Abrir comanda manual</span>
                </button>
                <?php if (!empty($garcons)): ?>
                    <?php foreach($garcons as $garcom): ?>
                    <button type="button" class="btn-garcom" data-garcom="<?= htmlspecialchars((string) $garcom['codigo'], ENT_QUOTES, 'UTF-8') ?>" data-garcom-name="<?= htmlspecialchars($garcom['nome'], ENT_QUOTES, 'UTF-8') ?>" aria-pressed="false">
                        <span class="cash-waiter-code"><?= htmlspecialchars($garcom['codigo']) ?></span>
                        <span class="cash-waiter-name"><?= htmlspecialchars($garcom['nome']) ?></span>
                    </button>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="cash-waiter-empty">
                        <p>Nenhum garçom cadastrado</p>
                        <button type="button" class="cash-button cash-button--secondary" onclick="fecharModalGarcom()">
                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                            Fechar
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cash-modal-actions">
                <button type="button" class="cash-button cash-button--secondary" onclick="fecharModalGarcom()">
                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL COMPROVANTE -->
    <div id="modalComprovante" class="cash-modal" role="dialog" aria-modal="true" aria-labelledby="modalComprovanteTitle" aria-hidden="true" style="display: none;">
        <div class="cash-modal-card cash-modal-card--receipt">
            <h2 id="modalComprovanteTitle" class="cash-modal-title"><i class="bi bi-check-circle" aria-hidden="true"></i> Venda finalizada</h2>
            <p id="totalVenda" class="cash-modal-total">Total: <strong>R$ 0,00</strong></p>
            <p class="cash-modal-description">Deseja imprimir o comprovante?</p>
            
            <div class="cash-modal-actions cash-modal-actions--center">
                <button id="btnImprimirComprovante" class="cash-button cash-button--primary">
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    Imprimir
                </button>
                <button id="btnPularImpressao" class="cash-button cash-button--secondary">
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    Não imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript do Modal -->
    <script>
        // Variável global para armazenar o garçom selecionado
        let garcomSelecionado = null;
        let garcomNomeSelecionado = null;

        function notificarCaixa(mensagem, tipo) {
            if (window.caixaSystem && typeof window.caixaSystem.mostrarToast === 'function') {
                window.caixaSystem.mostrarToast(mensagem, tipo);
                return;
            }
            alert(mensagem);
        }

        function abrirModalGarcom() {
            const modal = document.getElementById('modalGarcom');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            garcomSelecionado = null;
            garcomNomeSelecionado = null;
            // Resetar seleção visual
            document.querySelectorAll('.btn-garcom').forEach(btn => {
                btn.classList.remove('is-selected');
                btn.setAttribute('aria-pressed', 'false');
            });
        }

        function fecharModalGarcom() {
            const modal = document.getElementById('modalGarcom');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            garcomSelecionado = null;
            garcomNomeSelecionado = null;
        }

        function selecionarGarcom(codigo, nome) {
            garcomSelecionado = codigo;
            garcomNomeSelecionado = nome;
            
            // Efeito visual de seleção
            document.querySelectorAll('.btn-garcom').forEach(btn => {
                const selecionado = btn.getAttribute('data-garcom') === codigo;
                btn.classList.toggle('is-selected', selecionado);
                btn.setAttribute('aria-pressed', String(selecionado));
            });
            
            // Criar comanda automaticamente após 300ms (feedback visual)
            setTimeout(() => {
                criarComandaComGarcom();
            }, 300);
        }

        document.getElementById('gridGarcons').addEventListener('click', function(event) {
            const button = event.target.closest('.btn-garcom');
            if (button && button.dataset.garcom) {
                selecionarGarcom(button.dataset.garcom, button.dataset.garcomName);
            }
        });

        document.getElementById('btnAbrirComandaCaixa').addEventListener('click', criarComandaCaixa);

        async function criarComandaCaixa(event) {
            const button = event.currentTarget;
            if (button.disabled) return;
            if (!window.caixaSystem || typeof window.caixaSystem.atualizarUIComanda !== 'function') {
                notificarCaixa('O caixa ainda está carregando. Tente novamente.', 'warning');
                return;
            }
            button.disabled = true;
            try {
                const response = await fetch(window.appConfig.apiBase + 'nova_comanda.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (!response.ok || !data.success || !data.comanda_id) {
                    throw new Error(data.message || 'Não foi possível abrir uma comanda pelo caixa.');
                }

                window.caixaSystem.comandaAtual = { id: Number(data.comanda_id) };
                window.caixaSystem.itensComanda = [];
                window.caixaSystem.totalComanda = 0;
                window.caixaSystem.taxaGorjeta = 0;
                window.caixaSystem.totalAPagar = 0;
                window.caixaSystem.atualizarUIComanda();
                if (window.comandasAbertasUI) window.comandasAbertasUI.load();
                fecharModalGarcom();
                notificarCaixa(data.message || 'Comanda aberta pelo caixa.', 'success');
            } catch (error) {
                console.error('Erro ao abrir comanda pelo caixa:', error);
                notificarCaixa(error.message || 'Não foi possível abrir uma comanda pelo caixa.', 'error');
            } finally {
                button.disabled = false;
            }
        }

        async function criarComandaComGarcom() {
            if (!garcomSelecionado) {
                notificarCaixa('Selecione um garçom para continuar.', 'warning');
                return;
            }
            
            try {
                // Fazer requisição direta
                const response = await fetch(window.appConfig.createOrderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        garcom: garcomSelecionado
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Atualizar CaixaSystem se disponível
                    if (window.caixaSystem) {
                        window.caixaSystem.comandaAtual = { id: data.comanda_id };
                        window.caixaSystem.itensComanda = [];
                        window.caixaSystem.totalComanda = 0;
                        window.caixaSystem.taxaGorjeta = 0;
                        window.caixaSystem.totalAPagar = 0;
                        window.caixaSystem.atualizarUIComanda();
                    }
                    if (window.comandasAbertasUI) {
                        window.comandasAbertasUI.load();
                    }
                    
                    fecharModalGarcom();
                    
                    // Mostrar toast se disponível
                    if (window.caixaSystem && typeof window.caixaSystem.mostrarToast === 'function') {
                        window.caixaSystem.mostrarToast(data.message, 'success');
                    }
                } else {
                    throw new Error(data.message || 'Erro ao criar comanda');
                }
            } catch (error) {
                console.error('Erro ao criar comanda:', error);
                notificarCaixa('Erro ao criar comanda: ' + error.message, 'error');
                fecharModalGarcom();
            }
        }

        // Função novaComanda (chamada pelo botão)
        function novaComanda() {
            if (window.comandasAbertasUI) {
                window.comandasAbertasUI.close();
            }

            if (window.appConfig && window.appConfig.erroConfigMapaMesas) {
                notificarCaixa('Não foi possível carregar a configuração do atendimento. Avise o administrador antes de abrir comandas.', 'error');
                return;
            }

            if (window.caixaSystem && window.caixaSystem.comandaAtual) {
                if (!confirm('A comanda atual continuará aberta. Deseja iniciar outra comanda?')) return;
            }

            if (window.appConfig && window.appConfig.usarMapaMesas) {
                const mapa = document.getElementById('mapaMesasCaixa');
                if (!mapa) {
                    notificarCaixa('O mapa de mesas está ativo, mas não foi possível carregá-lo. Atualize a página ou avise o administrador.', 'error');
                    return;
                }
                mapa.scrollIntoView({ behavior: 'smooth', block: 'center' });
                notificarCaixa('Selecione uma mesa livre no mapa para abrir uma comanda.', 'info');
                return;
            }

            abrirModalGarcom();
        }
    </script>

    <script>
        // Wrapper global para compatibilidade com os elementos inline do template
        // Chamado pelos onclick nos cards: adicionarProduto(produtoId, nome, preco)
        function adicionarProduto(produtoId, nome, preco) {
            if (window.caixaSystem && typeof window.caixaSystem.adicionarItem === 'function') {
                // O CaixaSystem exige uma comanda selecionada antes de adicionar itens.
                window.caixaSystem.adicionarItem(produtoId, 1);
                return;
            }

            // Fallback (CaixaSystem ainda não carregou)
            console.warn('CaixaSystem ainda não pronto. Tente novamente em alguns momentos.');
        }

        // Delegar chamadas de busca pra CaixaSystem se existir
        function filtrarProdutos() {
            const el = document.getElementById('search-produto') || document.getElementById('busca-produto');
            const termo = el ? el.value : '';
            if (window.caixaSystem && typeof window.caixaSystem.filtrarProdutos === 'function') {
                window.caixaSystem.filtrarProdutos(termo);
            }
        }

        // Delegar finalização de comanda pro CaixaSystem
        function finalizarComanda() {
            if (window.caixaSystem && typeof window.caixaSystem.finalizarComanda === 'function') {
                window.caixaSystem.finalizarComanda();
            } else {
                notificarCaixa('O caixa ainda está carregando. Tente novamente em alguns instantes.', 'warning');
            }
        }

        // Delegar cancelamento de comanda pro CaixaSystem
        function cancelarComanda() {
            if (window.caixaSystem && typeof window.caixaSystem.cancelarComanda === 'function') {
                window.caixaSystem.cancelarComanda();
            }
        }
    </script>

    <!-- Script de impressão -->
<script src="<?= htmlspecialchars(PathConfig::modules('caixa/impressao-service.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<script>
// Teste de compatibilidade Web USB
document.addEventListener('DOMContentLoaded', function() {
    if (navigator.usb) {
        console.log('Web USB API suportada');
        // Opcional: mostrar status da impressora
        const statusElement = document.createElement('div');
        statusElement.style.cssText = 'position: fixed; bottom: 10px; right: 10px; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; border: 1px solid #dee2e6;';
        statusElement.textContent = 'Web USB disponível';
        document.body.appendChild(statusElement);
    } else {
        console.warn('Web USB não suportada');
    }
});
</script>

    <script src="<?= htmlspecialchars(PathConfig::modules('caixa/caixa.js'), ENT_QUOTES, 'UTF-8') ?>?v=<?= time() ?>"></script>
    <script src="<?= htmlspecialchars(PathConfig::modules('caixa/comandas-abertas.js'), ENT_QUOTES, 'UTF-8') ?>?v=<?= filemtime(__DIR__ . '/comandas-abertas.js') ?>"></script>

    <?php if (!empty($usar_mapa_mesas_caixa)): ?>
    <style>
        #mapaMesasCaixa { padding: 12px 15px; border-bottom: 1px solid #e0e0e0; background: white; }
        #mapaMesasCaixa h3 { font-size: 0.9rem; color: #2c3e50; margin-bottom: 8px; }
        .mapa-canvas-caixa { position: relative; width: 100%; min-height: 300px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; overflow: auto; }
        .mesa-btn-caixa { position: absolute; width: 68px; height: 68px; border-radius: 8px; border: 2px solid; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; transition: transform .15s; background: white; }
        .mesa-btn-caixa:hover { transform: scale(1.07); }
        .mesa-btn-caixa.livre   { border-color: #27ae60; color: #27ae60; }
        .mesa-btn-caixa.ocupada { border-color: #e74c3c; color: #e74c3c; background: #fef2f2; }
        .mesa-btn-caixa small { font-size: 0.6rem; font-weight: 400; }
    </style>
    <div id="mapaMesasCaixa">
        <h3><i class="bi bi-grid-3x3-gap"></i> Mapa de Mesas</h3>
        <div class="mapa-canvas-caixa" id="mapaCanvasCaixa"></div>
    </div>
    <script>
    (function() {
        const canvas = document.getElementById('mapaCanvasCaixa');
        const cfg = window.appConfig;

        async function carregarMapaCaixa() {
            try {
                const r = await fetch(cfg.mapaMesasUrl);
                const d = await r.json();
                if (!r.ok || !d.success) {
                    throw new Error(d.message || 'Não foi possível carregar o mapa de mesas.');
                }
                canvas.replaceChildren();
                if (d.mesas.length === 0) {
                    const empty = document.createElement('p');
                    empty.setAttribute('role', 'status');
                    empty.textContent = 'Nenhuma mesa ativa cadastrada.';
                    canvas.appendChild(empty);
                    return;
                }
                let maxX = 0, maxY = 0;
                d.mesas.forEach(m => {
                    if (m.posicao_x + 86 > maxX) maxX = m.posicao_x + 86;
                    if (m.posicao_y + 86 > maxY) maxY = m.posicao_y + 86;
                });
                canvas.style.minWidth  = Math.max(maxX, 400) + 'px';
                canvas.style.minHeight = Math.max(maxY, 300) + 'px';
                d.mesas.forEach(m => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `mesa-btn-caixa ${m.status_mesa}`;
                    btn.style.left = m.posicao_x + 'px';
                    btn.style.top  = m.posicao_y + 'px';
                    const esc = v => { const s = document.createElement('span'); s.textContent = v ?? ''; return s.innerHTML; };
                    btn.innerHTML = `<span>${esc(m.numero_mesa)}</span><small>${esc(m.ambiente)}</small>`;
                    btn.setAttribute('aria-label', `Mesa ${m.numero_mesa} – ${m.status_mesa}`);
                    btn.addEventListener('click', () => clicarMesaCaixa(m).catch(error =>
                        notificarCaixa(error.message || 'Não foi possível abrir esta mesa.', 'error')
                    ));
                    canvas.appendChild(btn);
                });
            } catch(e) {
                const error = document.createElement('p');
                error.setAttribute('role', 'alert');
                error.textContent = e.message || 'Não foi possível carregar o mapa de mesas.';
                canvas.replaceChildren(error);
            }
        }

        async function clicarMesaCaixa(mesa) {
            if (mesa.status_mesa === 'livre') {
                if (!window.caixaSystem) { alert('Caixa ainda carregando.'); return; }
                try {
                    const r = await fetch(cfg.novaComandaUrl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ mesa_id: mesa.mesa_id })
                    });
                    const d = await r.json();
                    if (!r.ok || !d.success) throw new Error(d.message || 'Não foi possível abrir a comanda.');
                    window.caixaSystem.comandaAtual = { id: Number(d.comanda_id) };
                    window.caixaSystem.itensComanda = [];
                    window.caixaSystem.totalComanda = 0;
                    window.caixaSystem.taxaGorjeta  = 0;
                    window.caixaSystem.totalAPagar  = 0;
                    window.caixaSystem.atualizarUIComanda();
                    if (window.comandasAbertasUI) window.comandasAbertasUI.load();
                    carregarMapaCaixa();
                } catch(e) { alert(e.message); }
            } else {
                // Mesa ocupada: abrir painel de comandas filtrado
                const panel = document.getElementById('open-orders-panel');
                const search = document.getElementById('search-open-orders');
                if (panel && search) {
                    if (window.comandasAbertasUI) await window.comandasAbertasUI.load();
                    search.value = mesa.numero_mesa;
                    search.dispatchEvent(new Event('input'));
                    if (panel.getAttribute('aria-hidden') === 'true') {
                        document.getElementById('toggle-open-orders')?.click();
                    }
                }
            }
        }

        carregarMapaCaixa();
        setInterval(carregarMapaCaixa, 15000);

        // Atualizar mapa após finalizar comanda
        document.addEventListener('comandaFinalizada', carregarMapaCaixa);
    })();
    </script>
    <?php endif; ?>
    
    <script>
    // Event listeners para modal de comprovante
    document.addEventListener('DOMContentLoaded', function() {
        const btnImprimir = document.getElementById('btnImprimirComprovante');
        const btnPular = document.getElementById('btnPularImpressao');
        
        if (btnImprimir) {
            btnImprimir.addEventListener('click', function() {
                const modal = document.getElementById('modalComprovante');
                const comprovanteId = modal ? modal.dataset.comprovanteId : null;
                
                if (comprovanteId && window.caixaSystem) {
                    window.caixaSystem.imprimirComprovante(comprovanteId);
                }
                
                if (window.caixaSystem) {
                    window.caixaSystem.fecharModalComprovante();
                }
            });
        }
        
        if (btnPular) {
            btnPular.addEventListener('click', function() {
                if (window.caixaSystem) {
                    window.caixaSystem.fecharModalComprovante();
                }
            });
        }
    });
    </script>
    
    <script>
// SISTEMA DE EMERGÊNCIA - Garante funcionamento mesmo com versão antiga
console.log('Verificando serviço de impressão...');

// Aguardar carregamento completo
setTimeout(function() {
    // Se o serviço não existe ou tem métodos USB, substituir
    if (!window.impressaoService || window.impressaoService.conectarImpressora) {
        console.log('Substituindo serviço problemático...');
        
        // Remover serviço antigo
        if (window.impressaoService) {
            delete window.impressaoService;
        }
        
        // Criar serviço novo
        window.impressaoService = {
            imprimirComprovante: function(conteudo) {
                console.log('Imprimindo em modo de emergência.');
                try {
                    const texto = conteudo.replace(/\x1B\[[0-9;]*[A-Za-z]/g, '').replace(/\x0A/g, '\n');
                    const janela = window.open('', '_blank', 'width=400,height=600');
                    if (janela) {
                        janela.document.write('<h3>Espetinho do Junior</h3><pre>' + texto + '</pre><button onclick="window.print()">Imprimir</button>');
                        janela.document.close();
                    }
                    return Promise.resolve({success: true, message: 'Comprovante aberto!'});
                } catch (error) {
                    return Promise.resolve({success: false, message: 'Erro: ' + error.message});
                }
            }
        };
        
        console.log('Serviço de emergência ativado.');
    } else {
        console.log('Serviço OK.');
    }
}, 1000);
</script>
    <script src="https://cdn.jsdelivr.net/npm/react@18.3.1/umd/react.production.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@18.3.1/umd/react-dom.production.min.js" crossorigin="anonymous"></script>
    <script src="<?= htmlspecialchars(PathConfig::url('js/react-app.js'), ENT_QUOTES, 'UTF-8') ?>?v=<?= filemtime(__DIR__ . '/../../js/react-app.js') ?>"></script>
</body>
</html>