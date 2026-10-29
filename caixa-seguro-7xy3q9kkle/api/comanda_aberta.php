<?php
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php'); // ✅ CORRETO!

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM comandas WHERE status = 'aberta' ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'comanda' => $comanda ?: null
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro: ' . $e->getMessage()
    ]);
}
?>