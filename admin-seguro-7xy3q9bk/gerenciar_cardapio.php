<?php
require_once __DIR__ . '/includes/cardapio-auth.php';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados do caixa.');
    }

    $stmt = $db->query(
        "SELECT p.id, p.categoria_id, p.nome, p.nome_publico, p.descricao_publica, p.preco,
                p.estoque_minimo, p.estoque_atual, p.imagem, c.nome AS categoria_nome
         FROM produtos p
         INNER JOIN categorias c ON c.id = p.categoria_id
         WHERE p.ativo = 1
         ORDER BY c.nome, p.nome"
    );
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query(
        'SELECT id, produto_id, nome_exibicao, descricao, imagem, ordem
         FROM produto_cards
         ORDER BY produto_id, ordem, id'
    );
    $opcoesPorProduto = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $opcao) {
        $imagemRelativa = str_replace('\\', '/', (string) ($opcao['imagem'] ?? ''));
        $imagemValida = $imagemRelativa !== ''
            && strpos($imagemRelativa, '..') === false
            && $imagemRelativa[0] !== '/'
            && file_exists(__DIR__ . '/../public/images/menu/' . $imagemRelativa);
        $opcoesPorProduto[(int) $opcao['produto_id']][] = [
            'id' => (int) $opcao['id'],
            'nome_exibicao' => $opcao['nome_exibicao'],
            'descricao' => $opcao['descricao'] ?? '',
            'imagem' => $opcao['imagem'] ?? '',
            'imagem_url' => $imagemValida
                ? '../public/images/menu/' . $imagemRelativa
                : '../public/images/menu/default.png'
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar cardápio público</title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; color: #1f2937; background: #f3f4f6; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; }
        main { max-width: 1100px; margin: 0 auto; }
        header, .toolbar, .product-main, .modal-actions { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        header { margin-bottom: 20px; }
        h1 { margin: 0 0 8px; font-size: 1.7rem; }
        h2 { margin: 0 0 16px; }
        p { line-height: 1.5; }
        .back, button { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 7px; padding: 10px 14px; background: #4a6fa5; color: #fff; text-decoration: none; font: inherit; font-weight: 700; cursor: pointer; }
        button.secondary { background: #e5e7eb; color: #1f2937; }
        button.danger { background: #b91c1c; }
        button:disabled { opacity: .6; cursor: wait; }
        .help, .muted { color: #64748b; }
        .notice, .error, .warning { margin: 14px 0; padding: 12px 14px; border-radius: 7px; }
        .notice { background: #dcfce7; }
        .error { background: #fee2e2; color: #991b1b; }
        .warning { background: #fef3c7; }
        .toolbar { margin: 20px 0 12px; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font: inherit; }
        #searchProducts { max-width: 380px; }
        .product-list { display: grid; gap: 10px; }
        .product { display: grid; grid-template-columns: 90px minmax(0, 1fr) auto; align-items: center; gap: 16px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
        .product img { width: 90px; height: 72px; border-radius: 7px; object-fit: cover; background: #e5e7eb; }
        .product-main { justify-content: flex-start; }
        .product-name { margin: 0 0 5px; font-weight: 700; }
        .product-meta { margin: 0; color: #64748b; font-size: .92rem; }
        .empty { padding: 28px; border-radius: 10px; background: #fff; text-align: center; }
        dialog { width: min(680px, calc(100% - 24px)); max-height: 90vh; padding: 0; border: 0; border-radius: 12px; box-shadow: 0 20px 60px #0f172a40; }
        dialog::backdrop { background: #0f172a80; }
        .editor { padding: 22px; overflow-y: auto; }
        .editor-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
        .editor-header h2 { margin: 0 0 5px; }
        .close-button { padding: 6px 10px; background: transparent; color: #475569; font-size: 1.4rem; }
        .field { display: grid; gap: 6px; margin: 14px 0; }
        .field label, .option label { font-weight: 700; }
        .field small, .section-help { color: #64748b; }
        .product-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 12px; border-radius: 8px; background: #f8fafc; }
        .product-details p { margin: 0; }
        .options-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 20px; }
        .options-heading h3 { margin: 0; }
        .option { margin: 12px 0; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .option-image { display: flex; align-items: center; gap: 12px; margin-top: 10px; }
        .option-image img { width: 68px; height: 56px; border-radius: 6px; object-fit: cover; background: #e5e7eb; }
        .option-search { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .option-search a { display: inline-flex; border-radius: 6px; padding: 9px 12px; background: #e5e7eb; color: #1f2937; text-decoration: none; font-weight: 700; }
        .modal-actions { justify-content: flex-end; margin-top: 22px; }
        @media (max-width: 600px) {
            body { padding: 14px; }
            header, .toolbar { align-items: stretch; flex-direction: column; }
            .product { grid-template-columns: 64px minmax(0, 1fr); gap: 10px; }
            .product img { width: 64px; height: 60px; }
            .product button { grid-column: 1 / -1; }
            .product-details { grid-template-columns: 1fr; }
            .option-image { align-items: stretch; flex-direction: column; }
            .option-image img { width: 100%; height: 140px; }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <h1>Cardápio público</h1>
            <p class="help">Edite a apresentação dos produtos ativos. Nome, preço e categoria oficiais vêm do sistema de caixa.</p>
        </div>
        <a class="back" href="../caixa-seguro-7xy3q9kkle/">Voltar ao sistema</a>
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

    <div class="toolbar">
        <h2>Produtos que aparecem no cardápio (<?= count($produtos) ?>)</h2>
        <input id="searchProducts" type="search" placeholder="Buscar produto..." aria-label="Buscar produto">
    </div>

    <?php if ($produtos): ?>
        <div class="product-list" id="productList">
            <?php foreach ($produtos as $produto): ?>
                <?php
                $produtoId = (int) $produto['id'];
                $opcoes = $opcoesPorProduto[$produtoId] ?? [];
                $imagemRelativa = str_replace('\\', '/', (string) ($produto['imagem'] ?? ''));
                $imagemValida = $imagemRelativa !== ''
                    && strpos($imagemRelativa, '..') === false
                    && $imagemRelativa[0] !== '/'
                    && file_exists(__DIR__ . '/../public/images/menu/' . $imagemRelativa);
                $imagemUrl = $imagemValida
                    ? '../public/images/menu/' . $imagemRelativa
                    : '../public/images/menu/default.png';
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
                    'opcoes' => $opcoes
                ];
                $jsonProduto = json_encode($dadosProduto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                ?>
                <?php
                $termoPesquisa = $produto['nome'] . ' ' . $produto['categoria_nome'];
                $termoPesquisa = function_exists('mb_strtolower')
                    ? mb_strtolower($termoPesquisa, 'UTF-8')
                    : strtolower($termoPesquisa);
                ?>
                <article class="product" data-search="<?= htmlspecialchars($termoPesquisa, ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars($imagemUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
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
        <p class="empty" id="noSearchResults" hidden>Nenhum produto corresponde à busca.</p>
    <?php else: ?>
        <p class="empty">Não há produtos ativos para exibir. Ative os produtos no estoque para que apareçam aqui e no cardápio público.</p>
    <?php endif; ?>
</main>

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
            <input id="productImage" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
            <small>A imagem atual será mantida se nenhum arquivo for selecionado.</small>
        </div>

        <section>
            <div class="options-heading">
                <h3>Subcategorias do produto</h3>
                <button type="button" class="secondary" id="addOption">Adicionar</button>
            </div>
            <p class="section-help">Use para listar sabores ou apresentações. Cada subcategoria pode ter foto própria, mas compartilha produto e preço. Você pode enviar uma imagem ou pesquisar no Google Imagens e colar a URL direta do arquivo. Use apenas imagens que tenha autorização para publicar.</p>
            <div id="productOptions"></div>
        </section>

        <p class="error" id="editorError" hidden></p>
        <div class="modal-actions">
            <button type="button" class="secondary" data-close-editor>Cancelar</button>
            <button type="submit" id="saveProduct">Salvar apresentação</button>
        </div>
    </form>
</dialog>

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
                <img src="${escapeHtml(option.imagem_url || '../public/images/menu/default.png')}" alt="Prévia da imagem da subcategoria">
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
                row.querySelector('.option-image-url').value = '';
                row.querySelector('.option-image img').src = URL.createObjectURL(file);
            }
        });
        row.querySelector('.option-image-url').addEventListener('input', (event) => {
            if (event.target.value.trim()) {
                row.querySelector('.option-file').value = '';
            }
        });
        optionsContainer.append(row);
    }

    document.querySelectorAll('.edit-product').forEach((button) => {
        button.addEventListener('click', () => {
            currentProduct = JSON.parse(button.dataset.product);
            form.reset();
            errorBox.hidden = true;
            optionsContainer.replaceChildren();

            document.getElementById('productId').value = currentProduct.id;
            document.getElementById('officialName').textContent = currentProduct.nome;
            document.getElementById('officialPrice').textContent =
                'R$ ' + Number(currentProduct.preco).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            document.getElementById('officialCategory').textContent = currentProduct.categoria;
            document.getElementById('publicName').value = currentProduct.nome_publico;
            document.getElementById('publicDescription').value = currentProduct.descricao_publica;
            currentProduct.opcoes.forEach(addOption);
            editor.showModal();
        });
    });

    document.querySelectorAll('[data-close-editor]').forEach((button) => {
        button.addEventListener('click', () => editor.close());
    });

    document.getElementById('addOption').addEventListener('click', () => addOption());

    document.getElementById('searchProducts').addEventListener('input', (event) => {
        const query = event.target.value.trim().toLocaleLowerCase('pt-BR');
        let visibleCount = 0;
        document.querySelectorAll('.product').forEach((product) => {
            const matches = product.dataset.search.includes(query);
            product.hidden = !matches;
            if (matches) visibleCount++;
        });
        const noResults = document.getElementById('noSearchResults');
        if (noResults) noResults.hidden = visibleCount > 0;
    });

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

        const options = Array.from(optionsContainer.querySelectorAll('.option')).map((row) => ({
                id: row.dataset.id || null,
                nome_exibicao: row.querySelector('.option-name').value,
                descricao: row.querySelector('.option-description').value,
                imagem_url: row.querySelector('.option-image-url').value.trim()
            }));
        data.append('opcoes_json', JSON.stringify(options));
        options.forEach((option, index) => {
            const imageFile = optionsContainer.querySelectorAll('.option-file')[index].files[0];
            if (imageFile) {
                data.append(`opcao_imagem_${index}`, imageFile);
            }
        });

        try {
            const response = await fetch('../../caixa-seguro-7xy3q9kkle/api/salvar_produto.php', {
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
</body>
</html>
