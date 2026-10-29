<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

// Ler dados do POST
$input = json_decode(file_get_contents('php://input'), true);
$data_inicio = $input['data_inicio'] ?? null;
$data_fim = $input['data_fim'] ?? null;
$tipo = $input['tipo'] ?? 'vendas';

try {
    switch($tipo) {
        case 'vendas':
            $query = "SELECT 
                        DATE(data_venda) as data,
                        COUNT(id) as total_comandas,
                        COALESCE(SUM(valor_total), 0) as valor_total,
                        COALESCE(SUM(taxa_gorjeta), 0) as total_gorjetas,
                        COALESCE(AVG(valor_total), 0) as ticket_medio
                      FROM comandas 
                      WHERE status = 'fechada'";
            
            if ($data_inicio && $data_fim) {
                $query .= " AND DATE(data_venda) BETWEEN :data_inicio AND :data_fim";
            }
            
            $query .= " GROUP BY DATE(data_venda) ORDER BY data";
            break;
            
        case 'produtos':
            $query = "SELECT 
                        p.nome,
                        cat.nome as categoria,
                        SUM(ic.quantidade) as total_vendido,
                        SUM(ic.subtotal) as valor_total_vendido,
                        COUNT(DISTINCT ic.comanda_id) as total_comandas
                      FROM itens_comanda ic
                      JOIN produtos p ON ic.produto_id = p.id
                      JOIN categorias cat ON p.categoria_id = cat.id
                      JOIN comandas c ON ic.comanda_id = c.id
                      WHERE c.status = 'fechada'";
            
            if ($data_inicio && $data_fim) {
                $query .= " AND DATE(c.data_venda) BETWEEN :data_inicio AND :data_fim";
            }
            
            $query .= " GROUP BY p.id ORDER BY total_vendido DESC LIMIT 50";
            break;
            
        case 'estoque':
            $query = "SELECT 
                        me.data_movimentacao,
                        p.nome as nome_produto,
                        me.tipo,
                        me.quantidade,
                        me.observacao,
                        f.nome as fornecedor
                      FROM movimentacoes_estoque me
                      JOIN produtos p ON me.produto_id = p.id
                      LEFT JOIN fornecedores f ON me.fornecedor_id = f.id
                      WHERE 1=1";
            
            if ($data_inicio && $data_fim) {
                $query .= " AND DATE(me.data_movimentacao) BETWEEN :data_inicio AND :data_fim";
            }
            
            $query .= " ORDER BY me.data_movimentacao DESC LIMIT 100";
            break;
            
        default:
            throw new Exception('Tipo de relatório inválido');
    }
    
    $stmt = $db->prepare($query);
    
    if ($data_inicio && $data_fim) {
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
    }
    
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'total' => count($resultados)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
    ]);
}
?>