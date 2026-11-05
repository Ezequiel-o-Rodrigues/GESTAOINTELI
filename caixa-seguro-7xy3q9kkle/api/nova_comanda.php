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

$requestBody = file_get_contents('php://input');
$data = $requestBody === '' ? [] : json_decode($requestBody, true);

$responseStatus = 500;
try {
    if (!is_array($data)) {
        $responseStatus = 400;
        throw new RuntimeException('Dados da comanda inválidos.');
    }

    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    $stmt = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'usar_mapa_mesas'");
    $stmt->execute();
    $usarMapaMesas = $stmt->fetchColumn() === '1';

    $mesa_id = null;
    if (array_key_exists('mesa_id', $data) && $data['mesa_id'] !== null && $data['mesa_id'] !== '') {
        $mesaInput = $data['mesa_id'];
        $mesa_id = is_int($mesaInput) || is_string($mesaInput)
            ? filter_var($mesaInput, FILTER_VALIDATE_INT)
            : false;
        if ($mesa_id === false || $mesa_id < 1) {
            $responseStatus = 400;
            throw new RuntimeException('Mesa inválida.');
        }
    }

    if ($usarMapaMesas && $mesa_id === null) {
        $responseStatus = 400;
        throw new RuntimeException('Selecione uma mesa livre para abrir a comanda.');
    }

    $db->beginTransaction();
    $mesa_numero = null;
    if ($mesa_id !== null) {
        $check = $db->prepare('SELECT numero FROM mesas WHERE id = ? AND ativo = 1 FOR UPDATE');
        $check->execute([$mesa_id]);
        $mesa = $check->fetch(PDO::FETCH_ASSOC);
        if (!$mesa) {
            $responseStatus = 400;
            throw new RuntimeException('Mesa não encontrada ou inativa.');
        }

        $check = $db->prepare(
            "SELECT COUNT(*) FROM comandas
             WHERE status = 'aberta'
               AND (mesa_id = ? OR (
                    mesa_id IS NULL
                    AND CONVERT(mesa_numero USING utf8mb4) COLLATE utf8mb4_unicode_ci
                        = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci
               ))"
        );
        $check->execute([$mesa_id, $mesa['numero']]);
        if ((int) $check->fetchColumn() > 0) {
            $responseStatus = 409;
            throw new RuntimeException('Esta mesa já possui uma comanda aberta. Atualize o mapa e selecione a comanda existente.');
        }
        $mesa_numero = $mesa['numero'];
    }

    $stmt = $db->prepare(
        "INSERT INTO comandas (garcom_usuario_id, mesa_id, mesa_numero, status, valor_total, taxa_gorjeta, created_at, updated_at)
         VALUES (?, ?, ?, 'aberta', 0, 0, NOW(), NOW())"
    );
    $garcomUsuarioId = currentUserRole() === 'garcom' ? (int) $_SESSION['usuario_id'] : null;
    $stmt->execute([$garcomUsuarioId, $mesa_id, $mesa_numero]);
    $comandaId = (int) $db->lastInsertId();
    $db->commit();

    echo json_encode([
        'success'    => true,
        'comanda_id' => $comandaId,
        'message'    => 'Comanda criada com sucesso'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    if ($responseStatus === 500) {
        error_log('Erro ao criar comanda: ' . $e->getMessage());
    }
    http_response_code($responseStatus);
    echo json_encode([
        'success' => false,
        'message' => $responseStatus === 500 ? 'Não foi possível criar a comanda.' : $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>