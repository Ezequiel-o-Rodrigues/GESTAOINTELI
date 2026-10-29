<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // Query para detectar perdas reais
    $query = "SELECT 
                p.id as produto_id,
                p.nome,
                cat.nome as categoria,
                p.estoque_atual,
                p.preco,
                (SELECT COALESCE(SUM(quantidade), 0) 
                 FROM movimentacoes_estoque me 
                 WHERE me.produto_id = p.id AND me.tipo = 'entrada') as total_entradas,
                (SELECT COALESCE(SUM(ic.quantidade), 0) 
                 FROM itens_comanda ic 
                 JOIN comandas c ON ic.comanda_id = c.id 
                 WHERE ic.produto_id = p.id AND c.status = 'fechada') as total_vendido,
                ((SELECT COALESCE(SUM(quantidade), 0) FROM movimentacoes_estoque WHERE produto_id = p.id AND tipo = 'entrada') - 
                 (SELECT COALESCE(SUM(ic.quantidade), 0) FROM itens_comanda ic JOIN comandas c ON ic.comanda_id = c.id WHERE ic.produto_id = p.id AND c.status = 'fechada') - 
                 p.estoque_atual) as diferenca_estoque
              FROM produtos p
              JOIN categorias cat ON p.categoria_id = cat.id
              WHERE p.ativo = 1
              HAVING diferenca_estoque > 0";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $perdas_detectadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $novas_perdas = 0;
    
    foreach ($perdas_detectadas as $perda) {
        $estoque_esperado = $perda['total_entradas'] - $perda['total_vendido'];
        $estoque_real = $perda['estoque_atual'];
        $valor_perda = $perda['diferenca_estoque'] * $perda['preco'];
        
        // Verificar se já existe perda idêntica não visualizada
        $stmt_check = $db->prepare("
            SELECT id FROM perdas_estoque 
            WHERE produto_id = ? 
            AND quantidade_perdida = ? 
            AND estoque_esperado = ? 
            AND estoque_real = ? 
            AND visualizada = 0
        ");
        $stmt_check->execute([
            $perda['produto_id'], 
            $perda['diferenca_estoque'],
            $estoque_esperado,
            $estoque_real
        ]);
        
        if (!$stmt_check->fetch()) {
            // Não existe - criar nova perda
            $stmt_insert = $db->prepare("
                INSERT INTO perdas_estoque (produto_id, quantidade_perdida, valor_perda, estoque_esperado, estoque_real, motivo) 
                VALUES (?, ?, ?, ?, ?, 'Diferença de inventário detectada automaticamente')
            ");
            $stmt_insert->execute([
                $perda['produto_id'], 
                $perda['diferenca_estoque'], 
                $valor_perda,
                $estoque_esperado,
                $estoque_real
            ]);
            $novas_perdas++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'novas_perdas' => $novas_perdas,
        'message' => "Detecção concluída. $novas_perdas novas perdas registradas."
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro na detecção: ' . $e->getMessage()
    ]);
}
?>