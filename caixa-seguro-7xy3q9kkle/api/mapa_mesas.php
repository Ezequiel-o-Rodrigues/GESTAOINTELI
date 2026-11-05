<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query(
        "SELECT
            m.id          AS mesa_id,
            m.numero      AS numero_mesa,
            m.ambiente,
            m.posicao_x,
            m.posicao_y,
            IF(COUNT(c.id) > 0, 'ocupada', 'livre') AS status_mesa,
            COUNT(c.id)   AS qtd_comandas_abertas
         FROM mesas m
         LEFT JOIN comandas c ON c.mesa_id = m.id AND c.status = 'aberta'
         WHERE m.ativo = 1
         GROUP BY m.id
         ORDER BY m.ambiente, m.numero"
    );

    echo json_encode([
        'success' => true,
        'mesas'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
