<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $perda_id = $input['perda_id'] ?? null;
    
    if (!$perda_id) {
        throw new Exception('ID da perda é obrigatório');
    }
    
    $stmt = $db->prepare("UPDATE perdas_estoque SET visualizada = 1, data_visualizacao = NOW() WHERE id = ?");
    $stmt->execute([$perda_id]);
    
    echo json_encode(['success' => true, 'message' => 'Perda marcada como visualizada']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>