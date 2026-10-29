<?php
// API: verificar_estoque.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if (!ob_get_level()) ob_start();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $comanda_id = $data['comanda_id'] ?? null;

    if (!$comanda_id) {
        throw new Exception('Comanda ID não informado');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verificar estoque para todos os itens da comanda
    $stmt = $db->prepare("SELECT p.id, p.nome, p.estoque_atual, ic.quantidade as quantidade_solicitada, (p.estoque_atual - ic.quantidade) as estoque_apos FROM itens_comanda ic JOIN produtos p ON ic.produto_id = p.id WHERE ic.comanda_id = ?");
    $stmt->execute([$comanda_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $problemas = [];
    foreach ($itens as $item) {
        if ($item['estoque_apos'] < 0) {
            $problemas[] = [
                'produto' => $item['nome'],
                'estoque_atual' => $item['estoque_atual'],
                'quantidade_solicitada' => $item['quantidade_solicitada'],
                'deficit' => abs($item['estoque_apos'])
            ];
        }
    }

    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'success' => true,
        'estoque_suficiente' => empty($problemas),
        'problemas' => $problemas,
        'itens_verificados' => count($itens)
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

?>