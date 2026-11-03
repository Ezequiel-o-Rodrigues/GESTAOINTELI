<?php
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::config('database.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'É necessário entrar no sistema.']);
    exit;
}

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
        $stmt = $db->prepare(
            'SELECT id, nome_exibicao, descricao, imagem, ordem
             FROM produto_cards
             WHERE produto_id = :produto_id
             ORDER BY ordem, id'
        );
        $stmt->execute([':produto_id' => $produto['id']]);
        $produto['opcoes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($produto);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Produto não encontrado']);
    }
    
} catch (Exception $e) {
    error_log('Erro ao carregar produto: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Não foi possível carregar o produto.']);
}
?>