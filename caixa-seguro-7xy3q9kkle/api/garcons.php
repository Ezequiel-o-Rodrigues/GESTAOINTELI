<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
    $data_fim = $_GET['data_fim'] ?? date('Y-m-d');
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!is_string($data_inicio) || !is_string($data_fim)
        || !preg_match($datePattern, $data_inicio) || !preg_match($datePattern, $data_fim)
        || $data_inicio > $data_fim) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Informe um período válido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare(
        'SELECT ic.garcom_usuario_id AS id,
                COALESCE(MAX(ic.garcom_nome), MAX(u.nome), CONCAT(\'Garçom #\', ic.garcom_usuario_id)) AS nome,
                COUNT(DISTINCT c.id) AS comandas,
                COALESCE(SUM(ic.subtotal), 0) AS vendas_total,
                COALESCE(SUM(ic.comissao_valor), 0) AS comissao
         FROM itens_comanda ic
         JOIN comandas c ON c.id = ic.comanda_id
         LEFT JOIN usuarios u ON u.id = ic.garcom_usuario_id
         WHERE ic.garcom_usuario_id IS NOT NULL
           AND ic.cancelado_em IS NULL
           AND c.status = \'fechada\'
           AND DATE(c.data_venda) BETWEEN ? AND ?
         GROUP BY ic.garcom_usuario_id
         ORDER BY nome'
    );
    $stmt->execute([$data_inicio, $data_fim]);
    $garcons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalComandas = 0;
    foreach ($garcons as $garcom) {
        $totalComandas += (int) $garcom['comandas'];
    }
    $activeCount = count($garcons);
    $average = $activeCount > 0 ? ($totalComandas / $activeCount) : 0;
    $resultGarcons = [];

    foreach ($garcons as $garcom) {
        $comandas = (int) $garcom['comandas'];
        $ratio = $average > 0 ? $comandas / $average : null;
        $percentOfAverage = $ratio === null ? null : round($ratio * 100, 1);
        $percentDiff = $ratio === null ? 0 : (int) round(($ratio - 1) * 100);

        if ($ratio === null) {
            $classification = 'Sem Dados';
            $badge = 'secondary';
        } elseif ($ratio >= 1.33) {
            $classification = 'Excelente';
            $badge = 'success';
        } elseif ($ratio >= 1.12) {
            $classification = 'Bom';
            $badge = 'info';
        } elseif ($ratio >= 0.90) {
            $classification = 'Regular';
            $badge = 'primary';
        } elseif ($ratio >= 0.70) {
            $classification = 'Baixo';
            $badge = 'warning';
        } else {
            $classification = 'Muito Baixo';
            $badge = 'danger';
        }

        $resultGarcons[] = [
            'id' => (int) $garcom['id'],
            'origem' => 'usuario',
            'nome' => $garcom['nome'],
            'codigo' => null,
            'ativo' => 1,
            'comandas' => $comandas,
            'vendas_total' => round((float) $garcom['vendas_total'], 2),
            'comissao' => round((float) $garcom['comissao'], 2),
            'percent_of_average' => $percentOfAverage,
            'percent_diff' => $percentDiff,
            'classification' => $classification,
            'badge' => $badge
        ];
    }

    $stmt = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'commission_rate'");
    $stmt->execute();
    $rate = $stmt->fetchColumn();
    $rate = $rate !== false && is_numeric($rate) ? (float) $rate : 0;

    echo json_encode([
        'success' => true,
        'data_inicio' => $data_inicio,
        'data_fim' => $data_fim,
        'total_comandas' => $totalComandas,
        'active_garcons' => $activeCount,
        'average' => round($average, 2),
        'commission_rate' => $rate,
        'commission_rate_percent' => round($rate * 100, 1),
        'garcons' => $resultGarcons
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Erro ao carregar relatório de comissão por item: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Não foi possível gerar o relatório de garçons.'
    ], JSON_UNESCAPED_UNICODE);
}
?>
