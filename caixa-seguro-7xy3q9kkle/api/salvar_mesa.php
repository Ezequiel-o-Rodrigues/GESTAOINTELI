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
    $database = new Database();
    $db = $database->getConnection();

    $id      = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $numero  = trim((string) ($data['numero'] ?? ''));
    $ambiente = trim((string) ($data['ambiente'] ?? 'Salão Principal'));
    $posicao_x = (int) ($data['posicao_x'] ?? 0);
    $posicao_y = (int) ($data['posicao_y'] ?? 0);
    $ativo   = isset($data['ativo']) ? (int) (bool) $data['ativo'] : 1;

    if ($numero === '' || strlen($numero) > 20) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Número da mesa é obrigatório (máx. 20 caracteres).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($id) {
        // Ao desativar, verificar se há comanda aberta
        if (!$ativo) {
            $check = $db->prepare("SELECT COUNT(*) FROM comandas WHERE mesa_id = ? AND status = 'aberta'");
            $check->execute([$id]);
            if ((int) $check->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Não é possível desativar uma mesa com comanda aberta.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        $stmt = $db->prepare(
            "UPDATE mesas SET numero = ?, ambiente = ?, posicao_x = ?, posicao_y = ?, ativo = ? WHERE id = ?"
        );
        $stmt->execute([$numero, $ambiente, $posicao_x, $posicao_y, $ativo, $id]);
        echo json_encode(['success' => true, 'message' => 'Mesa atualizada.'], JSON_UNESCAPED_UNICODE);
    } else {
        $stmt = $db->prepare(
            "INSERT INTO mesas (numero, ambiente, posicao_x, posicao_y, ativo) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$numero, $ambiente, $posicao_x, $posicao_y, $ativo]);
        echo json_encode([
            'success'  => true,
            'mesa_id'  => (int) $db->lastInsertId(),
            'message'  => 'Mesa criada.'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    $msg = str_contains($e->getMessage(), 'uk_mesa_numero')
        ? 'Já existe uma mesa com esse número.'
        : $e->getMessage();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
}
