<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    $stmt = $db->query(
        "SELECT
            m.id          AS mesa_id,
            m.numero      AS numero_mesa,
            m.ambiente,
            m.posicao_x,
            m.posicao_y,
            COALESCE(m.formato, 'quadrada')  AS formato,
            COALESCE(m.largura, 60)          AS largura,
            COALESCE(m.altura,  60)          AS altura,
            IF(COUNT(c.id) > 0, 'ocupada', 'livre') AS status_mesa,
            COUNT(c.id)   AS qtd_comandas_abertas
         FROM mesas m
         LEFT JOIN comandas c
           ON (c.mesa_id = m.id OR (
                c.mesa_id IS NULL
                AND CONVERT(c.mesa_numero USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    = CONVERT(m.numero USING utf8mb4) COLLATE utf8mb4_unicode_ci
           ))
          AND c.status = 'aberta'
         WHERE m.ativo = 1
         GROUP BY m.id, m.numero, m.ambiente, m.posicao_x, m.posicao_y, m.formato, m.largura, m.altura
         ORDER BY m.ambiente, m.numero"
    );

    echo json_encode([
        'success' => true,
        'mesas'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('Erro ao carregar mapa de mesas: ' . $e->getMessage());
    http_response_code(500);
    $driverCode = (int) ($e->errorInfo[1] ?? 0);
    $message = in_array($driverCode, [1054, 1146], true)
        ? 'A estrutura do mapa de mesas está incompleta. Execute a migração migracao_mapa_mesas.sql no banco e atualize a página.'
        : 'Não foi possível carregar o mapa de mesas. Verifique o log do servidor para identificar o erro.';
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Erro ao carregar mapa de mesas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar o mapa de mesas. Verifique o log do servidor para identificar o erro.'], JSON_UNESCAPED_UNICODE);
}
