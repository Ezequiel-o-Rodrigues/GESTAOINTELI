<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID do produto é obrigatório');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id, nome, preco, estoque_atual, categoria_id FROM produtos WHERE id = ? AND ativo = 1");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produto) {
        echo json_encode([
            'success' => true,
            'id' => $produto['id'],
            'nome' => $produto['nome'],
            'preco' => $produto['preco'],
            'estoque_atual' => $produto['estoque_atual'],
            'categoria_id' => $produto['categoria_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Produto não encontrado',
            'id' => $id,
            'nome' => 'Produto não encontrado'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'id' => $_GET['id'] ?? null,
        'nome' => 'Erro ao carregar produto'
    ]);
}
?>