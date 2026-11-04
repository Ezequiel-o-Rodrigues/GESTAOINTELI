<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../includes/comissao-comanda.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function responderItem(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderItem(405, ['success' => false, 'message' => 'Método não permitido.']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    responderItem(400, ['success' => false, 'message' => 'Dados da operação inválidos.']);
}

$comandaId = filter_var($data['comanda_id'] ?? null, FILTER_VALIDATE_INT);
$itemId = filter_var($data['item_id'] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($data['nova_quantidade'] ?? $data['quantidade'] ?? 1, FILTER_VALIDATE_INT);
if (!$comandaId || $comandaId < 1 || !$quantity || $quantity < 1
    || (isset($data['item_id']) && (!$itemId || $itemId < 1 || !array_key_exists('nova_quantidade', $data)))) {
    responderItem(400, ['success' => false, 'message' => 'Comanda, item ou quantidade inválidos.']);
}

$isQuantityChange = isset($data['item_id']);
if (!$isQuantityChange && currentUserRole() !== 'garcom') {
    responderItem(403, ['success' => false, 'message' => 'Entre com uma conta ativa de garçom para lançar produtos.']);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id FROM comandas WHERE id = ? AND status = 'aberta' FOR UPDATE");
    $stmt->execute([(int) $comandaId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $db->rollBack();
        responderItem(404, ['success' => false, 'message' => 'Comanda aberta não encontrada.']);
    }

    if ($isQuantityChange) {
        if (!ajustarQuantidadeItemComanda(
            $db,
            (int) $comandaId,
            (int) $itemId,
            (int) $quantity,
            (int) $_SESSION['usuario_id']
        )) {
            $db->rollBack();
            responderItem(404, ['success' => false, 'message' => 'Item não encontrado nesta comanda.']);
        }
    } else {
        $productId = filter_var($data['produto_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId || $productId < 1) {
            $db->rollBack();
            responderItem(400, ['success' => false, 'message' => 'Produto inválido.']);
        }

        $garcom = buscarGarcomAtivo($db, (int) $_SESSION['usuario_id']);
        $stmt = $db->prepare('SELECT estoque_atual, nome, preco FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE');
        $stmt->execute([(int) $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            $db->rollBack();
            responderItem(404, ['success' => false, 'message' => 'Produto não encontrado ou inativo.']);
        }

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(ic.quantidade), 0)
             FROM itens_comanda ic
             JOIN comandas c ON c.id = ic.comanda_id
             WHERE ic.produto_id = ? AND ic.cancelado_em IS NULL AND c.status = 'aberta'"
        );
        $stmt->execute([(int) $productId]);
        $reserved = (int) $stmt->fetchColumn();
        if ($reserved + (int) $quantity > (int) $product['estoque_atual']) {
            $db->rollBack();
            responderItem(400, ['success' => false, 'message' => 'Estoque insuficiente para o produto: ' . $product['nome']]);
        }

        inserirItemComissao(
            $db,
            (int) $comandaId,
            (int) $productId,
            (int) $quantity,
            (float) $product['preco'],
            (int) $garcom['id'],
            $garcom['nome'],
            obterTaxaComissao($db)
        );
        atualizarTotalComanda($db, (int) $comandaId);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Comanda atualizada.'], JSON_UNESCAPED_UNICODE);
} catch (DomainException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responderItem(403, ['success' => false, 'message' => $e->getMessage()]);
} catch (OutOfBoundsException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responderItem(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao atualizar item da comanda no caixa: ' . $e->getMessage());
    responderItem(500, ['success' => false, 'message' => 'Não foi possível atualizar a comanda.']);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao calcular comissão do item no caixa: ' . $e->getMessage());
    responderItem(500, ['success' => false, 'message' => 'Não foi possível calcular a comissão do item.']);
}
?>
