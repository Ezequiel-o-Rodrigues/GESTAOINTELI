<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../includes/garcom-comanda.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
    $garcomId = (int) $_SESSION['usuario_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $comandaId = filter_var($_GET['comanda_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$comandaId || !buscarComandaAbertaDoGarcom($db, (int) $comandaId, $garcomId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comanda aberta não encontrada.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare(
            'SELECT ic.id, ic.produto_id, ic.quantidade, ic.preco_unitario, ic.subtotal, p.nome AS nome_produto
             FROM itens_comanda ic
             JOIN produtos p ON p.id = ic.produto_id
             WHERE ic.comanda_id = ?
             ORDER BY ic.id'
        );
        $stmt->execute([(int) $comandaId]);
        echo json_encode(['success' => true, 'itens' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
        exit;
    }

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
    if (!$comandaId || $comandaId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comanda inválida.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->beginTransaction();
    if (!buscarComandaAbertaDoGarcom($db, (int) $comandaId, $garcomId)) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comanda aberta não encontrada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (array_key_exists('item_id', $data)) {
        $itemId = filter_var($data['item_id'], FILTER_VALIDATE_INT);
        $novaQuantidade = filter_var($data['nova_quantidade'] ?? null, FILTER_VALIDATE_INT);
        if (!$itemId || $itemId < 1 || !$novaQuantidade || $novaQuantidade < 1) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Item ou quantidade inválidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare(
            'SELECT ic.preco_unitario, p.estoque_atual
             FROM itens_comanda ic
             JOIN produtos p ON p.id = ic.produto_id
             WHERE ic.id = ? AND ic.comanda_id = ?'
        );
        $stmt->execute([(int) $itemId, (int) $comandaId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Item não encontrado nesta comanda.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ((int) $item['estoque_atual'] < (int) $novaQuantidade) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'A quantidade solicitada excede o estoque atual.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare('UPDATE itens_comanda SET quantidade = ?, subtotal = ? * ? WHERE id = ? AND comanda_id = ?');
        $stmt->execute([(int) $novaQuantidade, $item['preco_unitario'], (int) $novaQuantidade, (int) $itemId, (int) $comandaId]);
    } else {
        $produtoId = filter_var($data['produto_id'] ?? null, FILTER_VALIDATE_INT);
        $quantidade = filter_var($data['quantidade'] ?? 1, FILTER_VALIDATE_INT);
        if (!$produtoId || $produtoId < 1 || !$quantidade || $quantidade < 1) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Produto ou quantidade inválidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare('SELECT preco, estoque_atual, nome FROM produtos WHERE id = ? AND ativo = 1');
        $stmt->execute([(int) $produtoId]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$produto) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado ou inativo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt = $db->prepare('SELECT id, quantidade FROM itens_comanda WHERE comanda_id = ? AND produto_id = ?');
        $stmt->execute([(int) $comandaId, (int) $produtoId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        $quantidadeAtual = $item ? (int) $item['quantidade'] : 0;
        if ((int) $produto['estoque_atual'] < $quantidadeAtual + (int) $quantidade) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Estoque insuficiente para ' . $produto['nome'] . '.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($item) {
            $novaQuantidade = (int) $item['quantidade'] + (int) $quantidade;
            $stmt = $db->prepare('UPDATE itens_comanda SET quantidade = ?, subtotal = ? * ? WHERE id = ?');
            $stmt->execute([$novaQuantidade, $produto['preco'], $novaQuantidade, (int) $item['id']]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO itens_comanda (comanda_id, produto_id, quantidade, preco_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $comandaId,
                (int) $produtoId,
                (int) $quantidade,
                $produto['preco'],
                (float) $produto['preco'] * (int) $quantidade
            ]);
        }
    }

    atualizarTotalComanda($db, (int) $comandaId);
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Comanda atualizada.'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao atualizar itens da comanda do garçom: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível atualizar a comanda.'], JSON_UNESCAPED_UNICODE);
}
?>
