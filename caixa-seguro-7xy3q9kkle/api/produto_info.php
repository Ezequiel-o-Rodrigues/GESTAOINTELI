<?php
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID do produto não informado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT p.*, c.nome as categoria_nome 
              FROM produtos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produto) {
        echo json_encode($produto);
    } else {
        echo json_encode(['error' => 'Produto não encontrado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
}
?>