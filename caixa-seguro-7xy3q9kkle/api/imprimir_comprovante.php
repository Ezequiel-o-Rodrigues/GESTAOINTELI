<?php
// api/imprimir_comprovante.php
// VERSÃO SIMPLIFICADA - SEM GORJETA - CACHE BUSTING

// HEADERS para evitar cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

// DEBUG - Verificar se nova versão está rodando
error_log("🔄 NOVA VERSÃO SIMPLIFICADA EXECUTADA - " . date('Y-m-d H:i:s'));

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $comprovante_id = $input['comprovante_id'] ?? null;
    
    if (!$comprovante_id) {
        echo json_encode(['success' => false, 'message' => 'Comprovante ID não informado']);
        exit;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Buscar comprovante
        $query = "SELECT * FROM comprovantes_venda WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $comprovante_id);
        $stmt->execute();
        $comprovante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comprovante) {
            throw new Exception('Comprovante não encontrado');
        }
        
        // Marcar como impresso
        $query_update = "UPDATE comprovantes_venda SET impresso = 1 WHERE id = :id";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bindParam(':id', $comprovante_id);
        $stmt_update->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Comprovante marcado para impressão',
            'conteudo' => $comprovante['conteudo']
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao processar impressão: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao processar impressão: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>