<?php
require_once __DIR__ . '/permissions.php';

$endpointRoles = [
    'adicionar_item.php' => ['admin', 'caixa', 'garcom'],
    'comanda_aberta.php' => ['admin', 'caixa'],
    'criar_tabela.php' => ['admin'],
    'debug.php' => ['admin'],
    'detectar_perdas.php' => ['admin', 'estoque'],
    'finalizar_comanda.php' => ['admin', 'caixa'],
    'find_database.php' => ['admin'],
    'garcons.php' => ['admin', 'caixa'],
    'garcom_comandas.php' => ['garcom'],
    'garcom_itens.php' => ['garcom'],
    'garcom_remover_item.php' => ['garcom'],
    'comandas_abertas.php' => ['admin', 'caixa'],
    'gerar_comprovante.php' => ['admin', 'caixa'],
    'gerar_relatorio.php' => ['admin', 'caixa', 'estoque'],
    'historico_perdas.php' => ['admin', 'caixa', 'estoque'],
    'imprimir_comprovante.php' => ['admin', 'caixa'],
    'imprimir_simples.php' => ['admin', 'caixa'],
    'itens_comanda.php' => ['admin', 'caixa'],
    'marcar_perda_visualizada.php' => ['admin', 'caixa', 'estoque'],
    'nova_comanda.php' => ['admin', 'caixa'],
    'produto_info.php' => ['admin', 'caixa', 'estoque'],
    'produto_info_simple.php' => ['admin', 'caixa', 'estoque'],
    'produtos_categoria.php' => ['admin', 'caixa', 'estoque'],
    'registrar_entrada.php' => ['admin', 'estoque'],
    'relatorio_alertas_perda.php' => ['admin', 'caixa', 'estoque'],
    'relatorio_analise_estoque.php' => ['admin', 'caixa', 'estoque'],
    'relatorio_produtos_vendidos.php' => ['admin', 'caixa', 'estoque'],
    'relatorio_top_categorias.php' => ['admin', 'caixa', 'estoque'],
    'relatorio_vendas_7dias.php' => ['admin', 'caixa', 'estoque'],
    'relatorio_vendas_mensais.php' => ['admin', 'caixa', 'estoque'],
    'relatorio_vendas_periodo.php' => ['admin', 'caixa', 'estoque'],
    'remover_item.php' => ['admin', 'caixa', 'estoque', 'garcom'],
    'salvar_produto.php' => ['admin', 'estoque'],
    'teste_conexao.php' => ['admin'],
    'toggle_produto.php' => ['admin', 'estoque'],
    'verificar_estoque.php' => ['admin', 'caixa', 'estoque'],
    'verificar_tabelas.php' => ['admin'],
];

$endpoint = basename($_SERVER['SCRIPT_NAME'] ?? '');
$allowedRoles = $endpointRoles[$endpoint] ?? [];
$role = currentUserRole();

if (empty($_SESSION['usuario_logado']) || empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Autenticação necessária.']);
    exit;
}

if (!$allowedRoles || !in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Seu perfil não tem permissão para esta operação.']);
    exit;
}
?>
