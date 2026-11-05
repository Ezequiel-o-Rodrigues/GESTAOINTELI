<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'service_fee_enabled'");
    $stmt->execute();
    $repasseAtivo = (string) $stmt->fetchColumn() === '1';
    $stmt = $db->prepare(
        "SELECT c.id, c.cliente_nome, c.mesa_numero,
                (
                    SELECT COALESCE(SUM(ic.subtotal), 0)
                    FROM itens_comanda ic
                    WHERE ic.comanda_id = c.id AND ic.cancelado_em IS NULL
                ) + (
                    SELECT COALESCE(SUM(il.subtotal), 0)
                    FROM itens_livres il
                    WHERE il.comanda_id = c.id
                ) AS valor_total,
                (
                    SELECT COALESCE(SUM(ic.comissao_valor), 0)
                    FROM itens_comanda ic
                    WHERE ic.comanda_id = c.id AND ic.cancelado_em IS NULL
                ) AS comissao_itens,
                COALESCE(u.nome, g.nome) AS garcom_nome
         FROM comandas c
         LEFT JOIN usuarios u ON u.id = c.garcom_usuario_id
         LEFT JOIN garcons g ON g.id = c.garcom_id
         WHERE c.status = 'aberta'
         ORDER BY c.created_at DESC, c.id DESC"
    );
    $stmt->execute();
    $comandas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comandas as &$comanda) {
        $subtotal = (float) $comanda['valor_total'];
        $gorjeta = $repasseAtivo ? (float) $comanda['comissao_itens'] : 0;
        $comanda['taxa_gorjeta'] = $gorjeta;
        $comanda['total_a_pagar'] = round($subtotal + $gorjeta, 2);
    }
    unset($comanda);
    echo json_encode(['success' => true, 'comandas' => $comandas], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Erro ao listar comandas abertas para o caixa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar as comandas abertas.'], JSON_UNESCAPED_UNICODE);
}
?>
