<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$tipo_periodo = $_GET['tipo'] ?? 'diario';

try {
    switch($tipo_periodo) {
        case 'diario':
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
            break;
            
        case 'semanal':
            $query = "SELECT 
                         CONCAT('Semana ', WEEK(data_venda), ' - ', YEAR(data_venda)) as periodo,
                         COUNT(id) as total_comandas,
                         COALESCE(SUM(valor_total), 0) as valor_total,
                         COALESCE(SUM(taxa_gorjeta), 0) as total_gorjetas,
                         COALESCE(AVG(valor_total), 0) as ticket_medio
                      FROM comandas 
                      WHERE status = 'fechada'
                        AND DATE(data_venda) BETWEEN :data_inicio AND :data_fim
                      GROUP BY YEAR(data_venda), WEEK(data_venda)
                      ORDER BY YEAR(data_venda) DESC, WEEK(data_venda) DESC";
            break;
            
        case 'mensal':
            $query = "SELECT 
                         CONCAT(YEAR(data_venda), '-', LPAD(MONTH(data_venda), 2, '0')) as periodo,
                         COUNT(id) as total_comandas,
                         COALESCE(SUM(valor_total), 0) as valor_total,
                         COALESCE(SUM(taxa_gorjeta), 0) as total_gorjetas,
                         COALESCE(AVG(valor_total), 0) as ticket_medio
                      FROM comandas 
                      WHERE status = 'fechada'
                        AND DATE(data_venda) BETWEEN :data_inicio AND :data_fim
                      GROUP BY YEAR(data_venda), MONTH(data_venda)
                      ORDER BY YEAR(data_venda) DESC, MONTH(data_venda) DESC";
            break;
            
        default:
            throw new Exception('Tipo de período inválido');
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->execute();
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Garantir que sempre retornamos um array, mesmo vazio
    if (!$resultados) {
        $resultados = [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'periodo' => $tipo_periodo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar relatório: ' . $e->getMessage(),
        'data' => [], // Sempre retornar array vazio em caso de erro
        'periodo' => $tipo_periodo
    ]);
}
?>