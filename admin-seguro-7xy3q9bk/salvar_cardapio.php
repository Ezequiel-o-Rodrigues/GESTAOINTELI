<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_cardapio']) || !is_string($token) || !hash_equals($_SESSION['csrf_cardapio'], $token)) {
    http_response_code(403);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$categoriaId = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
$nome = trim((string) ($_POST['nome'] ?? ''));
$preco = str_replace(',', '.', trim((string) ($_POST['preco'] ?? '')));
$nomeComprimento = preg_match_all('/./us', $nome, $caracteresNome);

if (!$id || !$categoriaId || $nome === '' || $nomeComprimento === false || $nomeComprimento > 100 || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $preco)) {
    header('Location: gerenciar_cardapio.php?erro=' . urlencode('Confira nome e preço informados.'));
    exit;
}

$arquivoNovo = null;

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados do caixa.');
    }

    $stmt = $db->prepare('SELECT id FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Produto não encontrado.');
    }

    $stmt = $db->prepare('SELECT id FROM categorias WHERE id = :id');
    $stmt->execute([':id' => $categoriaId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('A categoria selecionada não existe.');
    }

    $temImagem = isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE;
    $novoToken = bin2hex(random_bytes(32));

    if ($temImagem) {
        $upload = $_FILES['imagem'];
        if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            throw new RuntimeException('Não foi possível receber a imagem enviada.');
        }
        if ($upload['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
        }

        $imagemInfo = getimagesize($upload['tmp_name']);
        $extensoesMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        if (!$imagemInfo || !isset($extensoesMime[$imagemInfo['mime']])) {
            throw new RuntimeException('Envie uma imagem JPG, PNG, GIF ou WebP válida.');
        }

        $diretorio = __DIR__ . '/../public/images/menu/products';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
        }

        $nomeArquivo = $id . '_' . bin2hex(random_bytes(8)) . '.' . $extensoesMime[$imagemInfo['mime']];
        $arquivoNovo = $diretorio . DIRECTORY_SEPARATOR . $nomeArquivo;
        if (!move_uploaded_file($upload['tmp_name'], $arquivoNovo)) {
            throw new RuntimeException('Falha ao salvar a imagem enviada.');
        }
        $imagemRelativa = 'products/' . $nomeArquivo;
    }

    if ($temImagem) {
        $stmt = $db->prepare(
            'UPDATE produtos
             SET nome = :nome, preco = :preco, categoria_id = :categoria_id, imagem = :imagem
             WHERE id = :id'
        );
        $stmt->execute([
            ':nome' => $nome,
            ':preco' => $preco,
            ':categoria_id' => $categoriaId,
            ':imagem' => $imagemRelativa,
            ':id' => $id
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
            ':id' => $id
        ]);
    }

    $_SESSION['csrf_cardapio'] = $novoToken;
    header('Location: gerenciar_cardapio.php?msg=' . urlencode('Produto atualizado.'));
    exit;
} catch (Throwable $e) {
    if ($arquivoNovo !== null && is_file($arquivoNovo)) {
        unlink($arquivoNovo);
    }
    error_log('Erro ao atualizar produto do cardápio: ' . $e->getMessage());
    header('Location: gerenciar_cardapio.php?erro=' . urlencode($e->getMessage()));
    exit;
}
