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

try {
    $csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_admin_user']) || !hash_equals($_SESSION['csrf_admin_user'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sessão expirada. Atualize a página.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($data['mesas']) || !is_array($data['mesas'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payload inválido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $formatos = ['quadrada', 'redonda', 'retangular'];
    $stmt = $db->prepare(
        'UPDATE mesas SET posicao_x = ?, posicao_y = ?, formato = ?, largura = ?, altura = ? WHERE id = ?'
    );

    $db->beginTransaction();
    foreach ($data['mesas'] as $m) {
        $id      = filter_var($m['id'] ?? null, FILTER_VALIDATE_INT);
        $x       = filter_var($m['posicao_x'] ?? 0, FILTER_VALIDATE_INT);
        $y       = filter_var($m['posicao_y'] ?? 0, FILTER_VALIDATE_INT);
        $formato = in_array($m['formato'] ?? '', $formatos, true) ? $m['formato'] : 'quadrada';
        $largura = max(40, min(200, (int) ($m['largura'] ?? 60)));
        $altura  = max(40, min(200, (int) ($m['altura']  ?? 60)));

        if (!$id || $x === false || $y === false || $x < 0 || $y < 0) continue;
        $stmt->execute([$x, $y, $formato, $largura, $altura, $id]);
    }
    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Layout salvo.'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
    error_log('Erro ao salvar layout: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível salvar o layout.'], JSON_UNESCAPED_UNICODE);
}
