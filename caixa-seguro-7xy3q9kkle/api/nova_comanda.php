<?php
require_once __DIR__ . '/../includes/api-auth.php';
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $db = $database->getConnection();

    $mesa_id = filter_var($data['mesa_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

    if ($mesa_id !== null) {
        $check = $db->prepare("SELECT numero FROM mesas WHERE id = ? AND ativo = 1");
        $check->execute([$mesa_id]);
        $mesa = $check->fetch(PDO::FETCH_ASSOC);
        if (!$mesa) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mesa não encontrada ou inativa.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $mesa_numero = $mesa['numero'];
    } else {
        $mesa_numero = null;
    }

    $stmt = $db->prepare(
        "INSERT INTO comandas (garcom_usuario_id, mesa_id, mesa_numero, status, valor_total, taxa_gorjeta, created_at, updated_at)
         VALUES (?, ?, ?, 'aberta', 0, 0, NOW(), NOW())"
    );
    $stmt->execute([(int) $_SESSION['usuario_id'], $mesa_id, $mesa_numero]);

    echo json_encode([
        'success'    => true,
        'comanda_id' => (int) $db->lastInsertId(),
        'message'    => 'Comanda criada com sucesso'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao criar comanda: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>