<?php
require_once __DIR__ . '/../includes/api-auth.php';
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$comandaId = filter_var($_GET['comanda_id'] ?? null, FILTER_VALIDATE_INT);
if (!$comandaId || $comandaId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Informe uma comanda válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id FROM comandas WHERE id = ? AND status = 'aberta'");
    $stmt->execute([(int) $comandaId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comanda aberta não encontrada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $query = "SELECT ic.id, ic.comanda_id, ic.produto_id, ic.quantidade, ic.subtotal, p.nome as nome_produto
              FROM itens_comanda ic 
              JOIN produtos p ON ic.produto_id = p.id 
              WHERE ic.comanda_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([(int) $comandaId]);
    
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['itens' => $itens]);
    
} catch (PDOException $e) {
    error_log('Erro ao carregar itens da comanda no caixa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar os itens da comanda.'], JSON_UNESCAPED_UNICODE);
}
?>