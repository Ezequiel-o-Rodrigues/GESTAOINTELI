<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;
$categoria_id = $_GET['categoria_id'] ?? null;

try {
    $query = "SELECT 
        p.id as produto_id,
        p.nome as produto_nome,
        cat.nome as categoria_nome,
        SUM(ic.quantidade) as total_vendido,
        SUM(ic.subtotal) as valor_total,
        p.preco as preco_unitario
        FROM itens_comanda ic
        JOIN produtos p ON ic.produto_id = p.id
        JOIN categorias cat ON p.categoria_id = cat.id
        JOIN comandas c ON ic.comanda_id = c.id
        WHERE c.status = 'fechada'";
    
    $params = [];
    
    if ($data_inicio && $data_fim) {
        $query .= " AND DATE(c.data_venda) BETWEEN :data_inicio AND :data_fim";
        $params[':data_inicio'] = $data_inicio;
        $params[':data_fim'] = $data_fim;
    }
    
    if ($categoria_id) {
        $query .= " AND p.categoria_id = :categoria_id";
        $params[':categoria_id'] = $categoria_id;
    }
    
    $query .= " GROUP BY p.id, p.nome, cat.nome, p.preco ORDER BY total_vendido DESC LIMIT 50";
    
    $stmt = $db->prepare($query);
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $resultados
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
    ]);
}
?>