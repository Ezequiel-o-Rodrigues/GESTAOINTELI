<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir requisições OPTIONS para CORS
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

    // Ler dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    // Validações básicas
    if (empty($input['nome'])) {
        throw new Exception('Nome do produto é obrigatório');
    }
    if (empty($input['categoria_id'])) {
        throw new Exception('Categoria é obrigatória');
    }
    if (!isset($input['preco']) || $input['preco'] < 0) {
        throw new Exception('Preço inválido');
    }
    if (!isset($input['estoque_minimo']) || $input['estoque_minimo'] < 0) {
        throw new Exception('Estoque mínimo inválido');
    }

    $estoque_inicial = $input['estoque_inicial'] ?? 0;

    if (isset($input['id']) && !empty($input['id'])) {
        // ATUALIZAR produto existente
        $query = "UPDATE produtos SET 
                  nome = :nome, 
                  categoria_id = :categoria_id, 
                  preco = :preco, 
                  estoque_minimo = :estoque_minimo,
                  updated_at = NOW() 
                  WHERE id = :id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $input['nome']);
        $stmt->bindParam(':categoria_id', $input['categoria_id']);
        $stmt->bindParam(':preco', $input['preco']);
        $stmt->bindParam(':estoque_minimo', $input['estoque_minimo']);
        $stmt->bindParam(':id', $input['id']);

        $success = $stmt->execute();

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'id' => $input['id']
            ]);
        } else {
            throw new Exception('Erro ao atualizar produto');
        }

    } else {
        // NOVO produto
        $query = "INSERT INTO produtos 
                  (nome, categoria_id, preco, estoque_minimo, estoque_atual, ativo, created_at) 
                  VALUES 
                  (:nome, :categoria_id, :preco, :estoque_minimo, :estoque_atual, 1, NOW())";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $input['nome']);
        $stmt->bindParam(':categoria_id', $input['categoria_id']);
        $stmt->bindParam(':preco', $input['preco']);
        $stmt->bindParam(':estoque_minimo', $input['estoque_minimo']);
        $stmt->bindParam(':estoque_atual', $estoque_inicial);

        $success = $stmt->execute();

        if ($success) {
            $produto_id = $db->lastInsertId();
            
            // Se tem estoque inicial, registrar movimentação
            if ($estoque_inicial > 0) {
                $mov_query = "INSERT INTO movimentacoes_estoque 
                             (produto_id, tipo, quantidade, observacao, created_at) 
                             VALUES 
                             (:produto_id, 'entrada', :quantidade, 'Estoque inicial', NOW())";
                
                $mov_stmt = $db->prepare($mov_query);
                $mov_stmt->bindParam(':produto_id', $produto_id);
                $mov_stmt->bindParam(':quantidade', $estoque_inicial);
                $mov_stmt->execute();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Produto criado com sucesso',
                'id' => $produto_id
            ]);
        } else {
            throw new Exception('Erro ao criar produto');
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>