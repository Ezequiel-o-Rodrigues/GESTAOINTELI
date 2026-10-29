<?php
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['categoria_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM produtos WHERE categoria_id = ? AND ativo = 1 ORDER BY nome";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['categoria_id']]);
    
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($produtos);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>