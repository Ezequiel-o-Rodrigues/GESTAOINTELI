<?php
require_once __DIR__ . '/includes/cardapio-auth.php';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    exit('Não foi possível conectar ao banco de dados do caixa.');
}

$stmt = $db->query(
    "SELECT id, nome FROM categorias
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
    "SELECT p.id, p.nome, p.preco, p.imagem, p.ativo, p.categoria_id, c.nome AS categoria_nome
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
        .product { display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 16px; margin: 14px 0; padding: 16px; border-radius: 8px; background: #fff; box-shadow: 0 2px 6px #00000012; }
        .product img { width: 140px; height: 110px; object-fit: cover; border-radius: 6px; background: #eee; }
        .fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        label { display: grid; gap: 5px; font-size: .9rem; font-weight: 700; }
        input, select { box-sizing: border-box; width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 5px; font: inherit; }
        .status { margin: 0 0 10px; color: #4b5563; font-size: .9rem; }
        .actions { display: flex; align-items: center; gap: 12px; margin-top: 12px; }
        @media (max-width: 600px) {
            body { padding: 14px; }
            .product { grid-template-columns: 1fr; }
            .product img { width: 100%; height: 180px; }
            .fields { grid-template-columns: 1fr; }
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

    <p>Nome, preço e categoria são atualizados no mesmo cadastro usado pelo caixa e pelo estoque. Produtos ativos aparecem no cardápio público; produtos inativos ficam ocultos.</p>

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
                <form method="post" action="salvar_cardapio.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_cardapio'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                    <div class="fields">
                        <label>
                            Nome
                            <input type="text" name="nome" maxlength="100" required value="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label>
                            Preço (R$)
                            <input type="number" name="preco" min="0" max="99999999.99" step="0.01" required value="<?= htmlspecialchars((string) $produto['preco'], ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label>
                            Categoria
                            <select name="categoria_id" required>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['id'] === (int) $produto['categoria_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            Imagem (JPG, PNG, GIF ou WebP; até 5 MB)
                            <input type="file" name="imagem" accept="image/jpeg,image/png,image/gif,image/webp">
                        </label>
                    </div>
                    <div class="actions">
                        <button type="submit">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>
