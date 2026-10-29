<?php
// api/imprimir_simples.php - NOVO ARQUIVO
// VERSÃO 100% NOVA - SEM CACHE

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $comprovante_id = $input['comprovante_id'] ?? null;
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Buscar dados da comanda
        $query = "SELECT c.id as comanda_id, c.valor_total, c.data_venda, 
                         g.nome as garcom_nome,
                         GROUP_CONCAT(CONCAT(p.nome, '|', ic.quantidade, '|', ic.subtotal) SEPARATOR ';') as itens
                  FROM comandas c
                  LEFT JOIN garcons g ON c.garcom_id = g.id
                  LEFT JOIN itens_comanda ic ON c.id = ic.comanda_id
                  LEFT JOIN produtos p ON ic.produto_id = p.id
                  WHERE c.id = (SELECT comanda_id FROM comprovantes_venda WHERE id = :id)
                  GROUP BY c.id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $comprovante_id);
        $stmt->execute();
        $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comanda) throw new Exception('Comanda não encontrada');
        
        // GERAR COMPROVANTE SIMPLES - SEM GORJETA
        $linhas = [];
        $linhas[] = "ESPETINHO DO JUNIOR";
        $linhas[] = "-------------------";
        $linhas[] = "Comanda: #" . $comanda['comanda_id'];
        $linhas[] = "Data: " . date('d/m/Y H:i');
        
        if ($comanda['garcom_nome']) {
            $linhas[] = "Garcom: " . $comanda['garcom_nome'];
        }
        
        $linhas[] = "-------------------";
        
        // Itens
        $itens = explode(';', $comanda['itens']);
        foreach ($itens as $item) {
            if (empty($item)) continue;
            list($nome, $quantidade, $subtotal) = explode('|', $item);
            $linhas[] = $quantidade . "x " . substr($nome, 0, 18);
            $linhas[] = "   R$ " . number_format($subtotal, 2, ',', '.');
        }
        
        $linhas[] = "-------------------";
        $linhas[] = "TOTAL: R$ " . number_format($comanda['valor_total'], 2, ',', '.');
        $linhas[] = "================";
        $linhas[] = "Obrigado!";
        
        $conteudo = implode("\n", $linhas);
        
        // Marcar como impresso
        $update = "UPDATE comprovantes_venda SET impresso = 1 WHERE id = :id";
        $stmt_update = $db->prepare($update);
        $stmt_update->bindParam(':id', $comprovante_id);
        $stmt_update->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Comprovante SIMPLES - SEM GORJETA',
            'conteudo' => $conteudo,
            'versao' => 'HOSTINGER-SIMPLES-1.0'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>