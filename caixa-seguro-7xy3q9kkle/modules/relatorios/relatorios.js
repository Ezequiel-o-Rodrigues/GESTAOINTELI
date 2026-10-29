// Limpeza completa de estilos antigos
document.querySelectorAll('style[data-relatorios]').forEach(style => style.remove());
document.querySelectorAll('style').forEach(style => {
    if (style.textContent.includes('analise-estoque') || 
        style.textContent.includes('table-analise-estoque')) {
        style.remove();
    }
});
class Relatorios {
    constructor() {
        this.graficoVendas = null;
        this.graficoCategorias = null;
        this.graficoMensal = null;
        this.init();
    }

    init() {
        this.inicializarGraficos();
        this.carregarDadosIniciais();
        this.carregarAlertasPerda();
    }

    async carregarMetricasPerdas() {
        try {
            const response = await fetch('../../api/relatorio_metricas_perdas.php');
            const dados = await response.json();

            if (dados.success) {
                this.atualizarDashboardMetricas(dados.metricas, dados.top_perdas);
            }
        } catch (error) {
            console.error('Erro ao carregar métricas de perdas:', error);
        }
    }

        atualizarDashboardMetricas(metricas, topPerdas) {
        // Atualizar cards de métricas
        if (document.getElementById('total-produtos-analisados')) {
            document.getElementById('total-produtos-analisados').textContent = 
                metricas.total_produtos_analisados || 0;
            document.getElementById('produtos-com-perda').textContent = 
                metricas.produtos_com_perda || 0;
            document.getElementById('unidades-perdidas').textContent = 
                metricas.total_unidades_perdidas || 0;
            document.getElementById('valor-perdido').textContent = 
                this.formatarMoeda(metricas.total_valor_perdido || 0);
            document.getElementById('percentual-perda').textContent = 
                (metricas.percentual_perda_faturamento || 0).toFixed(2) + '%';
        }

        // Atualizar lista de top perdas
        this.atualizarTopPerdas(topPerdas);
    }

     atualizarTopPerdas(topPerdas) {
        const container = document.getElementById('top-perdas-list');
        if (!container) return;

        if (!topPerdas || topPerdas.length === 0) {
            container.innerHTML = '<div class="carregando">✅ Nenhuma perda significativa identificada</div>';
            return;
        }

        let html = '';
        topPerdas.forEach(produto => {
            html += `
                <div class="perda-item">
                    <div>
                        <div class="perda-produto">${produto.nome}</div>
                        <div class="perda-categoria">${produto.categoria}</div>
                    </div>
                    <div class="perda-valor">
                        ${this.formatarMoeda(produto.perdas_valor || 0)}
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    inicializarGraficos() {
        const ctxVendas = document.getElementById('grafico-vendas');
        const ctxCategorias = document.getElementById('grafico-categorias');
        const ctxMensal = document.getElementById('grafico-mensal');

        if (ctxVendas) {
            this.graficoVendas = new Chart(ctxVendas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Vendas (R$)',
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Vendas dos Últimos 7 Dias'
                        }
                    }
                }
            });
        }

        if (ctxCategorias) {
            this.graficoCategorias = new Chart(ctxCategorias.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f39c12', 
                            '#9b59b6', '#1abc9c', '#d35400', '#34495e'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        if (ctxMensal) {
            this.graficoMensal = new Chart(ctxMensal.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Vendas Mensais (R$)',
                        data: [],
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: '#3498db',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Vendas por Mês'
                        }
                    }
                }
            });
        }
    }

    async carregarDadosIniciais() {
        await this.carregarVendasUltimos7Dias();
        await this.carregarTopCategorias();
        await this.carregarVendasMensais();
    }

    async carregarVendasUltimos7Dias() {
        try {
            const response = await fetch('../../api/relatorio_vendas_7dias.php');
            const dados = await response.json();

            if (dados.success && this.graficoVendas) {
                this.graficoVendas.data.labels = dados.labels;
                this.graficoVendas.data.datasets[0].data = dados.valores;
                this.graficoVendas.update();
            }
        } catch (error) {
            console.error('Erro ao carregar vendas:', error);
        }
    }

    async carregarTopCategorias() {
        try {
            const response = await fetch('../../api/relatorio_top_categorias.php');
            const dados = await response.json();

            if (dados.success && this.graficoCategorias) {
                this.graficoCategorias.data.labels = dados.labels;
                this.graficoCategorias.data.datasets[0].data = dados.valores;
                this.graficoCategorias.update();
            }
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
        }
    }

    async carregarVendasMensais() {
        try {
            const response = await fetch('../../api/relatorio_vendas_mensais.php');
            const dados = await response.json();

            if (dados.success && this.graficoMensal) {
                this.graficoMensal.data.labels = dados.grafico.labels;
                this.graficoMensal.data.datasets[0].data = dados.grafico.valores;
                this.graficoMensal.update();
            }
        } catch (error) {
            console.error('Erro ao carregar vendas mensais:', error);
        }
    }

    async carregarAlertasPerda() {
        try {
            const response = await fetch('../../api/relatorio_alertas_perda_corrigido.php');
            const dados = await response.json();

            if (dados.success) {
                // Atualizar apenas o contador no card
                const contador = document.getElementById('perdas-nao-visualizadas');
                if (contador) {
                    contador.textContent = dados.data.length;
                    
                    // Atualizar classe do card
                    const card = contador.closest('.dashboard-card');
                    if (dados.data.length > 0) {
                        card.classList.add('alerta');
                        contador.classList.add('alerta');
                    } else {
                        card.classList.remove('alerta');
                        contador.classList.remove('alerta');
                    }
                }
            }
        } catch (error) {
            console.error('Erro ao carregar alertas:', error);
        }
    }



    async gerarRelatorio() {
    const dataInicio = document.getElementById('data-inicio').value;
    const dataFim = document.getElementById('data-fim').value;
    const tipoRelatorio = document.getElementById('tipo-relatorio').value;

    if (!dataInicio || !dataFim) {
        alert('Selecione as datas de início e fim');
        return;
    }

    try {
        let url = '';
        let params = `?data_inicio=${dataInicio}&data_fim=${dataFim}`;

        switch(tipoRelatorio) {
            case 'vendas':
                url = '../../api/relatorio_vendas_periodo.php';
                params += '&tipo=diario';
                break;
            case 'produtos':
                url = '../../api/relatorio_produtos_vendidos.php';
                break;
            case 'analise_estoque':
                url = '../../api/relatorio_analise_estoque.php';
                const categoria = document.getElementById('filtro-categoria')?.value;
                const valorMinimo = document.getElementById('filtro-valor-minimo')?.value;
                const tipoFiltro = document.getElementById('filtro-tipo')?.value;
                
                if (categoria) params += `&categoria_id=${categoria}`;
                if (valorMinimo) params += `&valor_minimo=${valorMinimo}`;
                if (tipoFiltro) params += `&tipo_filtro=${tipoFiltro}`;
            
                break;
        }

        console.log('URL da requisição:', url + params);

        const response = await fetch(url + params);
        const resultado = await response.json();

        console.log('Resultado da API:', resultado);

        if (resultado.success) {
            let dadosArray = Array.isArray(resultado.data) ? resultado.data : [];
            this.exibirResultados(dadosArray, tipoRelatorio, resultado.totais, resultado.periodo);
        } else {
            alert('Erro ao gerar relatório: ' + (resultado.message || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro completo ao gerar relatório:', error);
        alert('Erro ao gerar relatório: ' + error.message);
    }
}

    exibirResultados(dados, tipo, totais = {}, periodo = {}) {
    const container = document.querySelector('.resultados-relatorio');
    
    let html = '';
      if (tipo === 'analise_estoque') {
        html += this.criarFiltrosAvancados();
    }
    switch(tipo) {
        case 'vendas':
            html = this.criarTabelaVendas(dados);
            break;
        case 'produtos':
            html = this.criarTabelaProdutos(dados);
            break;
        case 'analise_estoque':
            html = this.criarTabelaAnaliseEstoque(dados, totais, periodo);
            break;
        default:
            html = '<div class="sem-dados">Tipo de relatório não reconhecido</div>';
    }

    container.innerHTML = html;
}
    criarTabelaVendas(dados) {
        if (!Array.isArray(dados) || dados.length === 0) {
            return '<div class="sem-dados">Nenhuma venda encontrada no período selecionado</div>';
        }

        let html = `<h3>📈 Vendas por Período</h3>
                   <div class="table-responsive">
                   <table class="table">
                   <thead>
                   <tr><th>Data</th><th>Comandas</th><th>Valor Total</th><th>Gorjetas</th><th>Ticket Médio</th></tr>
                   </thead><tbody>`;

        dados.forEach(item => {
            html += `<tr>
                    <td>${item.data || item.data_venda || item.periodo || item.mes_ano || 'N/A'}</td>
                    <td>${item.total_comandas || 0}</td>
                    <td>${this.formatarMoeda(item.valor_total || item.valor_total_vendas)}</td>
                    <td>${this.formatarMoeda(item.total_gorjetas)}</td>
                    <td>${this.formatarMoeda(item.ticket_medio)}</td>
                    </tr>`;
        });

        const totalVendas = dados.reduce((sum, item) => sum + parseFloat(item.valor_total || item.valor_total_vendas || 0), 0);
        const totalComandas = dados.reduce((sum, item) => sum + parseInt(item.total_comandas || 0), 0);

        html += `<tr class="total-row">
                <td><strong>Total</strong></td>
                <td><strong>${totalComandas}</strong></td>
                <td><strong>${this.formatarMoeda(totalVendas)}</strong></td>
                <td colspan="2"></td>
                </tr>`;
        html += `</tbody></table></div>`;
        return html;
    }

    criarTabelaProdutos(dados) {
        if (!Array.isArray(dados) || dados.length === 0) {
            return '<div class="sem-dados">Nenhum produto encontrado no período selecionado</div>';
        }

        let html = `<h3>📊 Produtos Mais Vendidos</h3>
                   <div class="table-responsive">
                   <table class="table">
                   <thead>
                   <tr><th>Produto</th><th>Categoria</th><th>Quantidade Vendida</th><th>Valor Total</th><th>Preço Unitário</th></tr>
                   </thead><tbody>`;

        dados.forEach(item => {
            html += `<tr>
                    <td><strong>${item.produto_nome}</strong></td>
                    <td>${item.categoria_nome}</td>
                    <td>${item.total_vendido}</td>
                    <td>${this.formatarMoeda(item.valor_total)}</td>
                    <td>${this.formatarMoeda(item.preco_unitario)}</td>
                    </tr>`;
        });

        const totalQuantidade = dados.reduce((sum, item) => sum + parseInt(item.total_vendido || 0), 0);
        const totalValor = dados.reduce((sum, item) => sum + parseFloat(item.valor_total || 0), 0);

        html += `<tr class="total-row">
                <td colspan="2"><strong>Total</strong></td>
                <td><strong>${totalQuantidade}</strong></td>
                <td><strong>${this.formatarMoeda(totalValor)}</strong></td>
                <td></td>
                </tr>`;
        html += `</tbody></table></div>`;
        return html;
    }

    criarTabelaAnaliseEstoque(dados, totais, periodo) {
    if (!Array.isArray(dados) || dados.length === 0) {
        return '<div class="sem-dados">Nenhum dado encontrado para análise de estoque no período selecionado</div>';
    }

    const { data_inicio, data_fim } = periodo;

    let html = `
        <div class="analise-estoque-container">
            <div class="analise-header">
                <h3><i class="bi bi-graph-up"></i> Análise de Estoque e Perdas</h3>
                <div class="periodo-info">
                    <strong>Período:</strong> ${this.formatarData(data_inicio)} à ${this.formatarData(data_fim)}
                </div>
            </div>

            <div class="totais-analise">
                <div class="total-item">
                    <div class="total-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="total-content">
                        <div class="total-value ${totais.total_produtos_com_perda > 0 ? 'alerta' : ''}">
                            ${totais.total_produtos_com_perda}
                        </div>
                        <div class="total-label">Produtos com Perdas</div>
                    </div>
                </div>
                
                <div class="total-item">
                    <div class="total-icon">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="total-content">
                        <div class="total-value ${totais.total_perdas_quantidade > 0 ? 'alerta' : ''}">
                            ${totais.total_perdas_quantidade}
                        </div>
                        <div class="total-label">Unidades Perdidas</div>
                    </div>
                </div>
                
                <div class="total-item">
                    <div class="total-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="total-content">
                        <div class="total-value ${totais.total_perdas_valor > 0 ? 'alerta' : ''}">
                            ${this.formatarMoeda(totais.total_perdas_valor)}
                        </div>
                        <div class="total-label">Valor das Perdas</div>
                    </div>
                </div>
                
                <div class="total-item">
                    <div class="total-icon">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="total-content">
                        <div class="total-value">
                            ${this.formatarMoeda(totais.total_faturamento)}
                        </div>
                        <div class="total-label">Faturamento Total</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-analise-estoque">
                    <thead class="analise-thead">
                        <tr>
                            <th class="produto-col">
                                <i class="bi bi-tag"></i>
                                <span class="header-text">Produto</span>
                            </th>
                            <th class="categoria-col">
                                <i class="bi bi-grid"></i>
                                <span class="header-text">Categoria</span>
                            </th>
                            <th class="numero-col">
                                <i class="bi bi-box-arrow-in-down"></i>
                                <span class="header-text">Estoque Inicial</span>
                            </th>
                            <th class="numero-col positivo">
                                <i class="bi bi-plus-circle"></i>
                                <span class="header-text">+ Entradas</span>
                            </th>
                            <th class="numero-col negativo">
                                <i class="bi bi-dash-circle"></i>
                                <span class="header-text">- Vendidos</span>
                            </th>
                            <th class="numero-col teorico">
                                <i class="bi bi-calculator"></i>
                                <span class="header-text">= Estoque Teórico</span>
                            </th>
                            <th class="numero-col real">
                                <i class="bi bi-clipboard-check"></i>
                                <span class="header-text">Estoque Real</span>
                            </th>
                            <th class="numero-col perda">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span class="header-text">Perdas (Qtd)</span>
                            </th>
                            <th class="numero-col perda">
                                <i class="bi bi-currency-dollar"></i>
                                <span class="header-text">Perdas (R$)</span>
                            </th>
                            <th class="numero-col">
                                <i class="bi bi-graph-up"></i>
                                <span class="header-text">Faturamento</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>`;


    dados.forEach(item => {
        const temPerda = item.perdas_quantidade > 0;
        const classeLinha = temPerda ? 'linha-com-perda' : 'linha-sem-perda';
        const iconeStatus = temPerda ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
        const corStatus = temPerda ? 'status-perda' : 'status-ok';
        
        html += `
                        <tr class="${classeLinha}">
                            <td class="produto-cell">
                                <div class="d-flex align-items-center">
                                    <i class="bi ${iconeStatus} ${corStatus} me-2"></i>
                                    <strong>${item.nome}</strong>
                                </div>
                            </td>
                            <td class="categoria-cell">
                                <span class="badge categoria-badge">${item.categoria}</span>
                            </td>
                            <td class="numero-cell">${item.estoque_inicial}</td>
                            <td class="numero-cell positivo">${item.entradas_periodo}</td>
                            <td class="numero-cell negativo">${item.vendidas_periodo}</td>
                            <td class="numero-cell teorico">
                                <strong>${item.estoque_teorico_final}</strong>
                            </td>
                            <td class="numero-cell real">${item.estoque_real_atual}</td>
                            <td class="numero-cell ${temPerda ? 'destaque-perda' : ''}">
                                ${item.perdas_quantidade}
                            </td>
                            <td class="numero-cell ${temPerda ? 'destaque-perda' : ''}">
                                ${this.formatarMoeda(item.perdas_valor)}
                            </td>
                            <td class="numero-cell faturamento">
                                ${this.formatarMoeda(item.faturamento_periodo)}
                            </td>
                        </tr>`;
    });

    html += `
                    </tbody>
                </table>
            </div>

            <div class="analise-footer">
                <div class="legenda-analise">
                    <div class="legenda-item">
                        <div class="legenda-cor perda"></div>
                        <span>Produto com perdas identificadas</span>
                    </div>
                    <div class="legenda-item">
                        <div class="legenda-cor sem-perda"></div>
                        <span>Sem perdas no período</span>
                    </div>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="exportarParaExcel()">
                        <i class="bi bi-file-earmark-excel"></i>
                        Exportar Excel
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="imprimirRelatorio()">
                        <i class="bi bi-printer"></i>
                        Imprimir
                    </button>
                </div>
            </div>
        </div>`;
    
    return html;
}

     criarFiltrosAvancados() {
        return `
            <div class="filtros-avancados">
                <h4>🎯 Filtros Avançados para Análise de Estoque</h4>
                <div class="filtros-grid-avancado">
                    <div class="filtro-group">
                        <label>Categoria:</label>
                        <select id="filtro-categoria" class="form-select">
                            <option value="">Todas as Categorias</option>
                            <option value="1">Espetos</option>
                            <option value="2">Porções</option>
                            <option value="3">Bebidas</option>
                            <option value="4">Cervejas</option>
                            <option value="5">Diversos</option>
                        </select>
                    </div>
                    <div class="filtro-group">
                        <label>Valor Mínimo de Perda:</label>
                        <input type="number" id="filtro-valor-minimo" class="form-input" 
                               placeholder="R$ 0,00" step="0.01" min="0">
                    </div>
                    <div class="filtro-group">
                        <label>Mostrar Apenas:</label>
                        <select id="filtro-tipo" class="form-select">
                            <option value="todos">Todos os Produtos</option>
                            <option value="com_perda">Apenas com Perdas</option>
                            <option value="sem_perda">Apenas sem Perdas</option>
                        </select>
                    </div>
                    <div class="filtro-group">
                        <button class="btn btn-secondary" onclick="aplicarFiltrosAvancados()">
                            🔄 Aplicar Filtros
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    formatarMoeda(valor) {
        const numero = parseFloat(valor) || 0;
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(numero);
    }

    exportarRelatorio() {
        const tabela = document.querySelector('.resultados-relatorio table');
        if (!tabela) {
            alert('Gere um relatório primeiro para exportar');
            return;
        }

        const html = tabela.outerHTML;
        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'relatorio.xls';
        a.click();
        URL.revokeObjectURL(url);
    }
    
    formatarData(data) {
        return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
    }

    async marcarPerdaVisualizada(perdaId) {
        try {
            const response = await fetch('../../api/marcar_perda_visualizada.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ perda_id: perdaId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Atualizar interface do modal em tempo real
                const statusCell = document.getElementById(`status-${perdaId}`);
                if (statusCell) {
                    statusCell.innerHTML = '<span class="text-success">✅ Visualizada</span>';
                }
                
                // Atualizar data de visualização na linha
                const row = document.getElementById(`perda-row-${perdaId}`);
                if (row) {
                    const cells = row.getElementsByTagName('td');
                    if (cells.length >= 8) {
                        cells[7].textContent = new Date().toLocaleDateString('pt-BR');
                    }
                }
                
                this.mostrarToast('Perda marcada como visualizada', 'success');
                // Recarregar contador de perdas
                this.carregarAlertasPerda();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erro ao marcar perda:', error);
            this.mostrarToast('Erro ao marcar perda como visualizada', 'error');
        }
    }



    async abrirHistoricoPerdas(filtros = {}) {
        try {
            let url = '../../api/historico_perdas.php';
            const params = new URLSearchParams();
            
            if (filtros.data_inicio && filtros.data_fim) {
                params.append('data_inicio', filtros.data_inicio);
                params.append('data_fim', filtros.data_fim);
            } else if (filtros.mes_ano) {
                params.append('mes_ano', filtros.mes_ano);
            }
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                this.mostrarModalHistoricoPerdas(result.data, result.filtros);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
            this.mostrarToast('Erro ao carregar histórico de perdas', 'error');
        }
    }

    mostrarModalHistoricoPerdas(perdas, filtros = {}) {
        const modalHtml = `
            <div class="modal fade" id="modalHistoricoPerdas" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">📋 Histórico de Perdas de Estoque</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${this.criarFiltrosData(filtros)}
                            <div id="tabela-historico-container">
                                ${this.criarTabelaHistoricoPerdas(perdas)}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-primary" onclick="exportarHistoricoPerdas()">
                                📄 Exportar
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal existente se houver
        const modalExistente = document.getElementById('modalHistoricoPerdas');
        if (modalExistente) {
            modalExistente.remove();
        }
        
        // Adicionar novo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalHistoricoPerdas'));
        modal.show();
    }

    criarTabelaHistoricoPerdas(perdas) {
        if (!perdas || perdas.length === 0) {
            return '<div class="text-center p-4">📊 Nenhuma perda registrada no histórico</div>';
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Data</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Valor</th>
                            <th>Motivo</th>
                            <th>Status</th>
                            <th>Visualizada em</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        perdas.forEach(perda => {
            const dataIdentificacao = new Date(perda.data_identificacao).toLocaleDateString('pt-BR');
            const dataVisualizacao = perda.data_visualizacao ? 
                new Date(perda.data_visualizacao).toLocaleDateString('pt-BR') : '-';
            let statusContent;
            if (perda.visualizada) {
                statusContent = `<span class="text-success">✅ Visualizada</span>`;
            } else {
                statusContent = `<button class="btn btn-sm btn-success" onclick="marcarPerdaVisualizada(${perda.id})">
                    ✓ Visualizar Perda
                </button>`;
            }

            html += `
                <tr id="perda-row-${perda.id}">
                    <td>${dataIdentificacao}</td>
                    <td><strong>${perda.produto_nome}</strong></td>
                    <td><span class="badge bg-secondary">${perda.categoria_nome}</span></td>
                    <td class="text-center">${perda.quantidade_perdida}</td>
                    <td class="text-end">${this.formatarMoeda(perda.valor_perda)}</td>
                    <td>${perda.motivo || 'Diferença de inventário'}</td>
                    <td class="text-center" id="status-${perda.id}">${statusContent}</td>
                    <td>${dataVisualizacao}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Total de Perdas</h6>
                                <h4 class="text-danger">${perdas.length}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Valor Total</h6>
                                <h4 class="text-danger">${this.formatarMoeda(perdas.reduce((sum, p) => sum + parseFloat(p.valor_perda || 0), 0))}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return html;
    }



    criarFiltrosData(filtros = {}) {
        const hoje = new Date();
        const mesAtual = hoje.getFullYear() + '-' + String(hoje.getMonth() + 1).padStart(2, '0');
        
        return `
            <div class="filtros-data-container mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">📅 Filtrar por Período</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Mês/Ano:</label>
                                <input type="month" id="filtro-mes-ano" class="form-control" 
                                       value="${filtros.mes_ano || mesAtual}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data Início:</label>
                                <input type="date" id="filtro-data-inicio" class="form-control" 
                                       value="${filtros.data_inicio || ''}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data Fim:</label>
                                <input type="date" id="filtro-data-fim" class="form-control" 
                                       value="${filtros.data_fim || ''}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="btn-group w-100">
                                    <button class="btn btn-primary" onclick="aplicarFiltroData()">
                                        🔍 Filtrar
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="limparFiltroData()">
                                        🗑️ Limpar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                📝 <strong>Dica:</strong> Use "Mês/Ano" para filtros rápidos ou "Data Início/Fim" para períodos específicos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async aplicarFiltroData() {
        const mesAno = document.getElementById('filtro-mes-ano').value;
        const dataInicio = document.getElementById('filtro-data-inicio').value;
        const dataFim = document.getElementById('filtro-data-fim').value;
        
        let filtros = {};
        
        // Priorizar filtro por período específico se ambas as datas estiverem preenchidas
        if (dataInicio && dataFim) {
            if (dataInicio > dataFim) {
                this.mostrarToast('Data de início deve ser anterior à data fim', 'error');
                return;
            }
            filtros = { data_inicio: dataInicio, data_fim: dataFim };
        } else if (mesAno) {
            filtros = { mes_ano: mesAno };
        }
        
        // Recarregar dados com filtro
        try {
            let url = '../../api/historico_perdas.php';
            const params = new URLSearchParams();
            
            if (filtros.data_inicio && filtros.data_fim) {
                params.append('data_inicio', filtros.data_inicio);
                params.append('data_fim', filtros.data_fim);
            } else if (filtros.mes_ano) {
                params.append('mes_ano', filtros.mes_ano);
            }
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                // Atualizar apenas a tabela
                const container = document.getElementById('tabela-historico-container');
                if (container) {
                    container.innerHTML = this.criarTabelaHistoricoPerdas(result.data);
                }
                
                const totalFiltrado = result.data.length;
                this.mostrarToast(`Filtro aplicado: ${totalFiltrado} perdas encontradas`, 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erro ao aplicar filtro:', error);
            this.mostrarToast('Erro ao aplicar filtro', 'error');
        }
    }

    limparFiltroData() {
        document.getElementById('filtro-mes-ano').value = '';
        document.getElementById('filtro-data-inicio').value = '';
        document.getElementById('filtro-data-fim').value = '';
        
        // Recarregar todos os dados
        this.abrirHistoricoPerdas();
    }

    exportarHistoricoPerdas() {
        const tabela = document.querySelector('#modalHistoricoPerdas table');
        if (!tabela) {
            this.mostrarToast('Nenhuma tabela para exportar', 'error');
            return;
        }
        
        const html = tabela.outerHTML;
        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `historico_perdas_${new Date().toISOString().split('T')[0]}.xls`;
        a.click();
        URL.revokeObjectURL(url);
        
        this.mostrarToast('Histórico exportado com sucesso', 'success');
    }

    mostrarToast(mensagem, tipo = 'info') {
        // Criar toast simples
        const toast = document.createElement('div');
        toast.className = `alert alert-${tipo === 'error' ? 'danger' : tipo === 'success' ? 'success' : 'info'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
}
function aplicarFiltrosAvancados() {
    relatorios.gerarRelatorio();
}

// Inicializar relatórios
const relatorios = new Relatorios();

// REMOVA qualquer style antigo
const oldStyles = document.querySelectorAll('style[data-relatorios]');
oldStyles.forEach(style => style.remove());

// INJETE este novo CSS com alta prioridade
const style = document.createElement('style');
style.setAttribute('data-relatorios', 'true');
style.textContent = `
    /* CONTAINER PRINCIPAL */
    .analise-estoque-container {
        background: white !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 20px rgba(0,0,0,0.1) !important;
        overflow: hidden !important;
        margin-bottom: 2rem !important;
    }

    .analise-header {
        background: linear-gradient(135deg, #2c3e50, #34495e) !important;
        color: white !important;
        padding: 1.5rem !important;
        border-bottom: none !important;
    }

    .analise-header h3 {
        margin: 0 !important;
        font-weight: 600 !important;
        color: white !important;
    }

    .periodo-info {
        background: rgba(255,255,255,0.1) !important;
        padding: 0.75rem 1rem !important;
        border-radius: 6px !important;
        margin-top: 0.5rem !important;
        font-size: 0.9rem !important;
        color: white !important;
    }

    /* CARDS DE TOTAIS */
    .totais-analise {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
        gap: 1rem !important;
        padding: 1.5rem !important;
        background: #f8f9fa !important;
    }

    .total-item {
        background: white !important;
        padding: 1.25rem !important;
        border-radius: 10px !important;
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
        border-left: 4px solid #3498db !important;
    }

    .total-item .total-icon {
        font-size: 1.5rem !important;
        color: #3498db !important;
    }

    .total-item .total-value {
        font-size: 1.5rem !important;
        font-weight: bold !important;
        color: #2c3e50 !important;
        line-height: 1.2 !important;
    }

    .total-item .total-value.alerta {
        color: #e74c3c !important;
    }

    .total-item .total-label {
        font-size: 0.85rem !important;
        color: #7f8c8d !important;
        margin-top: 0.25rem !important;
    }

    /* CABEÇALHOS DA TABELA - MAIS VISÍVEIS */
    .table-analise-estoque {
        margin: 0 !important;
        font-size: 0.85rem !important;
        width: 100% !important;
        border-collapse: collapse !important;
        background: white !important;
    }

    .analise-thead th {
        background: linear-gradient(135deg, #2c3e50, #34495e) !important;
        color: white !important;
        font-weight: 700 !important;
        border-bottom: 3px solid #1a2530 !important;
        padding: 1rem 0.75rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
        font-size: 0.9rem !important;
        text-transform: none !important;
        letter-spacing: 0.5px !important;
    }

    .header-text {
        font-weight: 700 !important;
        color: white !important;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
        margin-left: 5px !important;
    }

    /* MELHOR CONTRASTE PARA OS ÍCONES */
    .analise-thead th i {
        color: #ecf0f1 !important;
        margin-right: 5px !important;
        font-size: 0.95rem !important;
    }

    /* CORES DAS COLUNAS - MANTENDO CONTRASTE */
    .positivo { 
        background: rgba(39, 174, 96, 0.1) !important; 
        border-left: 3px solid #27ae60 !important;
    }
    .negativo { 
        background: rgba(231, 76, 60, 0.1) !important;
        border-left: 3px solid #e74c3c !important;
    }
    .teorico { 
        background: rgba(52, 152, 219, 0.1) !important;
        border-left: 3px solid #3498db !important;
        font-weight: bold !important; 
    }
    .real { 
        background: rgba(44, 62, 80, 0.1) !important;
        border-left: 3px solid #2c3e50 !important;
    }
    .perda { 
        background: rgba(231, 76, 60, 0.15) !important;
        border-left: 3px solid #e74c3c !important;
    }
    .faturamento { 
        background: rgba(39, 174, 96, 0.1) !important;
        border-left: 3px solid #27ae60 !important;
    }

    /* CÉLULAS */
    .table-analise-estoque td {
        padding: 0.75rem !important;
        vertical-align: middle !important;
        text-align: center !important;
        border-color: #e9ecef !important;
        color: #2c3e50 !important;
        background: inherit !important;
        font-weight: 500 !important;
    }

    .produto-cell {
        text-align: left !important;
        font-weight: 600 !important;
    }

    .categoria-cell {
        text-align: center !important;
    }

    .numero-cell {
        font-family: 'Courier New', monospace !important;
        font-weight: 600 !important;
        text-align: center !important;
    }

    /* BADGES */
    .categoria-badge {
        background: #e3f2fd !important;
        color: #1976d2 !important;
        padding: 0.35rem 0.75rem !important;
        border-radius: 20px !important;
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        border: none !important;
    }

    /* LINHAS COM DESTAQUE */
    .linha-com-perda {
        background: #fff5f5 !important;
        border-left: 4px solid #e74c3c !important;
    }

    .linha-com-perda:hover {
        background: #ffeaea !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 8px rgba(231, 76, 60, 0.2) !important;
    }

    .linha-sem-perda {
        background: #f8fff8 !important;
        border-left: 4px solid #27ae60 !important;
    }

    .linha-sem-perda:hover {
        background: #f0fff0 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 8px rgba(39, 174, 96, 0.2) !important;
    }

    .destaque-perda {
        color: #e74c3c !important;
        font-weight: bold !important;
        background: #fff0f0 !important;
        border-radius: 4px !important;
        padding: 2px 6px !important;
    }

    /* FOOTER */
    .analise-footer {
        padding: 1.5rem !important;
        background: #f8f9fa !important;
        border-top: 1px solid #dee2e6 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 1rem !important;
    }

    .legenda-analise {
        display: flex !important;
        gap: 1.5rem !important;
        align-items: center !important;
    }

    .legenda-item {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        font-size: 0.85rem !important;
        color: #6c757d !important;
    }

    .legenda-cor {
        width: 16px !important;
        height: 16px !important;
        border-radius: 3px !important;
    }

    .legenda-cor.perda {
        background: #fff5f5 !important;
        border: 2px solid #e74c3c !important;
    }

    .legenda-cor.sem-perda {
        background: #f0fff4 !important;
        border: 2px solid #27ae60 !important;
    }

    /* ESTILOS PARA ALERTAS DE PERDA */
    .alertas-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 1rem !important;
        padding-bottom: 0.5rem !important;
        border-bottom: 2px solid #e74c3c !important;
    }

    .alertas-actions {
        display: flex !important;
        gap: 0.5rem !important;
    }

    .alertas-content {
        transition: all 0.3s ease !important;
    }

    .alerta-item {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 1rem !important;
        margin-bottom: 0.5rem !important;
        border-radius: 8px !important;
        border-left: 4px solid #e74c3c !important;
    }

    .alerta-item.perda {
        background: #fff5f5 !important;
        border-left-color: #e74c3c !important;
    }

    .alerta-item.sucesso {
        background: #f0fff4 !important;
        border-left-color: #27ae60 !important;
        justify-content: center !important;
    }

    .alerta-info {
        flex: 1 !important;
    }

    @keyframes fadeOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
    }

    /* ESTILOS PARA FILTROS DE DATA */
    .filtros-data-container .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }

    .filtros-data-container .card-header {
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
        color: white !important;
        border-bottom: none !important;
    }

    .filtros-data-container .form-control {
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }

    .filtros-data-container .form-control:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
    }

    .filtros-data-container .btn-group .btn {
        border-radius: 6px !important;
    }

    .filtros-data-container .btn-group .btn:first-child {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    .filtros-data-container .btn-group .btn:last-child {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }

    /* RESPONSIVIDADE */
    @media (max-width: 1200px) {
        .table-analise-estoque {
            font-size: 0.8rem !important;
        }
        
        .analise-thead th {
            padding: 0.75rem 0.5rem !important;
            font-size: 0.85rem !important;
        }
        
        .header-text {
            font-size: 0.85rem !important;
        }

        .alertas-header {
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        .alerta-item {
            flex-direction: column !important;
            gap: 0.5rem !important;
            text-align: center !important;
        }

        .filtros-data-container .row {
            flex-direction: column !important;
        }

        .filtros-data-container .col-md-3 {
            margin-bottom: 1rem !important;
        }

        .filtros-data-container .btn-group {
            flex-direction: row !important;
        }
    }
`;
document.head.appendChild(style);

// Funções globais para os botões HTML
function gerarRelatorio() {
    relatorios.gerarRelatorio();
}

function exportarRelatorio() {
    relatorios.exportarRelatorio();
}

function marcarPerdaVisualizada(perdaId) {
    relatorios.marcarPerdaVisualizada(perdaId);
}

function abrirHistoricoPerdas() {
    relatorios.abrirHistoricoPerdas();
}



function aplicarFiltroData() {
    relatorios.aplicarFiltroData();
}

function limparFiltroData() {
    relatorios.limparFiltroData();
}

function exportarHistoricoPerdas() {
    relatorios.exportarHistoricoPerdas();
}

async function limparPerdasDuplicadas() {
    if (!confirm('Deseja limpar as perdas duplicadas do banco de dados? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const response = await fetch('../../api/limpar_perdas_duplicadas.php');
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ ${result.message}`);
            relatorios.carregarAlertasPerda();
            // Recarregar página para atualizar tudo
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(`❌ Erro: ${result.message}`);
        }
    } catch (error) {
        alert(`❌ Erro ao limpar duplicadas: ${error.message}`);
    }
}

