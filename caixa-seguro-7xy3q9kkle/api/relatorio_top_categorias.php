<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT 
                cat.nome as categoria,
                COUNT(DISTINCT ic.comanda_id) as total_comandas,
                SUM(ic.quantidade) as total_itens,
                SUM(ic.subtotal) as valor_total
              FROM itens_comanda ic
              JOIN produtos p ON ic.produto_id = p.id
              JOIN categorias cat ON p.categoria_id = cat.id
              JOIN comandas c ON ic.comanda_id = c.id
              WHERE c.status = 'fechada'
                AND c.data_venda >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY cat.id, cat.nome
              ORDER BY valor_total DESC
              LIMIT 8";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar dados para o gráfico
    $labels = [];
    $valores = [];
    
    foreach ($categorias as $categoria) {
        $labels[] = $categoria['categoria'];
        $valores[] = (float)$categoria['valor_total'];
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'valores' => $valores,
        'dados' => $categorias
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar categorias: ' . $e->getMessage(),
        'labels' => [],
        'valores' => []
    ]);
}
?>