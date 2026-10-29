<?php
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$comanda_id = $data['comanda_id'] ?? null;
$item_id = $data['item_id'] ?? null;

if (!$comanda_id || !$item_id) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar informações do item antes de remover
    $query_item = "SELECT * FROM itens_comanda WHERE id = ? AND comanda_id = ?";
    $stmt_item = $db->prepare($query_item);
    $stmt_item->execute([$item_id, $comanda_id]);
    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
        exit;
    }
    
    // Remover o item
    $query_delete = "DELETE FROM itens_comanda WHERE id = ? AND comanda_id = ?";
    $stmt_delete = $db->prepare($query_delete);
    $stmt_delete->execute([$item_id, $comanda_id]);
    
    // Atualizar o total da comanda
    $query_update = "UPDATE comandas SET valor_total = valor_total - ? WHERE id = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([$item['subtotal'], $comanda_id]);
    
    echo json_encode(['success' => true, 'message' => 'Item removido com sucesso']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>