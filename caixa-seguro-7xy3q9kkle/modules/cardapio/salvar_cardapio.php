<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/menu-images.php';

function voltarComErro($mensagem) {
    header('Location: ' . PathConfig::modules('cardapio/index.php?erro=' . urlencode($mensagem)));
    exit;
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
$arquivoNovo = null;

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados do caixa.');
    }

    if ($acao === 'salvar_categoria') {
        $categoriaId = ($_POST['categoria_id'] ?? '') === ''
            ? null
            : filter_var($_POST['categoria_id'], FILTER_VALIDATE_INT);
        $nomeInput = $_POST['nome_categoria'] ?? '';
        $descricaoInput = $_POST['descricao_categoria'] ?? '';
        if (!is_string($nomeInput) || !is_string($descricaoInput)) {
            throw new RuntimeException('Confira o nome e a descrição da categoria.');
        }
        $nome = trim($nomeInput);
        $descricao = trim($descricaoInput);
        $nomeComprimento = preg_match_all('/./us', $nome, $caracteresNome);
        $descricaoComprimento = preg_match_all('/./us', $descricao, $caracteresDescricao);
        if ($nome === '' || $nomeComprimento === false || $nomeComprimento > 50 || $descricaoComprimento === false || $descricaoComprimento > 1000) {
            throw new RuntimeException('Informe um nome de categoria de até 50 caracteres e uma descrição de até 1.000 caracteres.');
        }

        if ($categoriaId !== null) {
            if ($categoriaId < 1) {
                throw new RuntimeException('Categoria inválida.');
            }
            if (($_POST['backup_confirmado'] ?? '') !== '1') {
                throw new RuntimeException('Confirme o backup antes de renomear ou editar uma categoria compartilhada.');
            }
            $stmt = $db->prepare('UPDATE categorias SET nome = :nome, descricao = :descricao WHERE id = :id');
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao === '' ? null : $descricao,
                ':id' => $categoriaId
            ]);
        } else {
            $stmt = $db->prepare('INSERT INTO categorias (nome, descricao) VALUES (:nome, :descricao)');
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao === '' ? null : $descricao
            ]);
        }

        header('Location: ' . PathConfig::modules('cardapio/index.php?msg=' . urlencode('Categoria salva. A categoria é compartilhada com estoque e relatórios.')));
        exit;
    }

    if ($acao === 'excluir_categoria') {
        $categoriaId = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$categoriaId || $categoriaId < 1) {
            throw new RuntimeException('Categoria inválida.');
        }
        if (($_POST['backup_confirmado'] ?? '') !== '1') {
            throw new RuntimeException('Confirme o backup antes de excluir uma categoria.');
        }

        $stmt = $db->prepare('SELECT COUNT(*) FROM produtos WHERE categoria_id = :categoria_id');
        $stmt->execute([':categoria_id' => $categoriaId]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('A categoria possui produtos. Reclassifique todos no estoque antes de excluí-la.');
        }

        $stmt = $db->prepare('DELETE FROM categorias WHERE id = :id');
        $stmt->execute([':id' => $categoriaId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Categoria não encontrada.');
        }

        header('Location: ' . PathConfig::modules('cardapio/index.php?msg=' . urlencode('Categoria excluída.')));
        exit;
    }

    if (!$idProduto || $idProduto < 1) {
        throw new RuntimeException('Produto inválido.');
    }

    $stmt = $db->prepare('SELECT id, imagem FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $idProduto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        throw new RuntimeException('Produto não encontrado.');
    }

    if ($acao === 'salvar_imagem_produto') {
        $arquivoNovo = menuPrepareUploadedImage($_FILES['imagem'] ?? null, $idProduto);
        if (!$arquivoNovo) {
            throw new RuntimeException('Selecione uma imagem antes de salvar.');
        }
        $stmt = $db->prepare('UPDATE produtos SET imagem = :imagem, imagem_url = NULL WHERE id = :id');
        $stmt->execute([
            ':imagem' => $arquivoNovo['relativo'],
            ':id' => $idProduto
        ]);
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

        $arquivoNovo = menuPrepareUploadedImage($_FILES['imagem'] ?? null, $idProduto);
        $imagem = $arquivoNovo ? $arquivoNovo['relativo'] : $imagemAtual;

        if ($cardId) {
            $stmt = $db->prepare(
                'UPDATE produto_cards
                 SET nome_exibicao = :nome, descricao = :descricao, imagem = :imagem,
                     imagem_url = NULL, ordem = :ordem
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
        menuRemoveManagedImage($imagem);
    } else {
        throw new RuntimeException('Ação inválida.');
    }

    header('Location: ' . PathConfig::modules('cardapio/index.php?msg=' . urlencode('Alterações salvas.')));
    exit;
} catch (Throwable $e) {
    if ($arquivoNovo && is_file($arquivoNovo['caminho'])) {
        if (!unlink($arquivoNovo['caminho'])) {
            error_log('Não foi possível remover o upload temporário após falha: ' . $arquivoNovo['caminho']);
        }
    }
    error_log('Erro ao atualizar apresentação do cardápio: ' . $e->getMessage());
    if ($e instanceof RuntimeException) {
        voltarComErro($e->getMessage());
    }
    voltarComErro('Não foi possível salvar. Confira se a migração da tabela de cards já foi aplicada.');
}
