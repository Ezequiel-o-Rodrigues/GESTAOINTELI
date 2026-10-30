<?php
function menuIsValidImageUrl($url) {
    if (!is_string($url) || strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    $partes = parse_url($url);
    if (
        !$partes
        || strtolower($partes['scheme'] ?? '') !== 'https'
        || empty($partes['host'])
        || isset($partes['user'])
        || isset($partes['pass'])
        || (isset($partes['port']) && (int) $partes['port'] !== 443)
    ) {
        return false;
    }

    $host = strtolower(rtrim(trim($partes['host'], '[]'), '.'));
    if ($host === '' || $host === 'localhost' || preg_match('/\.local$|\.internal$/i', $host)) {
        return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    return true;
}

function menuHasLocalImage($localPath, $imageDirectory) {
    $relative = str_replace('\\', '/', (string) ($localPath ?? ''));
    if (
        $relative === ''
        || strpos($relative, '..') !== false
        || $relative[0] === '/'
        || strtolower(basename($relative)) === 'default.png'
    ) {
        return false;
    }

    $baseReal = realpath($imageDirectory);
    $fileReal = realpath($imageDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    return $baseReal !== false
        && $fileReal !== false
        && is_file($fileReal)
        && strpos($fileReal, $baseReal . DIRECTORY_SEPARATOR) === 0;
}

function menuResolveImageSource($localPath, $remoteUrl, $imageDirectory, $urlPrefix) {
    $relative = str_replace('\\', '/', (string) ($localPath ?? ''));
    if (menuHasLocalImage($relative, $imageDirectory)) {
        return $urlPrefix . $relative;
    }

    if (menuIsValidImageUrl($remoteUrl)) {
        return $remoteUrl;
    }

    return $urlPrefix . 'default.png';
}

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

    $conteudo = file_get_contents($arquivo['tmp_name']);
    if ($conteudo === false) {
        throw new RuntimeException('Não foi possível ler a imagem enviada.');
    }

    return menuStoreImageContents($conteudo, $produtoId);
}

function menuStoreImageContents($conteudo, $produtoId) {
    if (!is_string($conteudo) || $conteudo === '' || strlen($conteudo) > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('A imagem deve ter no máximo 5 MB.');
    }

    $imagemInfo = getimagesizefromstring($conteudo);
    $extensoesMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    if (
        !$imagemInfo
        || !isset($imagemInfo['mime'])
        || !isset($extensoesMime[$imagemInfo['mime']])
        || $imagemInfo[0] > 10000
        || $imagemInfo[1] > 10000
    ) {
        throw new InvalidArgumentException('Envie uma imagem JPG, PNG, GIF ou WebP válida.');
    }

    $diretorio = __DIR__ . '/../public/images/menu/products';
    if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
        throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
    }

    $nomeArquivo = $produtoId . '_' . bin2hex(random_bytes(8)) . '.' . $extensoesMime[$imagemInfo['mime']];
    $caminho = $diretorio . DIRECTORY_SEPARATOR . $nomeArquivo;
    if (file_put_contents($caminho, $conteudo, LOCK_EX) !== strlen($conteudo)) {
        if (is_file($caminho) && !unlink($caminho)) {
            error_log('Não foi possível remover arquivo de imagem incompleto: ' . $caminho);
        }
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
