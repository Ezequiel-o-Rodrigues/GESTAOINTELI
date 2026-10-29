<?php
function menuPrepareUploadedImage($arquivo, $produtoId) {
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
        throw new InvalidArgumentException('Não foi possível receber a imagem enviada.');
    }
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('A imagem deve ter no máximo 5 MB.');
    }

    $imagemInfo = getimagesize($arquivo['tmp_name']);
    $extensoesMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    if (!$imagemInfo || !isset($imagemInfo['mime']) || !isset($extensoesMime[$imagemInfo['mime']])) {
        throw new InvalidArgumentException('Envie uma imagem JPG, PNG, GIF ou WebP válida.');
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

function menuRemoveManagedImage($imagemRelativa) {
    $imagemRelativa = str_replace('\\', '/', (string) $imagemRelativa);
    if (strpos($imagemRelativa, 'products/') !== 0 || strpos($imagemRelativa, '..') !== false) {
        return;
    }

    $diretorioGerenciado = realpath(__DIR__ . '/../public/images/menu/products');
    $arquivo = __DIR__ . '/../public/images/menu/' . $imagemRelativa;
    if ($diretorioGerenciado && realpath(dirname($arquivo)) === $diretorioGerenciado && is_file($arquivo)) {
        if (!unlink($arquivo)) {
            error_log('Não foi possível remover a imagem gerenciada: ' . $imagemRelativa);
        }
    }
}
