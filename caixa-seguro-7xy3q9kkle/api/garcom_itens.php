<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../includes/garcom-comanda.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function responderItensGarcom(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $comandaId = filter_var($_GET['comanda_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$comandaId || $comandaId < 1) {
            responderItensGarcom(400, ['success' => false, 'message' => 'Comanda inválida.']);
        }
        $stmt = $db->prepare("SELECT id FROM comandas WHERE id = ? AND status = 'aberta'");
        $stmt->execute([(int) $comandaId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            responderItensGarcom(404, ['success' => false, 'message' => 'Comanda aberta não encontrada.']);
        }

        $stmt = $db->prepare(
            'SELECT ic.id, ic.produto_id, ic.quantidade, ic.preco_unitario, ic.subtotal,
                    COALESCE(ic.garcom_nome, u.nome) AS garcom_nome, p.nome AS nome_produto
             FROM itens_comanda ic
             JOIN produtos p ON p.id = ic.produto_id
             LEFT JOIN usuarios u ON u.id = ic.garcom_usuario_id
             WHERE ic.comanda_id = ? AND ic.cancelado_em IS NULL
             ORDER BY ic.id'
        );
        $stmt->execute([(int) $comandaId]);
        echo json_encode(['success' => true, 'itens' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderItensGarcom(405, ['success' => false, 'message' => 'Método não permitido.']);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        responderItensGarcom(400, ['success' => false, 'message' => 'Dados da operação inválidos.']);
    }

    $comandaId = filter_var($data['comanda_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$comandaId || $comandaId < 1) {
        responderItensGarcom(400, ['success' => false, 'message' => 'Comanda inválida.']);
    }

    $db->beginTransaction();
    if (!buscarComandaAbertaDoGarcom($db, (int) $comandaId)) {
        $db->rollBack();
        responderItensGarcom(404, ['success' => false, 'message' => 'Comanda aberta não encontrada.']);
    }

    if (array_key_exists('item_id', $data)) {
        $itemId = filter_var($data['item_id'], FILTER_VALIDATE_INT);
        $quantity = filter_var($data['nova_quantidade'] ?? null, FILTER_VALIDATE_INT);
        if (!$itemId || $itemId < 1 || !$quantity || $quantity < 1) {
            $db->rollBack();
            responderItensGarcom(400, ['success' => false, 'message' => 'Item ou quantidade inválidos.']);
        }
        if (!ajustarQuantidadeItemComanda(
            $db,
            (int) $comandaId,
            (int) $itemId,
            (int) $quantity,
            (int) $_SESSION['usuario_id']
        )) {
            $db->rollBack();
            responderItensGarcom(404, ['success' => false, 'message' => 'Item não encontrado nesta comanda.']);
        }
    } else {
        $productId = filter_var($data['produto_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($data['quantidade'] ?? 1, FILTER_VALIDATE_INT);
        if (!$productId || $productId < 1 || !$quantity || $quantity < 1) {
            $db->rollBack();
            responderItensGarcom(400, ['success' => false, 'message' => 'Produto ou quantidade inválidos.']);
        }

        $garcom = buscarGarcomAtivo($db, (int) $_SESSION['usuario_id']);
        $stmt = $db->prepare('SELECT preco, estoque_atual, nome FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE');
        $stmt->execute([(int) $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            $db->rollBack();
            responderItensGarcom(404, ['success' => false, 'message' => 'Produto não encontrado ou inativo.']);
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
            responderItensGarcom(400, ['success' => false, 'message' => 'Estoque insuficiente para ' . $product['nome'] . '.']);
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
    responderItensGarcom(403, ['success' => false, 'message' => $e->getMessage()]);
} catch (OutOfBoundsException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responderItensGarcom(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao atualizar itens da comanda do garçom: ' . $e->getMessage());
    responderItensGarcom(500, ['success' => false, 'message' => 'Não foi possível atualizar a comanda.']);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao calcular comissão dos itens do garçom: ' . $e->getMessage());
    responderItensGarcom(500, ['success' => false, 'message' => 'Não foi possível calcular a comissão do item.']);
}
?>
