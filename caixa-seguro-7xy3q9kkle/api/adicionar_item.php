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
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados da operação inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$comandaId = filter_var(is_array($data) ? ($data['comanda_id'] ?? null) : null, FILTER_VALIDATE_INT);
if (!$comandaId || $comandaId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Informe uma comanda válida.'], JSON_UNESCAPED_UNICODE);
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

    if (isset($data['item_id']) && array_key_exists('nova_quantidade', $data)) {
        $itemId = filter_var($data['item_id'], FILTER_VALIDATE_INT);
        $quantity = filter_var($data['nova_quantidade'], FILTER_VALIDATE_INT);
        if (!$itemId || $itemId < 1 || !$quantity || $quantity < 1) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Item ou quantidade inválidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare('SELECT preco_unitario FROM itens_comanda WHERE id = ? AND comanda_id = ?');
        $stmt->execute([(int) $itemId, (int) $comandaId]);
        $price = $stmt->fetchColumn();
        if ($price === false) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Item não encontrado nesta comanda.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare('UPDATE itens_comanda SET quantidade = ?, subtotal = ? * ? WHERE id = ? AND comanda_id = ?');
        $stmt->execute([(int) $quantity, $price, (int) $quantity, (int) $itemId, (int) $comandaId]);
    } else {
        $productId = filter_var($data['produto_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($data['quantidade'] ?? 1, FILTER_VALIDATE_INT);
        if (!$productId || $productId < 1 || !$quantity || $quantity < 1) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Produto ou quantidade inválidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare('SELECT estoque_atual, nome, preco FROM produtos WHERE id = ? AND ativo = 1');
        $stmt->execute([(int) $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado ou inativo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ((int) $product['estoque_atual'] < (int) $quantity) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Estoque insuficiente para o produto: ' . $product['nome']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare('SELECT id, quantidade FROM itens_comanda WHERE comanda_id = ? AND produto_id = ?');
        $stmt->execute([(int) $comandaId, (int) $productId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($item) {
            $newQuantity = (int) $item['quantidade'] + (int) $quantity;
            $stmt = $db->prepare('UPDATE itens_comanda SET quantidade = ?, subtotal = ? * ? WHERE id = ?');
            $stmt->execute([$newQuantity, $product['preco'], $newQuantity, (int) $item['id']]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO itens_comanda (comanda_id, produto_id, quantidade, preco_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $comandaId,
                (int) $productId,
                (int) $quantity,
                $product['preco'],
                (float) $product['preco'] * (int) $quantity
            ]);
        }
    }

    $stmt = $db->prepare('SELECT COALESCE(SUM(subtotal), 0) FROM itens_comanda WHERE comanda_id = ?');
    $stmt->execute([(int) $comandaId]);
    $total = $stmt->fetchColumn();
    $stmt = $db->prepare("UPDATE comandas SET valor_total = ? WHERE id = ? AND status = 'aberta'");
    $stmt->execute([$total, (int) $comandaId]);
    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Comanda atualizada.'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao atualizar item da comanda no caixa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível atualizar a comanda.'], JSON_UNESCAPED_UNICODE);
}
?>
