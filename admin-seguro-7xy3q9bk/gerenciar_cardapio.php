<?php
require_once __DIR__ . '/includes/cardapio-auth.php';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/includes/menu-images.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados do caixa.');
    }

    $stmt = $db->query(
        "SELECT p.id, p.categoria_id, p.nome, p.nome_publico, p.descricao_publica, p.preco,
                p.imagem_url,
                p.estoque_minimo, p.estoque_atual, p.imagem, c.nome AS categoria_nome
         FROM produtos p
         INNER JOIN categorias c ON c.id = p.categoria_id
         WHERE p.ativo = 1
         ORDER BY c.nome, p.nome"
    );
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query(
        'SELECT id, produto_id, nome_exibicao, descricao, imagem, imagem_url, ordem
         FROM produto_cards
         ORDER BY produto_id, ordem, id'
    );
    $opcoesPorProduto = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $opcao) {
        $imagemPreview = menuResolveImageSource(
            $opcao['imagem'] ?? '',
            $opcao['imagem_url'] ?? '',
            __DIR__ . '/../public/images/menu',
            '../public/images/menu/'
        );
        $opcoesPorProduto[(int) $opcao['produto_id']][] = [
            'id' => (int) $opcao['id'],
            'nome_exibicao' => $opcao['nome_exibicao'],
            'descricao' => $opcao['descricao'] ?? '',
            'imagem' => $opcao['imagem'] ?? '',
            'imagem_url' => $opcao['imagem_url'] ?? '',
            'imagem_preview' => $imagemPreview
        ];
    }
} catch (Throwable $e) {
    error_log('Erro ao carregar gerenciador do cardápio: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar os produtos do cardápio. Confira a conexão e execute migracao_cardapio_integrado.sql no banco do caixa, se ainda não foi aplicada.');
}

if (!isset($_SESSION['csrf_produto'])) {
    $_SESSION['csrf_produto'] = bin2hex(random_bytes(32));
}

$mensagem = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';
$produtosPorCategoria = [];
foreach ($produtos as $produto) {
    $categoriaId = (int) $produto['categoria_id'];
    if (!isset($produtosPorCategoria[$categoriaId])) {
        $produtosPorCategoria[$categoriaId] = [
            'nome' => $produto['categoria_nome'],
            'produtos' => []
        ];
    }
    $produtosPorCategoria[$categoriaId]['produtos'][] = $produto;
}

$tituloPagina = 'Editar cardápio público';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/includes/functions.php';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/includes/header.php';
?>
<div class="menu-admin">
    <style>
        .menu-admin { padding: 2rem 0 3rem; color: #1e293b; }
        .menu-admin [hidden] { display: none !important; }
        .menu-admin h1, .menu-admin h2, .menu-admin h3 { color: #0f172a; font-weight: 700; }
        .menu-admin h1 { margin: 0 0 .5rem; font-size: 1.875rem; }
        .menu-admin h2 { margin: 0; font-size: 1.25rem; }
        .menu-admin p { line-height: 1.5; }
        .menu-admin .page-heading { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 1.5rem; }
        .menu-admin .page-icon { display: grid; width: 48px; height: 48px; flex: 0 0 auto; place-items: center; border-radius: 14px; background: #fff7ed; color: #ea580c; }
        .menu-admin .page-description, .menu-admin .muted { margin: 0; color: #64748b; }
        .menu-admin .notice, .menu-admin .error, .menu-admin .warning { margin: 1rem 0; padding: 14px 16px; border-radius: 10px; }
        .menu-admin .notice { border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
        .menu-admin .error { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; }
        .menu-admin .warning { border: 1px solid #fde68a; background: #fffbeb; color: #92400e; }
        .menu-admin .filter-panel { margin: 1.5rem 0; padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; box-shadow: 0 1px 3px #0f172a0a; }
        .menu-admin .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .menu-admin .toolbar-summary { color: #64748b; font-size: .9rem; }
        .menu-admin .filter-controls { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(220px, 1fr); gap: 12px; }
        .menu-admin .filter-field { position: relative; }
        .menu-admin .filter-field i { position: absolute; top: 50%; left: 13px; color: #94a3b8; transform: translateY(-50%); pointer-events: none; }
        .menu-admin input, .menu-admin select, .menu-admin textarea { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #1e293b; font: inherit; }
        .menu-admin .filter-field input { padding-left: 40px; }
        .menu-admin input:focus, .menu-admin select:focus, .menu-admin textarea:focus { border-color: #f97316; outline: 3px solid #fed7aa80; }
        .menu-admin button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 9px 14px; border: 0; border-radius: 8px; background: #f97316; color: #fff; font: inherit; font-weight: 700; cursor: pointer; transition: background .2s ease, transform .2s ease; }
        .menu-admin button:hover { background: #ea580c; transform: translateY(-1px); }
        .menu-admin button.secondary { background: #e2e8f0; color: #334155; }
        .menu-admin button.secondary:hover { background: #cbd5e1; }
        .menu-admin button.danger { background: #dc2626; }
        .menu-admin button.danger:hover { background: #b91c1c; }
        .menu-admin button:disabled { opacity: .6; cursor: wait; transform: none; }
        .menu-admin .category-section { margin: 1.75rem 0; }
        .menu-admin .category-heading { display: flex; align-items: center; gap: 10px; margin: 0 0 12px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; }
        .menu-admin .category-heading::before { width: 4px; height: 24px; border-radius: 4px; background: #f97316; content: ''; }
        .menu-admin .category-count { margin-left: auto; color: #64748b; font-size: .85rem; font-weight: 500; }
        .menu-admin .product-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); gap: 12px; }
        .menu-admin .product { display: grid; grid-template-columns: 76px minmax(0, 1fr) auto; align-items: center; gap: 14px; min-width: 0; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: 0 1px 2px #0f172a08; transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
        .menu-admin .product:hover { transform: translateY(-2px); border-color: #fed7aa; box-shadow: 0 8px 20px #0f172a0d; }
        .menu-admin .product > img { width: 76px; height: 68px; border-radius: 9px; object-fit: cover; background: #f1f5f9; }
        .menu-admin .product-main { min-width: 0; }
        .menu-admin .product-name { overflow: hidden; margin: 0 0 5px; color: #0f172a; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .menu-admin .product-meta { margin: 0; color: #64748b; font-size: .86rem; }
        .menu-admin .product .edit-product { min-height: 38px; padding: 8px 12px; }
        .menu-admin .empty { padding: 28px; border: 1px dashed #cbd5e1; border-radius: 12px; background: #fff; color: #64748b; text-align: center; }
        .menu-admin dialog { width: min(680px, calc(100% - 24px)); max-height: 90vh; padding: 0; border: 0; border-radius: 14px; box-shadow: 0 20px 60px #0f172a40; }
        .menu-admin dialog::backdrop { background: #0f172a80; }
        .menu-admin .editor { padding: 22px; overflow-y: auto; }
        .menu-admin .editor-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .menu-admin .editor-header h2 { margin: 0 0 5px; }
        .menu-admin .close-button { padding: 4px 10px; background: transparent; color: #475569; font-size: 1.4rem; }
        .menu-admin .field { display: grid; gap: 6px; margin: 14px 0; }
        .menu-admin .field label, .menu-admin .option label { font-weight: 700; }
        .menu-admin .field small, .menu-admin .section-help { color: #64748b; }
        .menu-admin .product-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 12px; border-radius: 8px; background: #f8fafc; }
        .menu-admin .product-details p { margin: 0; }
        .menu-admin .options-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 20px; }
        .menu-admin .options-heading h3 { margin: 0; }
        .menu-admin .option { margin: 12px 0; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .menu-admin .option-image { display: flex; align-items: center; gap: 12px; margin-top: 10px; }
        .menu-admin .option-image img { width: 68px; height: 56px; border-radius: 6px; object-fit: cover; background: #e5e7eb; }
        .menu-admin .option-search { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .menu-admin .option-search a { display: inline-flex; border-radius: 6px; padding: 9px 12px; background: #e2e8f0; color: #334155; text-decoration: none; font-weight: 700; }
        .menu-admin .product-image-preview { width: 140px; height: 110px; border-radius: 7px; object-fit: cover; background: #e5e7eb; }
        .menu-admin .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }
        @media (max-width: 640px) {
            .menu-admin { padding: 1.25rem 0 2rem; }
            .menu-admin .filter-controls { grid-template-columns: 1fr; }
            .menu-admin .toolbar { align-items: flex-start; flex-direction: column; }
            .menu-admin .product { grid-template-columns: 60px minmax(0, 1fr); gap: 10px; }
            .menu-admin .product > img { width: 60px; height: 58px; }
            .menu-admin .product .edit-product { grid-column: 1 / -1; }
            .menu-admin .product-details { grid-template-columns: 1fr; }
            .menu-admin .option-image { align-items: stretch; flex-direction: column; }
            .menu-admin .option-image img { width: 100%; height: 140px; }
        }
    </style>
    <header class="page-heading">
        <span class="page-icon" aria-hidden="true"><i class="bi bi-shop"></i></span>
        <div>
            <h1>Cardápio público</h1>
            <p class="page-description">Edite a apresentação dos produtos ativos. Nome, preço e categoria oficiais vêm do sistema de caixa.</p>
        </div>
    </header>

    <p class="warning">
        As opções são subdivisões visuais do produto: não criam produtos, preços ou estoque. Todas compartilham o preço do produto no caixa.
    </p>

    <?php if ($mensagem !== ''): ?>
        <p class="notice"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p class="error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <section class="filter-panel" aria-label="Filtros de produtos">
        <div class="toolbar">
            <h2>Produtos que aparecem no cardápio</h2>
            <span class="toolbar-summary" id="visibleProductCount"><?= count($produtos) ?> <?= count($produtos) === 1 ? 'produto' : 'produtos' ?></span>
        </div>
        <div class="filter-controls">
            <label class="filter-field">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="searchProducts" type="search" placeholder="Buscar por nome, descrição ou categoria..." aria-label="Buscar produtos">
            </label>
            <label>
                <span class="visually-hidden">Filtrar por categoria</span>
                <select id="categoryFilter" aria-label="Filtrar por categoria">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($produtosPorCategoria as $categoriaId => $categoria): ?>
                        <option value="<?= (int) $categoriaId ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </section>

    <?php if ($produtos): ?>
        <div id="productList">
            <?php foreach ($produtosPorCategoria as $categoriaId => $categoria): ?>
                <section class="category-section" data-category-section="<?= (int) $categoriaId ?>">
                    <h2 class="category-heading">
                        <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="category-count"><?= count($categoria['produtos']) ?> <?= count($categoria['produtos']) === 1 ? 'produto' : 'produtos' ?></span>
                    </h2>
                    <div class="product-list">
                    <?php foreach ($categoria['produtos'] as $produto): ?>
                <?php
                $produtoId = (int) $produto['id'];
                $opcoes = $opcoesPorProduto[$produtoId] ?? [];
                $imagemUrl = menuResolveImageSource(
                    $produto['imagem'] ?? '',
                    $produto['imagem_url'] ?? '',
                    __DIR__ . '/../public/images/menu',
                    '../public/images/menu/'
                );
                $dadosProduto = [
                    'id' => $produtoId,
                    'categoria_id' => (int) $produto['categoria_id'],
                    'nome' => $produto['nome'],
                    'nome_publico' => $produto['nome_publico'] ?? '',
                    'descricao_publica' => $produto['descricao_publica'] ?? '',
                    'preco' => $produto['preco'],
                    'categoria' => $produto['categoria_nome'],
                    'estoque_minimo' => $produto['estoque_minimo'],
                    'estoque_atual' => $produto['estoque_atual'],
                    'imagem' => $produto['imagem'] ?? '',
                    'imagem_url' => $imagemUrl,
                    'imagem_url_original' => $produto['imagem_url'] ?? '',
                    'opcoes' => $opcoes
                ];
                $jsonProduto = json_encode($dadosProduto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                ?>
                <?php
                $termoPesquisa = $produto['nome'] . ' ' . ($produto['nome_publico'] ?? '') . ' '
                    . ($produto['descricao_publica'] ?? '') . ' ' . $produto['categoria_nome'];
                $termoPesquisa = function_exists('mb_strtolower')
                    ? mb_strtolower($termoPesquisa, 'UTF-8')
                    : strtolower($termoPesquisa);
                ?>
                <article class="product" data-search="<?= htmlspecialchars($termoPesquisa, ENT_QUOTES, 'UTF-8') ?>" data-category="<?= (int) $categoriaId ?>">
                    <img src="<?= htmlspecialchars($imagemUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" onerror="this.onerror=null; this.src='../public/images/menu/default.png'">
                    <div class="product-main">
                        <div>
                            <p class="product-name"><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="product-meta">
                                <?= htmlspecialchars($produto['categoria_nome'], ENT_QUOTES, 'UTF-8') ?>
                                · R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?>
                                · <?= count($opcoes) ?> <?= count($opcoes) === 1 ? 'subcategoria' : 'subcategorias' ?>
                            </p>
                            <?php if (!empty($produto['nome_publico'])): ?>
                                <p class="product-meta">Nome no cardápio: <?= htmlspecialchars($produto['nome_publico'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="edit-product" data-product="<?= htmlspecialchars($jsonProduto, ENT_QUOTES, 'UTF-8') ?>">Editar</button>
                </article>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p class="empty" id="noSearchResults" hidden>Nenhum produto corresponde aos filtros selecionados.</p>
    <?php else: ?>
        <p class="empty">Não há produtos ativos para exibir. Ative os produtos no estoque para que apareçam aqui e no cardápio público.</p>
    <?php endif; ?>
<dialog id="productEditor" aria-labelledby="editorTitle">
    <form class="editor" id="productEditorForm">
        <div class="editor-header">
            <div>
                <h2 id="editorTitle">Editar apresentação</h2>
                <p class="help">O produto, o preço e a categoria não são alterados por este formulário.</p>
            </div>
            <button type="button" class="close-button" data-close-editor aria-label="Fechar">×</button>
        </div>

        <input type="hidden" id="productId">
        <div class="product-details">
            <p><strong>Produto:</strong> <span id="officialName"></span></p>
            <p><strong>Preço:</strong> <span id="officialPrice"></span></p>
            <p><strong>Categoria:</strong> <span id="officialCategory"></span></p>
        </div>

        <div class="field">
            <label for="publicName">Nome no cardápio (opcional)</label>
            <input id="publicName" type="text" maxlength="100">
        </div>
        <div class="field">
            <label for="publicDescription">Descrição (opcional)</label>
            <textarea id="publicDescription" rows="3" maxlength="1000"></textarea>
        </div>
        <div class="field">
            <label for="productImage">Imagem do produto (opcional, JPG/PNG/GIF/WebP, até 5 MB)</label>
            <img class="product-image-preview" id="productImagePreview" src="../public/images/menu/default.png" alt="Prévia da imagem do produto" onerror="this.onerror=null; this.src='../public/images/menu/default.png'">
            <input id="productImage" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
            <div class="option-search">
                <label for="productImageUrl">Ou importar uma URL HTTPS direta de imagem</label>
                <input id="productImageUrl" type="url" maxlength="2048" placeholder="https://site.com/imagem.jpg">
                <a id="productGoogleImageSearch" href="#" target="_blank" rel="noopener noreferrer">Pesquisar no Google Imagens</a>
            </div>
            <small>A imagem adicionada por último, seja arquivo ou link, será usada no cardápio. Sem imagem, será exibida a imagem padrão. Na busca, copie o endereço direto do arquivo. Use apenas imagens que tenha autorização para publicar.</small>
        </div>

        <section>
            <div class="options-heading">
                <h3>Subcategorias do produto</h3>
                <button type="button" class="secondary" id="addOption">Adicionar</button>
            </div>
            <p class="section-help">Use para listar sabores ou apresentações. Cada subcategoria pode ter foto própria, mas compartilha produto e preço. A imagem adicionada por último, seja arquivo ou link, será usada; sem imagem, aparece a imagem padrão. Use apenas imagens que tenha autorização para publicar.</p>
            <div id="productOptions"></div>
        </section>

        <p class="error" id="editorError" hidden></p>
        <div class="modal-actions">
            <button type="button" class="secondary" data-close-editor>Cancelar</button>
            <button type="submit" id="saveProduct">Salvar apresentação</button>
        </div>
    </form>
</dialog>
</div>

<script>
(() => {
    const editor = document.getElementById('productEditor');
    const form = document.getElementById('productEditorForm');
    const optionsContainer = document.getElementById('productOptions');
    const errorBox = document.getElementById('editorError');
    let currentProduct = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[character]));

    function addOption(option = {}) {
        const row = document.createElement('div');
        row.className = 'option';
        row.dataset.id = option.id || '';
        row.dataset.imageSource = '';
        row.innerHTML = `
            <div class="field">
                <label>Nome da subcategoria</label>
                <input class="option-name" type="text" maxlength="100" required value="${escapeHtml(option.nome_exibicao || '')}">
            </div>
            <div class="field">
                <label>Descrição (opcional)</label>
                <textarea class="option-description" rows="2" maxlength="1000">${escapeHtml(option.descricao || '')}</textarea>
            </div>
            <div class="option-image">
                <img src="${escapeHtml(option.imagem_preview || '../public/images/menu/default.png')}" alt="Prévia da imagem da subcategoria" onerror="this.onerror=null; this.src='../public/images/menu/default.png'">
                <label class="field">
                    Foto própria da subcategoria (JPG, PNG, GIF ou WebP; até 5 MB)
                    <input class="option-file" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                </label>
            </div>
            <div class="field option-search">
                <label>Ou importar uma URL HTTPS direta de imagem</label>
                <input class="option-image-url" type="url" maxlength="2048" placeholder="https://site.com/imagem.jpg">
                <a class="google-image-search" href="#" target="_blank" rel="noopener noreferrer">Pesquisar no Google Imagens</a>
                <small>Na busca, abra a imagem, copie o endereço direto do arquivo e cole aqui. Links de páginas de resultados/redirecionamentos não são aceitos.</small>
            </div>
            <button type="button" class="danger remove-option">Remover subcategoria</button>
        `;
        row.querySelector('.remove-option').addEventListener('click', () => row.remove());
        row.querySelector('.google-image-search').addEventListener('click', (event) => {
            const optionName = row.querySelector('.option-name').value.trim();
            if (!optionName) {
                event.preventDefault();
                row.querySelector('.option-name').focus();
                return;
            }
            const query = `${currentProduct.nome} ${optionName}`;
            event.currentTarget.href = `https://www.google.com/search?tbm=isch&q=${encodeURIComponent(query)}`;
        });
        row.querySelector('.option-file').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                row.dataset.imageSource = 'arquivo';
                row.querySelector('.option-image-url').value = '';
                row.querySelector('.option-image img').src = URL.createObjectURL(file);
            }
        });
        row.querySelector('.option-image-url').addEventListener('input', (event) => {
            row.dataset.imageSource = 'url';
            row.querySelector('.option-file').value = '';
            row.querySelector('.option-image img').src = event.target.value.trim()
                || '../public/images/menu/default.png';
        });
        const externalImageUrl = row.querySelector('.option-image-url');
        externalImageUrl.value = option.imagem_url || '';
        optionsContainer.append(row);
    }

    document.querySelectorAll('.edit-product').forEach((button) => {
        button.addEventListener('click', () => {
            currentProduct = JSON.parse(button.dataset.product);
            form.reset();
            form.dataset.imageSource = '';
            errorBox.hidden = true;
            optionsContainer.replaceChildren();

            document.getElementById('productId').value = currentProduct.id;
            document.getElementById('officialName').textContent = currentProduct.nome;
            document.getElementById('officialPrice').textContent =
                'R$ ' + Number(currentProduct.preco).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            document.getElementById('officialCategory').textContent = currentProduct.categoria;
            document.getElementById('publicName').value = currentProduct.nome_publico;
            document.getElementById('publicDescription').value = currentProduct.descricao_publica;
            document.getElementById('productImagePreview').src = currentProduct.imagem_url;
            document.getElementById('productImageUrl').value = currentProduct.imagem_url_original;
            currentProduct.opcoes.forEach(addOption);
            editor.showModal();
        });
    });

    document.getElementById('productGoogleImageSearch').addEventListener('click', (event) => {
        const query = currentProduct ? currentProduct.nome : '';
        if (!query) {
            event.preventDefault();
            return;
        }
        event.currentTarget.href = `https://www.google.com/search?tbm=isch&q=${encodeURIComponent(query)}`;
    });

    document.getElementById('productImage').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            form.dataset.imageSource = 'arquivo';
            document.getElementById('productImageUrl').value = '';
            document.getElementById('productImagePreview').src = URL.createObjectURL(file);
        }
    });

    document.getElementById('productImageUrl').addEventListener('input', (event) => {
        form.dataset.imageSource = 'url';
        document.getElementById('productImage').value = '';
        document.getElementById('productImagePreview').src = event.target.value.trim()
            || '../public/images/menu/default.png';
    });

    document.querySelectorAll('[data-close-editor]').forEach((button) => {
        button.addEventListener('click', () => editor.close());
    });

    document.getElementById('addOption').addEventListener('click', () => addOption());

    const searchProducts = document.getElementById('searchProducts');
    const categoryFilter = document.getElementById('categoryFilter');
    const visibleProductCount = document.getElementById('visibleProductCount');
    const normalizeSearch = (value) => String(value)
        .toLocaleLowerCase('pt-BR')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    function filterProducts() {
        const query = normalizeSearch(searchProducts.value.trim());
        const selectedCategory = categoryFilter.value;
        let visibleCount = 0;
        document.querySelectorAll('.category-section').forEach((section) => {
            let categoryCount = 0;
            section.querySelectorAll('.product').forEach((product) => {
                const matchesSearch = normalizeSearch(product.dataset.search).includes(query);
                const matchesCategory = !selectedCategory || product.dataset.category === selectedCategory;
                const matches = matchesSearch && matchesCategory;
                product.hidden = !matches;
                if (matches) {
                    visibleCount++;
                    categoryCount++;
                }
            });
            section.hidden = categoryCount === 0;
            const countLabel = section.querySelector('.category-count');
            countLabel.textContent = `${categoryCount} ${categoryCount === 1 ? 'produto' : 'produtos'}`;
        });
        visibleProductCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'produto' : 'produtos'}`;
        const noResults = document.getElementById('noSearchResults');
        if (noResults) noResults.hidden = visibleCount > 0;
    }

    searchProducts.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        const saveButton = document.getElementById('saveProduct');
        saveButton.disabled = true;
        saveButton.textContent = 'Salvando...';

        const data = new FormData();
        data.append('id', String(currentProduct.id));
        data.append('nome', currentProduct.nome);
        data.append('categoria_id', String(currentProduct.categoria_id || ''));
        data.append('preco', String(currentProduct.preco));
        data.append('estoque_minimo', String(currentProduct.estoque_minimo));
        data.append('estoque_inicial', String(currentProduct.estoque_atual));
        data.append('csrf_token', <?= json_encode($_SESSION['csrf_produto']) ?>);
        data.append('nome_publico', document.getElementById('publicName').value);
        data.append('descricao_publica', document.getElementById('publicDescription').value);

        const image = document.getElementById('productImage').files[0];
        if (image) data.append('imagem', image);
        data.append('imagem_url', document.getElementById('productImageUrl').value.trim());
        data.append('imagem_origem', form.dataset.imageSource || '');

        const options = Array.from(optionsContainer.querySelectorAll('.option')).map((row) => ({
                id: row.dataset.id || null,
                nome_exibicao: row.querySelector('.option-name').value,
                descricao: row.querySelector('.option-description').value,
                imagem_url: row.querySelector('.option-image-url').value.trim(),
                imagem_origem: row.dataset.imageSource || ''
            }));
        data.append('opcoes_json', JSON.stringify(options));
        options.forEach((option, index) => {
            const imageFile = optionsContainer.querySelectorAll('.option-file')[index].files[0];
            if (imageFile) {
                data.append(`opcao_imagem_${index}`, imageFile);
            }
        });

        try {
            const response = await fetch('../caixa-seguro-7xy3q9kkle/api/salvar_produto.php', {
                method: 'POST',
                body: data
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Não foi possível salvar as alterações.');
            }

            editor.close();
            window.location.reload();
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'Salvar apresentação';
        }
    });
})();
</script>
<?php require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/includes/footer.php'; ?>
