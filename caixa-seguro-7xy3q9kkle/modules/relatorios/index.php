<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Calcular datas da semana
$inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fim_semana = date('Y-m-d');

// Dados para o dashboard - foco na semana
try {
    // Vendas da semana
    $query_semana = "SELECT 
        COUNT(*) as vendas_semana,
        COALESCE(SUM(valor_total), 0) as faturamento_semana,
        COALESCE(AVG(valor_total), 0) as ticket_medio_semana
        FROM comandas 
        WHERE status = 'fechada' 
        AND DATE(data_venda) BETWEEN :inicio_semana AND :fim_semana";
    
    $stmt_semana = $db->prepare($query_semana);
    $stmt_semana->bindParam(':inicio_semana', $inicio_semana);
    $stmt_semana->bindParam(':fim_semana', $fim_semana);
    $stmt_semana->execute();
    $dashboard_semana = $stmt_semana->fetch(PDO::FETCH_ASSOC);
    
    // Alertas de estoque
    $query_estoque = "SELECT COUNT(*) as alertas_estoque FROM produtos WHERE estoque_atual <= estoque_minimo AND ativo = 1";
    $stmt_estoque = $db->prepare($query_estoque);
    $stmt_estoque->execute();
    $alertas_estoque = $stmt_estoque->fetch(PDO::FETCH_ASSOC);
    
    // Produtos com perdas (últimos 30 dias)
    $query_perdas = "SELECT COUNT(*) as total_perdas FROM (
        SELECT p.id
        FROM produtos p
        WHERE p.ativo = 1
        AND (
            (SELECT COALESCE(SUM(me.quantidade), 0) FROM movimentacoes_estoque me WHERE me.produto_id = p.id AND me.tipo = 'entrada' AND me.data_movimentacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)) -
            (SELECT COALESCE(SUM(ic.quantidade), 0) FROM itens_comanda ic JOIN comandas c ON ic.comanda_id = c.id WHERE ic.produto_id = p.id AND c.status = 'fechada' AND c.data_venda >= DATE_SUB(NOW(), INTERVAL 30 DAY)) -
            p.estoque_atual
        ) > 0
    ) as perdas";
    
    $stmt_perdas = $db->prepare($query_perdas);
    $stmt_perdas->execute();
    $total_perdas = $stmt_perdas->fetch(PDO::FETCH_ASSOC);
    
    $dashboard = array_merge($dashboard_semana, $alertas_estoque, $total_perdas);
    
} catch (Exception $e) {
    $dashboard = [
        'vendas_semana' => 0,
        'faturamento_semana' => 0,
        'ticket_medio_semana' => 0,
        'alertas_estoque' => 0,
        'total_perdas' => 0
    ];
}

// Produtos mais vendidos (da semana)
try {
    $query_top_produtos = "
        SELECT 
            p.nome,
            cat.nome as categoria,
            SUM(ic.quantidade) as total_vendido,
            SUM(ic.subtotal) as valor_total_vendido
        FROM itens_comanda ic
        JOIN produtos p ON ic.produto_id = p.id
        JOIN categorias cat ON p.categoria_id = cat.id
        JOIN comandas c ON ic.comanda_id = c.id
        WHERE c.status = 'fechada'
        AND DATE(c.data_venda) BETWEEN :inicio_semana AND :fim_semana
        GROUP BY p.id
        ORDER BY total_vendido DESC
        LIMIT 10";
    
    $stmt_top_produtos = $db->prepare($query_top_produtos);
    $stmt_top_produtos->bindParam(':inicio_semana', $inicio_semana);
    $stmt_top_produtos->bindParam(':fim_semana', $fim_semana);
    $stmt_top_produtos->execute();
    $top_produtos = $stmt_top_produtos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_produtos = [];
}
?>

<div class="relatorios-container">
    <h2>📊 Relatórios e Analytics</h2>
    
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <h3>Vendas dessa semana</h3>
            <div class="numero"><?= $dashboard['vendas_semana'] ?? 0 ?></div>
            <p>Comandas fechadas</p>
        </div>
        
        <div class="dashboard-card">
            <h3>Faturamento dessa semana</h3>
            <div class="numero"><?= formatarMoeda($dashboard['faturamento_semana'] ?? 0) ?></div>
            <p>Valor total vendido</p>
        </div>
        
        <div class="dashboard-card <?= ($dashboard['alertas_estoque'] ?? 0) > 0 ? 'alerta' : '' ?>">
            <h3>Alertas Estoque</h3>
            <div class="numero <?= ($dashboard['alertas_estoque'] ?? 0) > 0 ? 'alerta' : '' ?>">
                <?= $dashboard['alertas_estoque'] ?? 0 ?>
            </div>
            <p>Produtos com estoque baixo</p>
        </div>
        
        <div class="dashboard-card <?= ($dashboard['total_perdas'] ?? 0) > 0 ? 'alerta' : '' ?>" style="cursor: pointer;" onclick="abrirHistoricoPerdas()">
            <h3>Perdas Identificadas</h3>
            <div class="numero <?= ($dashboard['total_perdas'] ?? 0) > 0 ? 'alerta' : '' ?>" id="perdas-nao-visualizadas">
                0
            </div>
            <p>Produtos com divergência</p>
            <button class="btn btn-sm btn-outline-primary mt-2" onclick="event.stopPropagation(); abrirHistoricoPerdas();">
                📋 Ver Histórico
            </button>
        </div>
    </div>



    <div class="relatorios-section">
        <div class="filtros-relatorios">
            <h3>Filtrar Relatórios</h3>
            <div class="filtros-grid">
                <div class="filtro-group">
                    <label>Data Início:</label>
                    <input type="date" id="data-inicio" class="form-input" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="filtro-group">
                    <label>Data Fim:</label>
                    <input type="date" id="data-fim" class="form-input" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="filtro-group">
                    <label>Tipo de Relatório:</label>
                    <select id="tipo-relatorio" class="form-select">
                        <option value="vendas">Vendas por Período</option>
                        <option value="produtos">Produtos Mais Vendidos</option>
                        <option value="analise_estoque">🔍 Análise de Estoque e Perdas</option>
                    </select>
                </div>
                <div class="filtro-group">
                    <button class="btn btn-primary" onclick="gerarRelatorio()">Gerar Relatório</button>
                    <button class="btn" onclick="exportarRelatorio()">📥 Exportar</button>
                    <button class="btn btn-warning" onclick="limparPerdasDuplicadas()" title="Limpar perdas duplicadas">🧹 Limpar Duplicadas</button>
                </div>
            </div>
        </div>

        <div class="resultados-relatorio">
            <h3>Produtos Mais Vendidos da Semana (Top 10)</h3>
            <?php if (count($top_produtos) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_produtos as $produto): ?>
                        <tr>
                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                            <td><?= htmlspecialchars($produto['categoria']) ?></td>
                            <td><?= $produto['total_vendido'] ?></td>
                            <td><?= formatarMoeda($produto['valor_total_vendido']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="sem-dados">Nenhuma venda registrada nesta semana</div>
            <?php endif; ?>
        </div>

        <div class="graficos-section">
            <div class="grafico-card">
                <h4>Vendas por Dia (Últimos 7 dias)</h4>
                <canvas id="grafico-vendas"></canvas>
            </div>
            <div class="grafico-card">
                <h4>Top Categorias</h4>
                <canvas id="grafico-categorias"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="relatorios.js?v=<?= time() ?>"></script>

<style>
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.dashboard-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid #3498db;
}

.dashboard-card.alerta {
    border-left-color: #e74c3c;
}

.dashboard-card .numero {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2c3e50;
    margin: 0.5rem 0;
}

.dashboard-card .numero.alerta {
    color: #e74c3c;
}



.filtros-relatorios {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin: 2rem 0;
}

.filtros-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filtro-group {
    display: flex;
    flex-direction: column;
}

.graficos-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.grafico-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    height: 300px;
}

.sem-dados {
    text-align: center;
    padding: 2rem;
    color: #7f8c8d;
    font-style: italic;
    background: #f8f9fa;
    border-radius: 5px;
}

.analise-estoque-table th {
    background: #f8f9fa;
    position: sticky;
    top: 0;
}

.perda-destaque {
    background: #fff5f5 !important;
    font-weight: bold;
}

.sem-perda {
    background: #f0fff4 !important;
}
</style>

<?php require_once '../../includes/footer.php'; ?>