<?php
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php');

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Registrar movimentação
    $query = "INSERT INTO movimentacoes_estoque 
              (produto_id, tipo, quantidade, observacao, fornecedor_id) 
              VALUES (:produto_id, 'entrada', :quantidade, :observacao, :fornecedor_id)";
    $stmt = $db->prepare($query);
    $stmt->execute($data);
    
    // Atualizar estoque
    $query_update = "UPDATE produtos SET estoque_atual = estoque_atual + :quantidade WHERE id = :produto_id";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([
        'quantidade' => $data['quantidade'],
        'produto_id' => $data['produto_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Entrada registrada com sucesso']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>