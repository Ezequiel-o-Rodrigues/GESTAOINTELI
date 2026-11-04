<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../includes/garcom-comanda.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados da operação inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$comandaId = filter_var($data['comanda_id'] ?? null, FILTER_VALIDATE_INT);
$itemId = filter_var($data['item_id'] ?? null, FILTER_VALIDATE_INT);
if (!$comandaId || $comandaId < 1 || !$itemId || $itemId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comanda ou item inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    if (!buscarComandaAbertaDoGarcom($db, (int) $comandaId, (int) $_SESSION['usuario_id'])) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comanda aberta não encontrada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $db->prepare('DELETE FROM itens_comanda WHERE id = ? AND comanda_id = ?');
    $stmt->execute([(int) $itemId, (int) $comandaId]);
    if ($stmt->rowCount() < 1) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item não encontrado nesta comanda.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    atualizarTotalComanda($db, (int) $comandaId);
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Item removido.'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao remover item da comanda do garçom: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível remover o item.'], JSON_UNESCAPED_UNICODE);
}
?>
