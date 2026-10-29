<?php
require_once __DIR__ . '/includes/cardapio-auth.php';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';

function voltarComErro($mensagem) {
    header('Location: gerenciar_cardapio.php?erro=' . urlencode($mensagem));
    exit;
}

function prepararImagemEnviada($arquivo, $produtoId) {
    if ($arquivo === null || (is_array($arquivo) && ($arquivo['error'] ?? null) === UPLOAD_ERR_NO_FILE)) {
        return null;
    }

    if (
        !is_array($arquivo)
        || ($arquivo['error'] ?? null) !== UPLOAD_ERR_OK
        || !isset($arquivo['tmp_name'], $arquivo['size'])
        || !is_string($arquivo['tmp_name'])
        || !is_int($arquivo['size'])
        || !is_uploaded_file($arquivo['tmp_name'])
    ) {
        throw new RuntimeException('Não foi possível receber a imagem enviada.');
    }
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
    }

    $imagemInfo = getimagesize($arquivo['tmp_name']);
    $extensoesMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    if (!$imagemInfo || !isset($imagemInfo['mime']) || !isset($extensoesMime[$imagemInfo['mime']])) {
        throw new RuntimeException('Envie uma imagem JPG, PNG, GIF ou WebP válida.');
    }

    $diretorio = __DIR__ . '/../public/images/menu/products';
    if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
        throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
    }

    $nomeArquivo = $produtoId . '_' . bin2hex(random_bytes(8)) . '.' . $extensoesMime[$imagemInfo['mime']];
    $caminho = $diretorio . DIRECTORY_SEPARATOR . $nomeArquivo;
    if (!move_uploaded_file($arquivo['tmp_name'], $caminho)) {
        throw new RuntimeException('Falha ao salvar a imagem enviada.');
    }

    return [
        'caminho' => $caminho,
        'relativo' => 'products/' . $nomeArquivo
    ];
}

function removerImagemGerenciada($imagemRelativa) {
    $imagemRelativa = str_replace('\\', '/', (string) $imagemRelativa);
    if (strpos($imagemRelativa, 'products/') !== 0 || strpos($imagemRelativa, '..') !== false) {
        return;
    }

    $diretorioGerenciado = realpath(__DIR__ . '/../public/images/menu/products');
    $arquivo = __DIR__ . '/../public/images/menu/' . $imagemRelativa;
    if ($diretorioGerenciado && realpath(dirname($arquivo)) === $diretorioGerenciado && is_file($arquivo)) {
        if (!unlink($arquivo)) {
            error_log('Não foi possível remover a imagem do card: ' . $imagemRelativa);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$token = $_POST['csrf_token'] ?? '';
if (
    !isset($_SESSION['csrf_cardapio'])
    || !is_string($_SESSION['csrf_cardapio'])
    || !is_string($token)
    || !hash_equals($_SESSION['csrf_cardapio'], $token)
) {
    http_response_code(403);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
}

$acao = $_POST['acao'] ?? 'salvar_produto';
$idProduto = filter_var($_POST['id'] ?? $_POST['produto_id'] ?? null, FILTER_VALIDATE_INT);
if (!$idProduto || $idProduto < 1) {
    voltarComErro('Produto inválido.');
}

$arquivoNovo = null;

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados do caixa.');
    }

    $stmt = $db->prepare('SELECT id, imagem FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $idProduto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        throw new RuntimeException('Produto não encontrado.');
    }

    if ($acao === 'salvar_produto') {
        $categoriaId = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $nomeInput = $_POST['nome'] ?? '';
        $precoInput = $_POST['preco'] ?? '';
        if (!is_string($nomeInput) || !is_string($precoInput)) {
            throw new RuntimeException('Confira nome e preço informados.');
        }
        $nome = trim($nomeInput);
        $preco = str_replace(',', '.', trim($precoInput));
        $nomeComprimento = preg_match_all('/./us', $nome, $caracteresNome);
        if (!$categoriaId || $categoriaId < 1 || $nome === '' || $nomeComprimento === false || $nomeComprimento > 100 || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $preco)) {
            throw new RuntimeException('Confira nome e preço informados.');
        }

        $stmt = $db->prepare('SELECT id FROM categorias WHERE id = :id');
        $stmt->execute([':id' => $categoriaId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('A categoria selecionada não existe.');
        }

        $arquivoNovo = prepararImagemEnviada($_FILES['imagem'] ?? null, $idProduto);
        if ($arquivoNovo) {
            $stmt = $db->prepare(
                'UPDATE produtos
                 SET nome = :nome, preco = :preco, categoria_id = :categoria_id, imagem = :imagem
                 WHERE id = :id'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':preco' => $preco,
                ':categoria_id' => $categoriaId,
                ':imagem' => $arquivoNovo['relativo'],
                ':id' => $idProduto
            ]);
        } else {
            $stmt = $db->prepare(
                'UPDATE produtos
                 SET nome = :nome, preco = :preco, categoria_id = :categoria_id
                 WHERE id = :id'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':preco' => $preco,
                ':categoria_id' => $categoriaId,
                ':id' => $idProduto
            ]);
        }
    } elseif ($acao === 'salvar_card') {
        $cardIdInput = $_POST['card_id'] ?? '';
        $cardId = $cardIdInput === '' ? null : filter_var($cardIdInput, FILTER_VALIDATE_INT);
        if ($cardIdInput !== '' && (!$cardId || $cardId < 1)) {
            throw new RuntimeException('Card inválido.');
        }
        $nomeInput = $_POST['nome_exibicao'] ?? '';
        $descricaoInput = $_POST['descricao'] ?? '';
        if (!is_string($nomeInput) || !is_string($descricaoInput)) {
            throw new RuntimeException('Confira nome e descrição do card.');
        }
        $nome = trim($nomeInput);
        $descricao = trim($descricaoInput);
        $ordem = filter_var($_POST['ordem'] ?? '0', FILTER_VALIDATE_INT);
        $nomeComprimento = preg_match_all('/./us', $nome, $caracteresNome);
        $descricaoComprimento = preg_match_all('/./us', $descricao, $caracteresDescricao);
        if ($nome === '' || $nomeComprimento === false || $nomeComprimento > 100 || $descricaoComprimento === false || $descricaoComprimento > 1000 || $ordem === false || $ordem < 0 || $ordem > 100000) {
            throw new RuntimeException('Confira nome, descrição e ordem do card.');
        }

        $imagemAtual = null;
        if ($cardId) {
            $stmt = $db->prepare('SELECT imagem FROM produto_cards WHERE id = :card_id AND produto_id = :produto_id');
            $stmt->execute([':card_id' => $cardId, ':produto_id' => $idProduto]);
            $imagemAtual = $stmt->fetchColumn();
            if ($imagemAtual === false) {
                throw new RuntimeException('Card não encontrado para este produto.');
            }
        }

        $arquivoNovo = prepararImagemEnviada($_FILES['imagem'] ?? null, $idProduto);
        $imagem = $arquivoNovo ? $arquivoNovo['relativo'] : $imagemAtual;

        if ($cardId) {
            $stmt = $db->prepare(
                'UPDATE produto_cards
                 SET nome_exibicao = :nome, descricao = :descricao, imagem = :imagem, ordem = :ordem
                 WHERE id = :id AND produto_id = :produto_id'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao === '' ? null : $descricao,
                ':imagem' => $imagem,
                ':ordem' => $ordem,
                ':id' => $cardId,
                ':produto_id' => $idProduto
            ]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO produto_cards (produto_id, nome_exibicao, descricao, imagem, ordem)
                 VALUES (:produto_id, :nome, :descricao, :imagem, :ordem)'
            );
            $stmt->execute([
                ':produto_id' => $idProduto,
                ':nome' => $nome,
                ':descricao' => $descricao === '' ? null : $descricao,
                ':imagem' => $imagem,
                ':ordem' => $ordem
            ]);
        }
    } elseif ($acao === 'excluir_card') {
        $cardId = filter_var($_POST['card_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$cardId || $cardId < 1) {
            throw new RuntimeException('Card inválido.');
        }

        $stmt = $db->prepare('SELECT imagem FROM produto_cards WHERE id = :card_id AND produto_id = :produto_id');
        $stmt->execute([':card_id' => $cardId, ':produto_id' => $idProduto]);
        $imagem = $stmt->fetchColumn();
        if ($imagem === false) {
            throw new RuntimeException('Card não encontrado para este produto.');
        }

        $stmt = $db->prepare('DELETE FROM produto_cards WHERE id = :card_id AND produto_id = :produto_id');
        $stmt->execute([':card_id' => $cardId, ':produto_id' => $idProduto]);
        removerImagemGerenciada($imagem);
    } else {
        throw new RuntimeException('Ação inválida.');
    }

    header('Location: gerenciar_cardapio.php?msg=' . urlencode('Alterações salvas.'));
    exit;
} catch (Throwable $e) {
    if ($arquivoNovo && is_file($arquivoNovo['caminho'])) {
        unlink($arquivoNovo['caminho']);
    }
    error_log('Erro ao atualizar apresentação do cardápio: ' . $e->getMessage());
    if ($e instanceof RuntimeException) {
        voltarComErro($e->getMessage());
    }
    voltarComErro('Não foi possível salvar. Confira se a migração da tabela de cards já foi aplicada.');
}
