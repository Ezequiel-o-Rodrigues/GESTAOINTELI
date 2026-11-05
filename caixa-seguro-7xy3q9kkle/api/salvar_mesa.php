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

    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    $idInput = $data['id'] ?? null;
    $id = ($idInput === null || $idInput === '')
        ? null
        : ((is_int($idInput) || is_string($idInput)) ? filter_var($idInput, FILTER_VALIDATE_INT) : false);
    $numero = isset($data['numero']) && is_string($data['numero']) ? trim($data['numero']) : '';
    $ambiente = isset($data['ambiente']) && is_string($data['ambiente'])
        ? trim($data['ambiente'])
        : 'Salão Principal';
    $posicaoXInput = $data['posicao_x'] ?? 0;
    $posicaoYInput = $data['posicao_y'] ?? 0;
    $posicaoX = is_int($posicaoXInput) || is_string($posicaoXInput)
        ? filter_var($posicaoXInput, FILTER_VALIDATE_INT)
        : false;
    $posicaoY = is_int($posicaoYInput) || is_string($posicaoYInput)
        ? filter_var($posicaoYInput, FILTER_VALIDATE_INT)
        : false;
    $ativoInput = $data['ativo'] ?? 1;
    $ativo = in_array($ativoInput, [0, 1, '0', '1', false, true], true)
        ? (int) (bool) $ativoInput
        : null;
    $numeroComprimento = preg_match_all('/./us', $numero, $numeroCaracteres);
    $ambienteComprimento = preg_match_all('/./us', $ambiente, $ambienteCaracteres);

    if (($idInput !== null && $idInput !== '' && ($id === false || $id < 1))
        || $posicaoX === false || $posicaoY === false || $posicaoX < 0 || $posicaoY < 0
        || $ativo === null) {
        $responseStatus = 400;
        throw new RuntimeException('Confira o ID, o status e as posições da mesa.');
    }
    if ($numero === '' || $numeroComprimento === false || $numeroComprimento > 20
        || $ambienteComprimento === false || $ambienteComprimento > 50) {
        $responseStatus = 400;
        throw new RuntimeException('Informe o número da mesa (máx. 20 caracteres) e um ambiente com até 50 caracteres.');
    }

    $db->beginTransaction();
    if ($id) {
        $check = $db->prepare('SELECT numero FROM mesas WHERE id = ? FOR UPDATE');
        $check->execute([$id]);
        $mesaAtual = $check->fetch(PDO::FETCH_ASSOC);
        if (!$mesaAtual) {
            $responseStatus = 404;
            throw new RuntimeException('Mesa não encontrada.');
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
        $check->execute([$id, $mesaAtual['numero']]);
        if ((int) $check->fetchColumn() > 0 && (!$ativo || $numero !== $mesaAtual['numero'])) {
            $responseStatus = 409;
            throw new RuntimeException('Não é possível desativar ou renumerar uma mesa com comanda aberta.');
        }

        $stmt = $db->prepare(
            "UPDATE mesas SET numero = ?, ambiente = ?, posicao_x = ?, posicao_y = ?, ativo = ? WHERE id = ?"
        );
        $stmt->execute([$numero, $ambiente, $posicaoX, $posicaoY, $ativo, $id]);
        $mesaId = (int) $id;
    } else {
        $stmt = $db->prepare(
            "INSERT INTO mesas (numero, ambiente, posicao_x, posicao_y, ativo) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$numero, $ambiente, $posicaoX, $posicaoY, $ativo]);
        $mesaId = (int) $db->lastInsertId();
    }

    $db->commit();
    echo json_encode(['success' => true, 'mesa_id' => $mesaId, 'message' => 'Mesa salva.'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Já existe uma mesa com esse número.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    error_log('Erro ao salvar mesa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível salvar a mesa.'], JSON_UNESCAPED_UNICODE);
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
    error_log('Erro ao salvar mesa: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível salvar a mesa.'], JSON_UNESCAPED_UNICODE);
}
