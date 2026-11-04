<?php
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/paths.php';

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$destino = PathConfig::modules('cardapio/');
if ($queryString !== '') {
    $destino .= '?' . $queryString;
}

header('Location: ' . $destino, true, 302);
exit;
