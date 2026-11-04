<?php
require_once __DIR__ . '/../includes/api-auth.php';
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Erro na conexão com o banco de dados');
    }

    $data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
    $data_fim = $_GET['data_fim'] ?? date('Y-m-d');

    // Buscar taxa de comissão do banco ou usar padrão - NOVO CÓDIGO
$rate = 0.03; // padrão
try {
    $stmt = $db->prepare('SELECT valor FROM configuracoes_sistema WHERE chave = "commission_rate"');
    if ($stmt->execute()) {
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($config && isset($config['valor'])) {
            $rate = floatval($config['valor']);
        }
    }
} catch (Exception $e) {
    // Mantém o valor padrão se houver erro
    error_log("Erro ao buscar taxa de comissão em garcons.php: " . $e->getMessage());
}

    // Comandas podem estar vinculadas ao cadastro legado ou a uma conta de usuário.
    $stmt = $db->prepare(
        "SELECT COUNT(*) AS total
         FROM comandas
         WHERE status = 'fechada'
           AND DATE(data_venda) BETWEEN ? AND ?
           AND (garcom_id IS NOT NULL OR garcom_usuario_id IS NOT NULL)"
    );
    $stmt->execute([$data_inicio, $data_fim]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalComandasComGarcom = (int)($row['total'] ?? 0);

    // Mantém a tabela legada e também avalia as contas de usuário com perfil Garçom.
    $stmt = $db->prepare(
        "SELECT 'legado' AS origem, g.id, g.nome, g.codigo, g.ativo,
                COUNT(c.id) AS comandas, COALESCE(SUM(c.valor_total), 0) AS vendas_total
         FROM garcons g
         LEFT JOIN comandas c
           ON c.garcom_id = g.id
          AND c.garcom_usuario_id IS NULL
          AND c.status = 'fechada'
          AND DATE(c.data_venda) BETWEEN ? AND ?
         WHERE g.ativo = 1
         GROUP BY g.id, g.nome, g.codigo, g.ativo
         UNION ALL
         SELECT 'usuario' AS origem, u.id, u.nome, NULL AS codigo, u.ativo,
                COUNT(c.id) AS comandas, COALESCE(SUM(c.valor_total), 0) AS vendas_total
         FROM usuarios u
         LEFT JOIN comandas c
           ON c.garcom_usuario_id = u.id
          AND c.status = 'fechada'
          AND DATE(c.data_venda) BETWEEN ? AND ?
         WHERE u.perfil = 'garcom' AND u.ativo = 1
         GROUP BY u.id, u.nome, u.ativo
         ORDER BY nome"
    );
    $stmt->execute([$data_inicio, $data_fim, $data_inicio, $data_fim]);
    $garcons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $activeCount = count($garcons);

    $average = $activeCount > 0 ? ($totalComandasComGarcom / $activeCount) : 0;

    $resultGarcons = [];

    foreach ($garcons as $g) {
        $cnt = (int)$g['comandas'];
        $vendas_total = (float)$g['vendas_total'];

        // Percentual em relação à média
        if ($average > 0) {
            $ratio = $cnt / $average;
            $percent_of_average = round($ratio * 100, 1);
            $percent_diff = round((($cnt / $average) - 1) * 100);
        } else {
            $ratio = null;
            $percent_of_average = null;
            $percent_diff = 0;
        }

        // Classificação
        $classification = 'Sem Dados';
        $icon = 'secondary';

        if ($ratio === null) {
            $classification = 'Sem Dados';
            $icon = 'secondary';
        } else {
            if ($ratio >= 1.33) {
                $classification = 'Excelente';
                $icon = 'success';
            } elseif ($ratio >= 1.12) {
                $classification = 'Bom';
                $icon = 'info';
            } elseif ($ratio >= 0.90) {
                $classification = 'Regular';
                $icon = 'primary';
            } elseif ($ratio >= 0.70) {
                $classification = 'Baixo';
                $icon = 'warning';
            } else {
                $classification = 'Muito Baixo';
                $icon = 'danger';
            }
        }

        $comissao = round($vendas_total * $rate, 2);

        $resultGarcons[] = [
            'id' => (int)$g['id'],
            'origem' => $g['origem'],
            'nome' => $g['nome'],
            'codigo' => $g['codigo'] ?? null,
            'ativo' => (int)$g['ativo'],
            'comandas' => $cnt,
            'vendas_total' => round($vendas_total, 2),
            'comissao' => $comissao,
            'percent_of_average' => $percent_of_average,
            'percent_diff' => $percent_diff,
            'classification' => $classification,
            'badge' => $icon,
        ];
    }

    $output = [
        'success' => true,
        'data_inicio' => $data_inicio,
        'data_fim' => $data_fim,
        'total_comandas' => $totalComandasComGarcom,
        'active_garcons' => $activeCount,
        'average' => round($average, 2),
        'commission_rate' => $rate,
        'commission_rate_percent' => round($rate * 100, 1),
        'garcons' => $resultGarcons,
    ];

    echo json_encode($output, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>