<?php
// api/imprimir_comprovante.php
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
        
        // Buscar dados da comanda para gerar comprovante limpo
        $query_comanda = "
            SELECT 
                c.id as comanda_id,
                c.valor_total,
                c.data_venda,
                g.nome as garcom_nome,
                g.codigo as garcom_codigo,
                GROUP_CONCAT(CONCAT(p.nome, '|', ic.quantidade, '|', ic.preco_unitario, '|', ic.subtotal) SEPARATOR ';') as itens
            FROM comandas c
            LEFT JOIN garcons g ON c.garcom_id = g.id
            LEFT JOIN itens_comanda ic ON c.id = ic.comanda_id
            LEFT JOIN produtos p ON ic.produto_id = p.id
            WHERE c.id = :comanda_id
            GROUP BY c.id
        ";
        
        $stmt_comanda = $db->prepare($query_comanda);
        $stmt_comanda->bindParam(':comanda_id', $comprovante['comanda_id']);
        $stmt_comanda->execute();
        $comanda = $stmt_comanda->fetch(PDO::FETCH_ASSOC);
        
        if (!$comanda) {
            throw new Exception('Comanda não encontrada');
        }
        
        // GERAR COMPROVANTE LIMPO E SIMPLES
        $conteudo_limpo = gerarComprovanteLimpo($comanda);
        
        // Marcar como impresso
        $query_update = "UPDATE comprovantes_venda SET impresso = 1 WHERE id = :id";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bindParam(':id', $comprovante_id);
        $stmt_update->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Comprovante marcado para impressão',
            'conteudo' => $conteudo_limpo
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao processar impressão: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao processar impressão: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}

// FUNÇÃO PARA GERAR COMPROVANTE LIMPO
function gerarComprovanteLimpo($comanda) {
    $itens = explode(';', $comanda['itens']);
    $linhas = [];
    
    // Cabeçalho SIMPLES
    $linhas[] = "ESPETINHO DO JUNIOR";
    $linhas[] = "-------------------------------";
    $linhas[] = "Comanda: #" . $comanda['comanda_id'];
    $linhas[] = "Data: " . date('d/m/Y H:i', strtotime($comanda['data_venda']));
    
    if ($comanda['garcom_nome']) {
        $linhas[] = "Garcom: " . $comanda['garcom_nome'];
    }
    
    $linhas[] = "-------------------------------";
    
    // Itens SIMPLIFICADOS
    $total_itens = 0;
    
    foreach ($itens as $item) {
        if (empty($item)) continue;
        
        list($nome, $quantidade, $preco_unitario, $subtotal) = explode('|', $item);
        $total_itens++;
        
        // Formato simples: 2x Nome do Produto
        $nome_limpo = substr(trim($nome), 0, 22);
        $linhas[] = $quantidade . "x " . $nome_limpo;
        $linhas[] = "   R$ " . number_format($subtotal, 2, ',', '.');
        
        // Adicionar linha em branco a cada 2 itens para melhor legibilidade
        if ($total_itens % 2 == 0) {
            $linhas[] = "";
        }
    }
    
    // Total SIMPLES - SEM GORJETA
    $linhas[] = "-------------------------------";
    $linhas[] = "TOTAL: R$ " . number_format($comanda['valor_total'], 2, ',', '.');
    $linhas[] = "========================";
    $linhas[] = "";
    $linhas[] = "Obrigado pela preferencia!";
    $linhas[] = "Volte sempre!";
    $linhas[] = "";
    $linhas[] = date('d/m/Y H:i:s');
    
    return implode("\n", $linhas);
}
?>