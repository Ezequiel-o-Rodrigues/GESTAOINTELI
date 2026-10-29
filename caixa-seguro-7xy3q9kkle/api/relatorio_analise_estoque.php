<?php
// api/relatorio_analise_estoque.php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
    $data_fim = $_GET['data_fim'] ?? date('Y-m-d');

    // Chamar a stored procedure existente
    $stmt = $db->prepare("CALL relatorio_analise_estoque_periodo(:data_inicio, :data_fim)");
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->execute();
    
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totais
    $totais = [
        'total_produtos_com_perda' => 0,
        'total_perdas_quantidade' => 0,
        'total_perdas_valor' => 0,
        'total_faturamento' => 0
    ];

    foreach ($dados as $item) {
        if ($item['perdas_quantidade'] > 0) {
            $totais['total_produtos_com_perda']++;
            $totais['total_perdas_quantidade'] += $item['perdas_quantidade'];
            $totais['total_perdas_valor'] += $item['perdas_valor'];
        }
        $totais['total_faturamento'] += $item['faturamento_periodo'];
    }

    echo json_encode([
        'success' => true,
        'data' => $dados,
        'totais' => $totais,
        'periodo' => [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
    ]);
}
?>