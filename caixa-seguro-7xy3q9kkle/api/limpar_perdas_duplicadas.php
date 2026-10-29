<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Encontrar perdas duplicadas (mesmo produto_id, mesma data)
    $query_duplicadas = "
        SELECT produto_id, DATE(data_identificacao) as data_perda, COUNT(*) as total
        FROM perdas_estoque 
        GROUP BY produto_id, DATE(data_identificacao)
        HAVING COUNT(*) > 1
    ";
    
    $stmt = $db->prepare($query_duplicadas);
    $stmt->execute();
    $duplicadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $removidas = 0;
    
    foreach ($duplicadas as $dup) {
        // Para cada grupo de duplicadas, manter apenas a mais recente
        $query_manter = "
            SELECT id FROM perdas_estoque 
            WHERE produto_id = ? AND DATE(data_identificacao) = ?
            ORDER BY data_identificacao DESC, id DESC
            LIMIT 1
        ";
        
        $stmt_manter = $db->prepare($query_manter);
        $stmt_manter->execute([$dup['produto_id'], $dup['data_perda']]);
        $manter = $stmt_manter->fetch(PDO::FETCH_ASSOC);
        
        if ($manter) {
            // Remover todas as outras do mesmo produto/data
            $query_remover = "
                DELETE FROM perdas_estoque 
                WHERE produto_id = ? AND DATE(data_identificacao) = ? AND id != ?
            ";
            
            $stmt_remover = $db->prepare($query_remover);
            $stmt_remover->execute([$dup['produto_id'], $dup['data_perda'], $manter['id']]);
            
            $removidas += $stmt_remover->rowCount();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Limpeza concluída. $removidas registros duplicados removidos.",
        'duplicadas_encontradas' => count($duplicadas),
        'registros_removidos' => $removidas
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro na limpeza: ' . $e->getMessage()
    ]);
}
?>