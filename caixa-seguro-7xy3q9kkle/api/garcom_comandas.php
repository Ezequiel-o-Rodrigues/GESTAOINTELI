<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->prepare(
            "SELECT c.id, c.cliente_nome, c.mesa_numero, c.mesa_id, c.valor_total, c.created_at,
                    COALESCE(u.nome, g.nome) AS garcom_nome
             FROM comandas c
             LEFT JOIN usuarios u ON u.id = c.garcom_usuario_id
             LEFT JOIN garcons g ON g.id = c.garcom_id
             WHERE c.status = 'aberta'
             ORDER BY c.created_at DESC, c.id DESC"
        );
        $stmt->execute();
        echo json_encode(['success' => true, 'comandas' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
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
        echo json_encode(['success' => false, 'message' => 'Dados da comanda inválidos.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $clienteNome = isset($data['cliente_nome']) && is_string($data['cliente_nome'])
        ? trim($data['cliente_nome'])
        : '';
    $mesaInput = $data['mesa_numero'] ?? '';
    $mesaNumero = is_string($mesaInput) || is_int($mesaInput) ? trim((string) $mesaInput) : '';
    $nomeComprimento = preg_match_all('/./us', $clienteNome, $nomeCaracteres);

    if ($clienteNome === '' && $mesaNumero === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Informe o nome do cliente ou o número da mesa.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($nomeComprimento === false || $nomeComprimento > 120 || strlen($mesaNumero) > 20
        || ($mesaNumero !== '' && !ctype_digit($mesaNumero))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Confira o nome do cliente e o número da mesa.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $db->prepare(
        "INSERT INTO comandas (garcom_usuario_id, cliente_nome, mesa_numero, status, valor_total, created_at, updated_at)
         VALUES (?, ?, ?, 'aberta', 0, NOW(), NOW())"
    );
    $stmt->execute([
        (int) $_SESSION['usuario_id'],
        $clienteNome === '' ? null : $clienteNome,
        $mesaNumero === '' ? null : $mesaNumero
    ]);

    echo json_encode([
        'success' => true,
        'comanda_id' => (int) $db->lastInsertId(),
        'message' => 'Comanda aberta.'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Erro ao carregar ou criar comanda do garçom: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a comanda.'], JSON_UNESCAPED_UNICODE);
}
?>
