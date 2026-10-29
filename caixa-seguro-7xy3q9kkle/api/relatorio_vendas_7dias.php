<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // Calcular data de 7 dias atrás
    $dataInicio = date('Y-m-d', strtotime('-7 days'));
    $dataFim = date('Y-m-d');
    
    $query = "SELECT 
                DATE(data_venda) as data,
                COUNT(id) as total_comandas,
                COALESCE(SUM(valor_total), 0) as valor_total,
                COALESCE(SUM(taxa_gorjeta), 0) as total_gorjetas,
                COALESCE(AVG(valor_total), 0) as ticket_medio
              FROM comandas 
              WHERE status = 'fechada' 
                AND DATE(data_venda) BETWEEN :data_inicio AND :data_fim
              GROUP BY DATE(data_venda)
              ORDER BY data";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':data_inicio', $dataInicio);
    $stmt->bindParam(':data_fim', $dataFim);
    $stmt->execute();
    
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Garantir que todos os últimos 7 dias estejam representados
    $dadosCompletos = [];
    for ($i = 6; $i >= 0; $i--) {
        $data = date('Y-m-d', strtotime("-$i days"));
        $encontrado = false;
        
        foreach ($vendas as $venda) {
            if ($venda['data'] == $data) {
                $dadosCompletos[] = $venda;
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            $dadosCompletos[] = [
                'data' => $data,
                'total_comandas' => 0,
                'valor_total' => 0,
                'total_gorjetas' => 0,
                'ticket_medio' => 0
            ];
        }
    }
    
    // Preparar dados para o gráfico
    $labels = [];
    $valores = [];
    
    foreach ($dadosCompletos as $venda) {
        $dataObj = DateTime::createFromFormat('Y-m-d', $venda['data']);
        $labels[] = $dataObj->format('d/m');
        $valores[] = (float)$venda['valor_total'];
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'valores' => $valores,
        'dados' => $dadosCompletos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar vendas: ' . $e->getMessage(),
        'labels' => [],
        'valores' => []
    ]);
}
?>