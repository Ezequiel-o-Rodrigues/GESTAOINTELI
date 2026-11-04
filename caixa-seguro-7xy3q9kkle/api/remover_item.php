<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$comandaId = filter_var(is_array($data) ? ($data['comanda_id'] ?? null) : null, FILTER_VALIDATE_INT);
$itemId = filter_var(is_array($data) ? ($data['item_id'] ?? null) : null, FILTER_VALIDATE_INT);
if (!$comandaId || $comandaId < 1 || !$itemId || $itemId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comanda ou item inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id FROM comandas WHERE id = ? AND status = 'aberta' FOR UPDATE");
    $stmt->execute([(int) $comandaId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
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

    $stmt = $db->prepare('SELECT COALESCE(SUM(subtotal), 0) FROM itens_comanda WHERE comanda_id = ?');
    $stmt->execute([(int) $comandaId]);
    $total = $stmt->fetchColumn();
    $stmt = $db->prepare("UPDATE comandas SET valor_total = ? WHERE id = ? AND status = 'aberta'");
    $stmt->execute([$total, (int) $comandaId]);
    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Item removido com sucesso.'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao remover item da comanda no caixa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível remover o item.'], JSON_UNESCAPED_UNICODE);
}
?>
