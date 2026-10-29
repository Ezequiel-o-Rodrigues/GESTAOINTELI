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
        "SELECT id, nome, descricao FROM categorias
         ORDER BY CASE nome
            WHEN 'Espetos' THEN 1
            WHEN 'Porções' THEN 2
            WHEN 'Jantinhas e Marmitas' THEN 3
            WHEN 'Bebidas não alcoólicas' THEN 4
            WHEN 'Cervejas' THEN 5
            WHEN 'Outros' THEN 6
            ELSE 7
         END, nome"
    );
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query(
        "SELECT p.id, p.nome, p.nome_publico, p.descricao_publica, p.preco, p.imagem, p.ativo, p.categoria_id, c.nome AS categoria_nome
         FROM produtos p
         INNER JOIN categorias c ON c.id = p.categoria_id
         ORDER BY
            CASE c.nome
                WHEN 'Espetos' THEN 1
                WHEN 'Porções' THEN 2
                WHEN 'Jantinhas e Marmitas' THEN 3
                WHEN 'Bebidas não alcoólicas' THEN 4
                WHEN 'Cervejas' THEN 5
                WHEN 'Outros' THEN 6
                ELSE 7
            END,
            c.nome,
            p.nome"
    );
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query(
        'SELECT id, produto_id, nome_exibicao, descricao, imagem, ordem
         FROM produto_cards
         ORDER BY produto_id, ordem, id'
    );
    $cardsPorProduto = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $card) {
        $cardsPorProduto[(int) $card['produto_id']][] = $card;
    }
} catch (Throwable $e) {
    error_log('Erro ao carregar gerenciador do cardápio: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar o gerenciador. Confira a conexão com o banco do caixa e, se necessário, faça backup e execute migracao_cardapio_integrado.sql no phpMyAdmin desse banco.');
}

if (!isset($_SESSION['csrf_cardapio'])) {
    $_SESSION['csrf_cardapio'] = bin2hex(random_bytes(32));
}

$mensagem = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerenciar produtos do cardápio</title>
    <style>
        body { margin: 0; padding: 24px; background: #f3f4f6; color: #1f2937; font: 16px Arial, sans-serif; }
        main { max-width: 1000px; margin: 0 auto; }
        header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
        h1 { margin: 0; font-size: 1.7rem; }
        .back, button { display: inline-block; border: 0; border-radius: 6px; padding: 10px 14px; background: #4a6fa5; color: #fff; text-decoration: none; font-weight: 700; cursor: pointer; }
        .notice { margin: 12px 0; padding: 12px; border-radius: 6px; background: #dcfce7; }
        .error { margin: 12px 0; padding: 12px; border-radius: 6px; background: #fee2e2; }
        .warning { margin: 16px 0; padding: 14px; border-radius: 6px; background: #fef3c7; }
        .category { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) auto auto; gap: 10px; align-items: end; margin: 10px 0; padding: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 7px; }
        .ack { display: flex; grid-column: 1 / -1; align-items: flex-start; gap: 8px; }
        .ack input { width: auto; margin-top: 3px; }
        .product { display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 16px; margin: 14px 0; padding: 16px; border-radius: 8px; background: #fff; box-shadow: 0 2px 6px #00000012; }
        .product img { width: 140px; height: 110px; object-fit: cover; border-radius: 6px; background: #eee; }
        .fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        label { display: grid; gap: 5px; font-size: .9rem; font-weight: 700; }
        input, select { box-sizing: border-box; width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 5px; font: inherit; }
        .status { margin: 0 0 10px; color: #4b5563; font-size: .9rem; }
        .actions { display: flex; align-items: center; gap: 12px; margin-top: 12px; }
        .cards { grid-column: 1 / -1; margin-top: 8px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
        .cards h2 { margin: 0 0 8px; font-size: 1.15rem; }
        .card-help { margin: 0 0 14px; color: #4b5563; font-size: .9rem; }
        .menu-card { display: grid; grid-template-columns: 100px minmax(0, 1fr); gap: 14px; margin: 12px 0; padding: 12px; border: 1px solid #e5e7eb; border-radius: 7px; }
        .menu-card img { width: 100px; height: 85px; }
        .menu-card form + form { margin-top: 8px; }
        .add-card { margin-top: 18px; padding-top: 14px; border-top: 1px dashed #cbd5e1; }
        .delete-button { background: #b91c1c; }
        @media (max-width: 600px) {
            body { padding: 14px; }
            .product { grid-template-columns: 1fr; }
            .product img { width: 100%; height: 180px; }
            .fields { grid-template-columns: 1fr; }
            .category { grid-template-columns: 1fr; }
            .menu-card { grid-template-columns: 1fr; }
            .menu-card img { width: 100%; height: 160px; }
        }
    </style>
</head>
<body>
<main>
    <header>
        <h1>Gerenciar produtos do cardápio</h1>
        <a class="back" href="../caixa-seguro-7xy3q9kkle/">Voltar ao sistema de caixa</a>
    </header>

    <?php if ($mensagem !== ''): ?>
        <p class="notice"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p class="error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <p>Produtos ativos aparecem no cardápio público e usam nome, preço e categoria oficiais do estoque. As apresentações públicas e opções são informativas: não criam produtos, preços ou estoque próprios.</p>

    <section class="warning">
        <strong>Categorias compartilhadas com estoque, caixa e relatórios.</strong>
        Alterar o nome de uma categoria ou reclassificar produtos também altera como as vendas históricas são agrupadas nos relatórios. Excluir uma categoria com produtos é bloqueado até que todos sejam reclassificados no estoque.
    </section>

    <section>
        <h2>Categorias</h2>
        <?php foreach ($categorias as $categoria): ?>
            <div class="category">
                <form method="post" action="salvar_cardapio.php" class="fields" style="grid-column: 1 / 3;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="salvar_categoria">
                    <input type="hidden" name="categoria_id" value="<?= (int) $categoria['id'] ?>">
                    <label>
                        Nome
                        <input type="text" name="nome_categoria" maxlength="50" required value="<?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        Descrição (opcional)
                        <input type="text" name="descricao_categoria" maxlength="1000" value="<?= htmlspecialchars($categoria['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label class="ack">
                        <input type="checkbox" name="backup_confirmado" value="1" required>
                        Confirmo que fiz backup e entendo o impacto do nome compartilhado nos relatórios históricos.
                    </label>
                    <button type="submit">Salvar categoria</button>
                </form>
                <form method="post" action="salvar_cardapio.php" onsubmit="return confirm('Excluir esta categoria? A operação será bloqueada se houver produtos vinculados.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="excluir_categoria">
                    <input type="hidden" name="categoria_id" value="<?= (int) $categoria['id'] ?>">
                    <label class="ack">
                        <input type="checkbox" name="backup_confirmado" value="1" required>
                        Backup feito
                    </label>
                    <button class="delete-button" type="submit">Excluir</button>
                </form>
            </div>
        <?php endforeach; ?>
        <form method="post" action="salvar_cardapio.php" class="category">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="salvar_categoria">
            <label>
                Nova categoria
                <input type="text" name="nome_categoria" maxlength="50" required>
            </label>
            <label>
                Descrição (opcional)
                <input type="text" name="descricao_categoria" maxlength="1000">
            </label>
            <button type="submit">Criar categoria</button>
        </form>
    </section>

    <?php foreach ($produtos as $produto): ?>
        <?php
        $imagemRelativa = str_replace('\\', '/', (string) ($produto['imagem'] ?? ''));
        $imagemValida = $imagemRelativa !== ''
            && strpos($imagemRelativa, '..') === false
            && $imagemRelativa[0] !== '/'
            && file_exists(__DIR__ . '/../public/images/menu/' . $imagemRelativa);
        $imagemUrl = $imagemValida
            ? '../public/images/menu/' . $imagemRelativa
            : '../public/images/menu/default.png';
        ?>
        <section class="product">
            <img src="<?= htmlspecialchars($imagemUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
            <div>
                <p class="status">
                    Produto #<?= (int) $produto['id'] ?> · <?= $produto['ativo'] ? 'Ativo e visível no cardápio' : 'Inativo e oculto no cardápio' ?>
                </p>
                <p>
                    <strong>Produto no caixa:</strong> <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?><br>
                    <strong>Preço oficial:</strong> R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?><br>
                    <strong>Categoria compartilhada:</strong> <?= htmlspecialchars($produto['categoria_nome'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p>
                    <strong>Nome público:</strong> <?= htmlspecialchars($produto['nome_publico'] ?: $produto['nome'], ENT_QUOTES, 'UTF-8') ?><br>
                    <strong>Descrição pública:</strong> <?= htmlspecialchars($produto['descricao_publica'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>
                <form method="post" action="salvar_cardapio.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="salvar_imagem_produto">
                    <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                    <div class="fields">
                        <label>
                            Imagem (JPG, PNG, GIF ou WebP; até 5 MB)
                            <input type="file" name="imagem" accept="image/jpeg,image/png,image/gif,image/webp">
                        </label>
                    </div>
                    <div class="actions">
                        <button type="submit">Atualizar imagem do produto</button>
                        <a href="../caixa-seguro-7xy3q9kkle/modules/estoque/">Editar produto no estoque</a>
                    </div>
                </form>
            </div>
            <div class="cards">
                <h2>Cards públicos</h2>
                <p class="card-help">
                    <?php if (!empty($cardsPorProduto[(int) $produto['id']])): ?>
                        Estas opções aparecem como apresentações relacionadas. São informativas e compartilham o preço do produto no caixa.
                    <?php else: ?>
                        Sem opções relacionadas: o cardápio usa o nome público configurado no estoque (ou o nome oficial) e a imagem do produto.
                    <?php endif; ?>
                </p>

                <?php foreach ($cardsPorProduto[(int) $produto['id']] ?? [] as $card): ?>
                    <?php
                    $imagemFonteCard = !empty($card['imagem']) ? $card['imagem'] : $produto['imagem'];
                    $imagemCard = str_replace('\\', '/', (string) ($imagemFonteCard ?? ''));
                    $imagemCardValida = $imagemCard !== ''
                        && strpos($imagemCard, '..') === false
                        && $imagemCard[0] !== '/'
                        && file_exists(__DIR__ . '/../public/images/menu/' . $imagemCard);
                    $imagemCardUrl = $imagemCardValida
                        ? '../public/images/menu/' . $imagemCard
                        : '../public/images/menu/default.png';
                    ?>
                    <div class="menu-card">
                        <img src="<?= htmlspecialchars($imagemCardUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                        <div>
                            <form method="post" action="salvar_cardapio.php" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="acao" value="salvar_card">
                                <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                                <input type="hidden" name="card_id" value="<?= (int) $card['id'] ?>">
                                <div class="fields">
                                    <label>
                                        Nome exibido
                                        <input type="text" name="nome_exibicao" maxlength="100" required value="<?= htmlspecialchars($card['nome_exibicao'], ENT_QUOTES, 'UTF-8') ?>">
                                    </label>
                                    <label>
                                        Ordem
                                        <input type="number" name="ordem" min="0" max="100000" required value="<?= (int) $card['ordem'] ?>">
                                    </label>
                                    <label>
                                        Descrição (opcional)
                                        <input type="text" name="descricao" maxlength="1000" value="<?= htmlspecialchars($card['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </label>
                                    <label>
                                        Trocar imagem (opcional)
                                        <input type="file" name="imagem" accept="image/jpeg,image/png,image/gif,image/webp">
                                    </label>
                                </div>
                                <div class="actions">
                                    <button type="submit">Salvar card</button>
                                </div>
                            </form>
                            <form method="post" action="salvar_cardapio.php" onsubmit="return confirm('Excluir apenas este card de apresentação? O produto e o estoque serão mantidos.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="acao" value="excluir_card">
                                <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                                <input type="hidden" name="card_id" value="<?= (int) $card['id'] ?>">
                                <button class="delete-button" type="submit">Excluir card</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <form class="add-card" method="post" action="salvar_cardapio.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="salvar_card">
                    <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                    <div class="fields">
                        <label>
                            Novo nome para o card
                            <input type="text" name="nome_exibicao" maxlength="100" required>
                        </label>
                        <label>
                            Ordem
                            <input type="number" name="ordem" min="0" max="100000" value="<?= count($cardsPorProduto[(int) $produto['id']] ?? []) + 1 ?>" required>
                        </label>
                        <label>
                            Descrição (opcional)
                            <input type="text" name="descricao" maxlength="1000">
                        </label>
                        <label>
                            Imagem (opcional; JPG, PNG, GIF ou WebP; até 5 MB)
                            <input type="file" name="imagem" accept="image/jpeg,image/png,image/gif,image/webp">
                        </label>
                    </div>
                    <div class="actions">
                        <button type="submit">Adicionar card</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>
