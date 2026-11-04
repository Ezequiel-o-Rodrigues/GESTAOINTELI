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
    $stmt = $db->prepare(
        "SELECT c.id, c.cliente_nome, c.mesa_numero, c.valor_total, c.created_at,
                COALESCE(u.nome, g.nome) AS garcom_nome
         FROM comandas c
         LEFT JOIN usuarios u ON u.id = c.garcom_usuario_id
         LEFT JOIN garcons g ON g.id = c.garcom_id
         WHERE c.status = 'aberta'
         ORDER BY c.created_at DESC, c.id DESC"
    );
    $stmt->execute();
    echo json_encode(['success' => true, 'comandas' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Erro ao listar comandas abertas para o caixa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar as comandas abertas.'], JSON_UNESCAPED_UNICODE);
}
?>
