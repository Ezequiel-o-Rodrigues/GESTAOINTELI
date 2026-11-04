<?php
require_once __DIR__ . '/../includes/api-auth.php';
// API: finalizar_comanda.php
// Retorna JSON limpo; usa Database class
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Buffer para evitar qualquer saída acidental
if (!ob_get_level()) ob_start();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $comanda_id = filter_var(is_array($data) ? ($data['comanda_id'] ?? null) : null, FILTER_VALIDATE_INT);

    if (!$comanda_id || $comanda_id < 1) {
        http_response_code(400);
        throw new Exception('Informe uma comanda válida.');
    }

    // Conectar ao banco
    $database = new Database();
    $db = $database->getConnection();

    // Rodar transação: calcular total, atualizar comanda, registrar movimentação e baixar estoque
    $db->beginTransaction();

    $stmt = $db->prepare('SELECT status FROM comandas WHERE id = ? FOR UPDATE');
    $stmt->execute([(int) $comanda_id]);
    $comandaAtual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$comandaAtual || $comandaAtual['status'] !== 'aberta') {
        http_response_code(409);
        throw new Exception('A comanda não existe ou já foi finalizada.');
    }

    // Calcular total da comanda (itens_comanda + itens_livres)
    $stmt = $db->prepare("SELECT COALESCE(SUM(subtotal),0) as total FROM (SELECT subtotal FROM itens_comanda WHERE comanda_id = ? AND cancelado_em IS NULL UNION ALL SELECT subtotal FROM itens_livres WHERE comanda_id = ?) t");
    $stmt->execute([$comanda_id, $comanda_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_comanda = floatval($row['total'] ?? 0);

    $stmt = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'service_fee_enabled'");
    $stmt->execute();
    $repasseAtivo = (string) $stmt->fetchColumn() === '1';
    $stmt = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'commission_rate'");
    $stmt->execute();
    $taxaPercentual = $stmt->fetchColumn();
    if ($taxaPercentual === false || !is_numeric($taxaPercentual)
        || (float) $taxaPercentual < 0 || (float) $taxaPercentual > 1) {
        throw new RuntimeException('A porcentagem da taxa de serviço não está configurada corretamente.');
    }
    $taxa_gorjeta = $repasseAtivo
        ? round($total_comanda * (float) $taxaPercentual, 2)
        : 0;

    // Atualizar comanda
    $stmt = $db->prepare("UPDATE comandas SET status = 'fechada', valor_total = ?, taxa_gorjeta = 0, data_venda = NOW(), data_finalizacao = NOW(), usuario_fechamento_id = ? WHERE id = ? AND status = 'aberta'");
    $stmt->execute([$total_comanda, (int) $_SESSION['usuario_id'], (int) $comanda_id]);
    if ($stmt->rowCount() !== 1) {
        http_response_code(409);
        throw new Exception('A comanda já foi finalizada por outro operador.');
    }
    // O gatilho legado aplica uma taxa própria; substituímos pelo percentual configurado
    // sobre o subtotal da comanda, que é independente das comissões dos garçons.
    $stmt = $db->prepare('UPDATE comandas SET taxa_gorjeta = ? WHERE id = ? AND status = \'fechada\'');
    $stmt->execute([$taxa_gorjeta, (int) $comanda_id]);

    // Baixar estoque: para cada item em itens_comanda, inserir movimentacao tipo 'saida' e decrementar estoque
    $stmt = $db->prepare("SELECT ic.produto_id, ic.quantidade FROM itens_comanda ic WHERE ic.comanda_id = ? AND ic.cancelado_em IS NULL");
    $stmt->execute([$comanda_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mov_stmt = $db->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, observacao, data_movimentacao, created_at) VALUES (?, 'saida', ?, ?, NOW(), NOW())");
    $update_prod = $db->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ?, updated_at = NOW() WHERE id = ?");

    foreach ($itens as $item) {
        $pid = (int)$item['produto_id'];
        $qtd = (int)$item['quantidade'];
        // registrar movimentação
        $mov_stmt->execute([$pid, $qtd, 'Venda - comanda ' . $comanda_id]);
        // decrementar estoque (não bloqueamos negativo aqui)
        $update_prod->execute([$qtd, $pid]);
    }

    // Gerar comprovante automaticamente
    $query_comprovante = "
        SELECT 
            c.id as comanda_id,
            c.valor_total,
            c.taxa_gorjeta,
            c.data_venda,
            c.cliente_nome,
            c.mesa_numero,
            MAX(COALESCE(u.nome, g.nome)) as garcom_nome,
            MAX(g.codigo) as garcom_codigo,
            GROUP_CONCAT(CONCAT(p.nome, '|', ic.quantidade, '|', ic.preco_unitario, '|', ic.subtotal) SEPARATOR ';') as itens
        FROM comandas c
        LEFT JOIN garcons g ON c.garcom_id = g.id
        LEFT JOIN usuarios u ON c.garcom_usuario_id = u.id
        LEFT JOIN itens_comanda ic ON c.id = ic.comanda_id AND ic.cancelado_em IS NULL
        LEFT JOIN produtos p ON ic.produto_id = p.id
        WHERE c.id = ?
        GROUP BY c.id
    ";
    
    $stmt_comp = $db->prepare($query_comprovante);
    $stmt_comp->execute([$comanda_id]);
    $comanda_data = $stmt_comp->fetch(PDO::FETCH_ASSOC);
    
    $comprovante_id = null;
    if ($comanda_data) {
        // Gerar conteúdo do comprovante
        $conteudo = gerarConteudoComprovante($comanda_data);
        
        // Salvar comprovante
        $query_insert = "INSERT INTO comprovantes_venda (comanda_id, conteudo, tipo) VALUES (?, ?, 'cliente')";
        $stmt_insert = $db->prepare($query_insert);
        $stmt_insert->execute([$comanda_id, $conteudo]);
        $comprovante_id = $db->lastInsertId();
    }

    $db->commit();

    // Limpar buffer e enviar JSON
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Comanda finalizada e estoque baixado com sucesso',
        'valor_total' => $total_comanda,
        'taxa_gorjeta' => $taxa_gorjeta,
        'total_a_pagar' => $total_comanda + $taxa_gorjeta,
        'comprovante_id' => $comprovante_id
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    while (ob_get_level()) ob_end_clean();
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function gerarConteudoComprovante($comanda) {
    $itens = explode(';', $comanda['itens']);
    $linhas = [];
    
    // Comandos ESC/POS
    $reset = "\x1B\x40"; // Reset printer
    $center = "\x1B\x61\x01"; // Center align
    $left = "\x1B\x61\x00"; // Left align
    $bold_on = "\x1B\x45\x01"; // Bold on
    $bold_off = "\x1B\x45\x00"; // Bold off
    $cut = "\x1B\x69"; // Partial cut
    
    // Cabeçalho
    $linhas[] = $reset . $center . $bold_on . "ESPETINHO DO JUNIOR" . $bold_off . $left;
    $linhas[] = str_repeat("-", 48);
    $linhas[] = "Comanda: #" . $comanda['comanda_id'];
    $linhas[] = "Data: " . date('d/m/Y H:i', strtotime($comanda['data_venda']));
    if (!empty($comanda['cliente_nome'])) {
        $linhas[] = "Cliente: " . $comanda['cliente_nome'];
    }
    if (!empty($comanda['mesa_numero'])) {
        $linhas[] = "Mesa: " . $comanda['mesa_numero'];
    }
    $identificacaoGarcom = $comanda['garcom_nome'] ?? 'Não informado';
    if (!empty($comanda['garcom_codigo'])) {
        $identificacaoGarcom .= " (" . $comanda['garcom_codigo'] . ")";
    }
    $linhas[] = "Garçom: " . $identificacaoGarcom;
    $linhas[] = str_repeat("-", 48);
    $linhas[] = "QTD  DESCRICAO";
    $linhas[] = "     VALOR UNIT.   SUBTOTAL";
    $linhas[] = str_repeat("-", 48);
    
    // Itens
    foreach ($itens as $item) {
        if (empty($item)) continue;
        
        list($nome, $quantidade, $preco_unitario, $subtotal) = explode('|', $item);
        
        // Formatar nome do produto
        $nome_linhas = str_split($nome, 25);
        
        $linhas[] = str_pad($quantidade, 4) . " " . $nome_linhas[0];
        $linhas[] = "     R$ " . str_pad(number_format($preco_unitario, 2, ',', '.'), 8) . 
                   "   R$ " . number_format($subtotal, 2, ',', '.');
        
        // Linhas adicionais do nome
        for ($i = 1; $i < count($nome_linhas); $i++) {
            $linhas[] = "     " . $nome_linhas[$i];
        }
        
        $linhas[] = ""; // Linha em branco
    }
    
    // Rodapé
    $linhas[] = str_repeat("-", 48);
    $linhas[] = "SUBTOTAL: R$ " . number_format($comanda['valor_total'], 2, ',', '.');
    if ((float) $comanda['taxa_gorjeta'] > 0) {
        $linhas[] = "SERVICO:  R$ " . number_format($comanda['taxa_gorjeta'], 2, ',', '.');
    }
    $linhas[] = str_repeat("=", 48);
    $linhas[] = $bold_on . "TOTAL:    R$ " . number_format($comanda['valor_total'] + $comanda['taxa_gorjeta'], 2, ',', '.') . $bold_off;
    $linhas[] = str_repeat("=", 48);
    $linhas[] = "";
    $linhas[] = $center . "OBRIGADO PELA PREFERÊNCIA!";
    $linhas[] = $center . "VOLTE SEMPRE!";
    $linhas[] = "";
    $linhas[] = $center . date('d/m/Y H:i:s');
    $linhas[] = "\n\n\n\n\n"; // Avançar papel
    $linhas[] = $cut; // Cortar papel
    
    return implode("\n", $linhas);
}

?>