<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/menu-images.php';

header('Content-Type: application/json; charset=utf-8');

function responderErro($mensagem, $status) {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $mensagem]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderErro('Método não permitido.', 405);
}

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    responderErro('É necessário entrar no sistema.', 401);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        responderErro('Dados inválidos.', 400);
    }
} else {
    $input = $_POST;
}

$hasMenuFields = array_key_exists('nome_publico', $input)
    || array_key_exists('descricao_publica', $input)
    || array_key_exists('opcoes_json', $input)
    || (isset($_FILES['imagem']) && ($_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

if ($hasMenuFields) {
    $token = $_POST['csrf_token'] ?? '';
    if (
        !isset($_SESSION['csrf_produto'])
        || !is_string($_SESSION['csrf_produto'])
        || !is_string($token)
        || !hash_equals($_SESSION['csrf_produto'], $token)
    ) {
        responderErro('Sessão expirada. Recarregue o formulário e tente novamente.', 403);
    }
}

$produtoIdInput = $input['id'] ?? null;
if ($produtoIdInput === null || $produtoIdInput === '') {
    $produtoId = null;
} else {
    $produtoId = filter_var($produtoIdInput, FILTER_VALIDATE_INT);
    if (!$produtoId || $produtoId < 1) {
        responderErro('Identificador de produto inválido.', 400);
    }
}
$nomeInput = $input['nome'] ?? '';
$precoInput = $input['preco'] ?? '';
if (!is_string($nomeInput) || !is_scalar($precoInput)) {
    responderErro('Confira nome e preço informados.', 400);
}
$nome = trim($nomeInput);
$categoriaId = filter_var($input['categoria_id'] ?? null, FILTER_VALIDATE_INT);
$preco = str_replace(',', '.', trim((string) $precoInput));
$estoqueMinimo = filter_var($input['estoque_minimo'] ?? null, FILTER_VALIDATE_INT);
$estoqueInicial = filter_var($input['estoque_inicial'] ?? 0, FILTER_VALIDATE_INT);
$nomeComprimento = preg_match_all('/./us', $nome, $caracteresNome);

if (
    $nome === ''
    || $nomeComprimento === false
    || $nomeComprimento > 100
    || !$categoriaId
    || $categoriaId < 1
    || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $preco)
    || $estoqueMinimo === false
    || $estoqueMinimo < 0
    || $estoqueInicial === false
    || $estoqueInicial < 0
) {
    responderErro('Confira nome, categoria, preço e estoque informados.', 400);
}

$nomePublico = null;
$descricaoPublica = null;
$opcoes = null;
if ($hasMenuFields) {
    $nomePublicoInput = $input['nome_publico'] ?? '';
    $descricaoPublicaInput = $input['descricao_publica'] ?? '';
    if (!is_string($nomePublicoInput) || !is_string($descricaoPublicaInput)) {
        responderErro('Confira o nome público e a descrição informados.', 400);
    }
    $nomePublicoInput = trim($nomePublicoInput);
    $descricaoPublicaInput = trim($descricaoPublicaInput);
    $nomePublicoComprimento = preg_match_all('/./us', $nomePublicoInput, $caracteresNomePublico);
    $descricaoComprimento = preg_match_all('/./us', $descricaoPublicaInput, $caracteresDescricaoPublica);
    if (
        $nomePublicoComprimento === false
        || $nomePublicoComprimento > 100
        || $descricaoComprimento === false
        || $descricaoComprimento > 1000
    ) {
        responderErro('Nome público ou descrição excede o limite permitido.', 400);
    }

    $nomePublico = $nomePublicoInput === '' ? null : $nomePublicoInput;
    $descricaoPublica = $descricaoPublicaInput === '' ? null : $descricaoPublicaInput;
    $opcoesInput = json_decode((string) ($input['opcoes_json'] ?? '[]'), true);
    if (!is_array($opcoesInput) || count($opcoesInput) > 50) {
        responderErro('A lista de opções é inválida ou excede o limite de 50 itens.', 400);
    }
    $opcoes = [];
    foreach ($opcoesInput as $opcao) {
        if (!is_array($opcao)) {
            responderErro('Uma das opções informadas é inválida.', 400);
        }
        $opcaoNome = $opcao['nome_exibicao'] ?? '';
        $opcaoDescricao = $opcao['descricao'] ?? '';
        if (!is_string($opcaoNome) || !is_string($opcaoDescricao)) {
            responderErro('Confira o nome e a descrição de cada opção.', 400);
        }
        $opcaoNome = trim($opcaoNome);
        $opcaoDescricao = trim($opcaoDescricao);
        $opcaoNomeComprimento = preg_match_all('/./us', $opcaoNome, $caracteresOpcaoNome);
        $opcaoDescricaoComprimento = preg_match_all('/./us', $opcaoDescricao, $caracteresOpcaoDescricao);
        if (
            $opcaoNome === ''
            || $opcaoNomeComprimento === false
            || $opcaoNomeComprimento > 100
            || $opcaoDescricaoComprimento === false
            || $opcaoDescricaoComprimento > 1000
        ) {
            responderErro('Confira o nome e a descrição de cada opção.', 400);
        }
        $opcaoId = ($opcao['id'] ?? null) === null || ($opcao['id'] ?? '') === ''
            ? null
            : filter_var($opcao['id'], FILTER_VALIDATE_INT);
        if ($opcaoId !== null && (!$opcaoId || $opcaoId < 1)) {
            responderErro('Uma das opções possui identificador inválido.', 400);
        }
        $opcoes[] = [
            'id' => $opcaoId,
            'nome' => $opcaoNome,
            'descricao' => $opcaoDescricao === '' ? null : $opcaoDescricao
        ];
    }
}

$arquivoNovo = null;
$db = null;
try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    $stmt = $db->prepare('SELECT id FROM categorias WHERE id = :id');
    $stmt->execute([':id' => $categoriaId]);
    if (!$stmt->fetchColumn()) {
        responderErro('A categoria selecionada não existe.', 400);
    }

    $db->beginTransaction();
    $produtoNovo = $produtoId === null;
    if (!$produtoNovo) {
        $stmt = $db->prepare(
            'UPDATE produtos
             SET nome = :nome, categoria_id = :categoria_id, preco = :preco,
                 estoque_minimo = :estoque_minimo, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':nome' => $nome,
            ':categoria_id' => $categoriaId,
            ':preco' => $preco,
            ':estoque_minimo' => $estoqueMinimo,
            ':id' => $produtoId
        ]);
        if ($stmt->rowCount() === 0) {
            $check = $db->prepare('SELECT id FROM produtos WHERE id = :id');
            $check->execute([':id' => $produtoId]);
            if (!$check->fetchColumn()) {
                throw new OutOfBoundsException('Produto não encontrado.');
            }
        }
    } else {
        $stmt = $db->prepare(
            'INSERT INTO produtos
                (nome, categoria_id, preco, estoque_minimo, estoque_atual, ativo, created_at)
             VALUES
                (:nome, :categoria_id, :preco, :estoque_minimo, :estoque_inicial, 1, NOW())'
        );
        $stmt->execute([
            ':nome' => $nome,
            ':categoria_id' => $categoriaId,
            ':preco' => $preco,
            ':estoque_minimo' => $estoqueMinimo,
            ':estoque_inicial' => $estoqueInicial
        ]);
        $produtoId = (int) $db->lastInsertId();

        if ($estoqueInicial > 0) {
            $stmt = $db->prepare(
                "INSERT INTO movimentacoes_estoque
                    (produto_id, tipo, quantidade, observacao, created_at)
                 VALUES (:produto_id, 'entrada', :quantidade, 'Estoque inicial', NOW())"
            );
            $stmt->execute([
                ':produto_id' => $produtoId,
                ':quantidade' => $estoqueInicial
            ]);
        }
    }

    if ($hasMenuFields) {
        if (isset($_FILES['imagem']) && ($_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $arquivoNovo = menuPrepareUploadedImage($_FILES['imagem'], $produtoId);
        }

        $query = 'UPDATE produtos SET nome_publico = :nome_publico, descricao_publica = :descricao_publica';
        $params = [
            ':nome_publico' => $nomePublico,
            ':descricao_publica' => $descricaoPublica,
            ':id' => $produtoId
        ];
        if ($arquivoNovo) {
            $query .= ', imagem = :imagem';
            $params[':imagem'] = $arquivoNovo['relativo'];
        }
        $query .= ' WHERE id = :id';
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $stmt = $db->prepare('SELECT id FROM produto_cards WHERE produto_id = :produto_id');
        $stmt->execute([':produto_id' => $produtoId]);
        $idsAtuais = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $idsEnviados = [];

        foreach ($opcoes as $indice => $opcao) {
            if ($opcao['id'] !== null) {
                if (!in_array($opcao['id'], $idsAtuais, true)) {
                    throw new RuntimeException('Uma opção não pertence a este produto.');
                }
                if (in_array($opcao['id'], $idsEnviados, true)) {
                    throw new InvalidArgumentException('Uma opção foi enviada mais de uma vez.');
                }
                $idsEnviados[] = $opcao['id'];
                $stmt = $db->prepare(
                    'UPDATE produto_cards
                     SET nome_exibicao = :nome, descricao = :descricao, ordem = :ordem
                     WHERE id = :id AND produto_id = :produto_id'
                );
                $stmt->execute([
                    ':nome' => $opcao['nome'],
                    ':descricao' => $opcao['descricao'],
                    ':ordem' => $indice + 1,
                    ':id' => $opcao['id'],
                    ':produto_id' => $produtoId
                ]);
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO produto_cards (produto_id, nome_exibicao, descricao, ordem)
                     VALUES (:produto_id, :nome, :descricao, :ordem)'
                );
                $stmt->execute([
                    ':produto_id' => $produtoId,
                    ':nome' => $opcao['nome'],
                    ':descricao' => $opcao['descricao'],
                    ':ordem' => $indice + 1
                ]);
            }
        }

        foreach (array_diff($idsAtuais, $idsEnviados) as $idRemovido) {
            $stmt = $db->prepare('DELETE FROM produto_cards WHERE id = :id AND produto_id = :produto_id');
            $stmt->execute([':id' => $idRemovido, ':produto_id' => $produtoId]);
        }
    }

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => $produtoNovo ? 'Produto criado com sucesso.' : 'Produto salvo com sucesso.',
        'id' => $produtoId
    ]);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    if ($arquivoNovo) {
        menuRemoveManagedImage($arquivoNovo['relativo']);
    }
    error_log('Erro ao salvar produto: ' . $e->getMessage());
    if ($e instanceof InvalidArgumentException) {
        responderErro($e->getMessage(), 400);
    }
    if ($e instanceof RuntimeException && $e->getMessage() === 'Uma opção não pertence a este produto.') {
        responderErro($e->getMessage(), 400);
    }
    if ($e instanceof OutOfBoundsException && $e->getMessage() === 'Produto não encontrado.') {
        responderErro($e->getMessage(), 404);
    }
    responderErro('Não foi possível salvar o produto. Confira os dados e se as migrações do cardápio foram aplicadas.', 500);
}
