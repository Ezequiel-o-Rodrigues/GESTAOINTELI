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

function menuImportImageFromUrl($url, $produtoId) {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A importação por URL requer a extensão cURL habilitada no servidor.');
    }
    if (!is_string($url) || strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
        throw new InvalidArgumentException('Informe uma URL válida que aponte diretamente para uma imagem.');
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
        throw new InvalidArgumentException('Use uma URL HTTPS pública, sem credenciais e na porta padrão.');
    }

    $host = strtolower(rtrim($partes['host'], '.'));
    if ($host === '' || $host === 'localhost' || preg_match('/\.local$|\.internal$/i', $host)) {
        throw new InvalidArgumentException('O endereço da imagem não é permitido.');
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $enderecos = [$host];
    } elseif (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        throw new InvalidArgumentException('Use um endereço de imagem com nome de domínio público.');
    } else {
        $registrosDns = dns_get_record($host, DNS_A);
        $enderecos = [];
        foreach ($registrosDns ?: [] as $registro) {
            if (isset($registro['ip'])) {
                $enderecos[] = $registro['ip'];
            }
        }
    }

    if (empty($enderecos)) {
        throw new InvalidArgumentException('Não foi possível localizar um endereço público para a imagem.');
    }
    foreach ($enderecos as $endereco) {
        if (!filter_var($endereco, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new InvalidArgumentException('O domínio da imagem resolve para um endereço não público.');
        }
    }

    $endereco = $enderecos[0];
    $porta = isset($partes['port']) ? (int) $partes['port'] : 443;
    $cabecalhoHost = $host;
    $path = $partes['path'] ?? '/';
    if (isset($partes['query'])) {
        $path .= '?' . $partes['query'];
    }
    $urlValidada = 'https://' . $cabecalhoHost . ($porta === 443 ? '' : ':' . $porta) . $path;
    $conteudo = '';
    $excedeuLimite = false;
    $curl = curl_init($urlValidada);
    if ($curl === false) {
        throw new RuntimeException('Não foi possível iniciar a importação da imagem.');
    }

    curl_setopt_array($curl, [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROXY => '',
        CURLOPT_RESOLVE => [$host . ':' . $porta . ':' . $endereco],
        CURLOPT_USERAGENT => 'GestaoInteliMenuImage/1.0',
        CURLOPT_WRITEFUNCTION => static function ($handle, $chunk) use (&$conteudo, &$excedeuLimite) {
            if (strlen($conteudo) + strlen($chunk) > 5 * 1024 * 1024) {
                $excedeuLimite = true;
                return 0;
            }
            $conteudo .= $chunk;
            return strlen($chunk);
        }
    ]);

    $resultado = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $erro = curl_error($curl);
    curl_close($curl);

    if ($excedeuLimite) {
        throw new InvalidArgumentException('A imagem deve ter no máximo 5 MB.');
    }
    if ($resultado === false) {
        error_log('Falha ao importar imagem HTTPS: ' . $erro);
        throw new RuntimeException('Não foi possível baixar a imagem. Use o link direto do arquivo e tente novamente.');
    }
    if ($status !== 200) {
        throw new InvalidArgumentException('O endereço não retornou uma imagem (HTTP ' . $status . '). Redirecionamentos não são aceitos; informe a URL direta do arquivo.');
    }

    return menuStoreImageContents($conteudo, $produtoId);
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
