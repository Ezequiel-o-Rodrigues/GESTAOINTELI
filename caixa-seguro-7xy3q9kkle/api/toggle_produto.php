<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['produto_id']) || !isset($input['ativo'])) {
        throw new Exception('Dados incompletos');
    }

    $query = "UPDATE produtos SET ativo = :ativo, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':ativo', $input['ativo']);
    $stmt->bindParam(':id', $input['produto_id']);

    $success = $stmt->execute();

    if ($success && $stmt->rowCount() > 0) {
        $status = $input['ativo'] ? 'ativado' : 'desativado';
        echo json_encode([
            'success' => true,
            'message' => "Produto {$status} com sucesso"
        ]);
    } else {
        throw new Exception('Produto não encontrado ou não alterado');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>