<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // Criar tabela de perdas se não existir
    $db->exec("
        CREATE TABLE IF NOT EXISTS perdas_estoque (
            id INT AUTO_INCREMENT PRIMARY KEY,
            produto_id INT NOT NULL,
            quantidade_perdida INT NOT NULL,
            valor_perda DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            estoque_esperado INT NOT NULL DEFAULT 0,
            estoque_real INT NOT NULL DEFAULT 0,
            motivo VARCHAR(255) DEFAULT 'Diferença de inventário',
            data_identificacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            visualizada TINYINT(1) DEFAULT 0,
            data_visualizacao DATETIME NULL,
            observacoes TEXT NULL,
            FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
        )
    ");
    
    // Adicionar colunas se não existirem
    $db->exec("ALTER TABLE perdas_estoque ADD COLUMN IF NOT EXISTS estoque_esperado INT NOT NULL DEFAULT 0");
    $db->exec("ALTER TABLE perdas_estoque ADD COLUMN IF NOT EXISTS estoque_real INT NOT NULL DEFAULT 0");
    
    // Query para detectar perdas não visualizadas
    $query = "SELECT 
                p.id as produto_id,
                p.nome,
                cat.nome as categoria,
                p.estoque_atual,
                p.estoque_minimo,
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
              HAVING diferenca_estoque > 0
              ORDER BY diferenca_estoque DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $perdas_detectadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar apenas perdas NÃO VISUALIZADAS já existentes
    $query_perdas_existentes = "
        SELECT pe.*, p.nome, cat.nome as categoria, p.preco,
               pe.quantidade_perdida as diferenca_estoque,
               pe.valor_perda
        FROM perdas_estoque pe
        JOIN produtos p ON pe.produto_id = p.id
        JOIN categorias cat ON p.categoria_id = cat.id
        WHERE pe.visualizada = 0
        ORDER BY pe.data_identificacao DESC
    ";
    
    $stmt_existentes = $db->prepare($query_perdas_existentes);
    $stmt_existentes->execute();
    $alertas_com_id = $stmt_existentes->fetchAll(PDO::FETCH_ASSOC);
    
    // Renomear campos para compatibilidade
    foreach ($alertas_com_id as &$alerta) {
        $alerta['produto_id'] = $alerta['produto_id'];
        $alerta['total_entradas'] = 0; // Não usado nos alertas
        $alerta['total_vendido'] = 0;  // Não usado nos alertas
        $alerta['estoque_atual'] = 0;  // Não usado nos alertas
    }
    
    echo json_encode([
        'success' => true,
        'data' => $alertas_com_id,
        'total_alertas' => count($alertas_com_id),
        'debug' => 'Mostrando apenas perdas não visualizadas existentes'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar alertas: ' . $e->getMessage(),
        'data' => [],
        'total_alertas' => 0
    ]);
}
?>