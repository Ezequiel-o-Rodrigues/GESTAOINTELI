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
$responseStatus = 500;

try {
    $csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_admin_user']) || !hash_equals($_SESSION['csrf_admin_user'], $csrfToken)) {
        $responseStatus = 403;
        throw new RuntimeException('Sua sessão expirou. Atualize a página e tente novamente.');
    }
    if (!is_array($data)) {
        $responseStatus = 400;
        throw new RuntimeException('Dados da mesa inválidos.');
    }

    $idInput = $data['id'] ?? null;
    $id = is_int($idInput) || is_string($idInput) ? filter_var($idInput, FILTER_VALIDATE_INT) : false;
    if ($id === false || $id < 1) {
        $responseStatus = 400;
        throw new RuntimeException('ID da mesa inválido.');
    }

    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    $db->beginTransaction();
    $mesa = $db->prepare('SELECT id, numero FROM mesas WHERE id = ? FOR UPDATE');
    $mesa->execute([$id]);
    $mesaAtual = $mesa->fetch(PDO::FETCH_ASSOC);
    if (!$mesaAtual) {
        $responseStatus = 404;
        throw new RuntimeException('Mesa não encontrada.');
    }

    $check = $db->prepare(
        "SELECT COUNT(*) FROM comandas
         WHERE status = 'aberta'
           AND (mesa_id = ? OR (mesa_id IS NULL AND mesa_numero = ?))"
    );
    $check->execute([$id, $mesaAtual['numero']]);
    if ((int) $check->fetchColumn() > 0) {
        $responseStatus = 409;
        throw new RuntimeException('Não é possível excluir uma mesa com comanda aberta.');
    }

    $stmt = $db->prepare("DELETE FROM mesas WHERE id = ?");
    $stmt->execute([$id]);
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Mesa excluída.'], JSON_UNESCAPED_UNICODE);

} catch (RuntimeException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code($responseStatus);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro ao excluir mesa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível excluir a mesa.'], JSON_UNESCAPED_UNICODE);
}
