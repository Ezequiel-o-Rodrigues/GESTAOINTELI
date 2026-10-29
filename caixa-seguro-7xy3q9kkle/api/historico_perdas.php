<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Parâmetros de filtro
    $data_inicio = $_GET['data_inicio'] ?? null;
    $data_fim = $_GET['data_fim'] ?? null;
    $mes_ano = $_GET['mes_ano'] ?? null;
    
    $where_conditions = [];
    $params = [];
    
    // Filtro por período específico
    if ($data_inicio && $data_fim) {
        $where_conditions[] = "DATE(pe.data_identificacao) BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
    }
    // Filtro por mês/ano (formato: YYYY-MM)
    elseif ($mes_ano) {
        $where_conditions[] = "DATE_FORMAT(pe.data_identificacao, '%Y-%m') = ?";
        $params[] = $mes_ano;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT 
            pe.id,
            pe.produto_id,
            p.nome as produto_nome,
            c.nome as categoria_nome,
            pe.quantidade_perdida,
            pe.valor_perda,
            pe.motivo,
            pe.data_identificacao,
            pe.visualizada,
            pe.data_visualizacao,
            pe.observacoes
        FROM perdas_estoque pe
        JOIN produtos p ON pe.produto_id = p.id
        JOIN categorias c ON p.categoria_id = c.id
        {$where_clause}
        ORDER BY pe.data_identificacao DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $perdas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $perdas,
        'total' => count($perdas),
        'filtros' => [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'mes_ano' => $mes_ano
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>