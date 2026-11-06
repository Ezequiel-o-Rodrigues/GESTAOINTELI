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
    $stmtMesa = $db->prepare(
        'UPDATE mesas SET posicao_x = ?, posicao_y = ?, formato = ?, largura = ?, altura = ? WHERE id = ?'
    );

    $db->beginTransaction();

    // Salvar posições das mesas
    foreach ($data['mesas'] as $m) {
        $id      = filter_var($m['id'] ?? null, FILTER_VALIDATE_INT);
        $x       = filter_var($m['posicao_x'] ?? 0, FILTER_VALIDATE_INT);
        $y       = filter_var($m['posicao_y'] ?? 0, FILTER_VALIDATE_INT);
        $formato = in_array($m['formato'] ?? '', $formatos, true) ? $m['formato'] : 'quadrada';
        $largura = max(40, min(200, (int) ($m['largura'] ?? 60)));
        $altura  = max(40, min(200, (int) ($m['altura']  ?? 60)));
        if (!$id || $x === false || $y === false || $x < 0 || $y < 0) continue;
        $stmtMesa->execute([$x, $y, $formato, $largura, $altura, $id]);
    }

    // Salvar decorações (linhas e textos) por ambiente
    if (isset($data['decoracoes']) && is_array($data['decoracoes'])) {
        // Apaga as decorações dos ambientes enviados e reinsere
        $ambientesEnviados = array_unique(array_map(
            fn($d) => substr(trim((string)($d['ambiente'] ?? 'Salão Principal')), 0, 50),
            $data['decoracoes']
        ));
        if ($ambientesEnviados) {
            $placeholders = implode(',', array_fill(0, count($ambientesEnviados), '?'));
            $db->prepare("DELETE FROM layout_decoracoes WHERE ambiente IN ($placeholders)")
               ->execute($ambientesEnviados);
        }

        $stmtDec = $db->prepare(
            'INSERT INTO layout_decoracoes (ambiente, tipo, x1, y1, x2, y2, texto, cor)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $tiposValidos = ['linha', 'texto'];
        foreach ($data['decoracoes'] as $d) {
            $tipo = in_array($d['tipo'] ?? '', $tiposValidos, true) ? $d['tipo'] : null;
            if (!$tipo) continue;
            $amb  = substr(trim((string)($d['ambiente'] ?? 'Salão Principal')), 0, 50);
            $x1   = max(0, (int)($d['x1'] ?? 0));
            $y1   = max(0, (int)($d['y1'] ?? 0));
            $x2   = max(0, (int)($d['x2'] ?? 0));
            $y2   = max(0, (int)($d['y2'] ?? 0));
            $txt  = $tipo === 'texto' ? substr(trim((string)($d['texto'] ?? '')), 0, 120) : null;
            $cor  = preg_match('/^#[0-9a-fA-F]{3,6}$/', $d['cor'] ?? '') ? $d['cor'] : '#94a3b8';
            $stmtDec->execute([$amb, $tipo, $x1, $y1, $x2, $y2, $txt, $cor]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Layout salvo.'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
    error_log('Erro ao salvar layout: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível salvar o layout.'], JSON_UNESCAPED_UNICODE);
}
