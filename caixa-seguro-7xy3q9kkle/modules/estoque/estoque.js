class Estoque {
    constructor() {
        // Não inicializar aqui - vamos esperar o DOM carregar
    }

    init() {
        this.configurarEventListeners();
    }

    configurarEventListeners() {
        // Configurar eventos apenas se os elementos existirem
        const formEntrada = document.getElementById('formEntrada');
        const formProduto = document.getElementById('formProduto');
        
        if (formEntrada) {
            formEntrada.addEventListener('submit', (e) => {
                e.preventDefault();
                this.registrarEntrada(this.getFormDataEntrada());
            });
        }
        
        if (formProduto) {
            formProduto.addEventListener('submit', (e) => {
                e.preventDefault();
                this.salvarProduto(this.getFormDataProduto());
            });
        }
    }

    getFormDataEntrada() {
        return {
            produto_id: document.getElementById('produto_id_entrada').value,
            quantidade: document.getElementById('quantidadeEntrada').value,
            fornecedor_id: document.getElementById('fornecedorEntrada').value || null,
            observacao: document.getElementById('observacaoEntrada').value
        };
    }

    getFormDataProduto() {
        return {
            id: document.getElementById('produtoId').value || null,
            nome: document.getElementById('nomeProduto').value,
            categoria_id: document.getElementById('categoriaProduto').value,
            preco: document.getElementById('precoProduto').value,
            estoque_minimo: document.getElementById('estoqueMinimoProduto').value,
            estoque_inicial: document.getElementById('estoqueInicialProduto').value || 0
        };
    }

    async abrirModalEntrada(produtoId) {
        try {
            const response = await fetch(`../../api/produto_info.php?id=${produtoId}`);
            const produto = await response.json();
            
            document.getElementById('produto_id_entrada').value = produto.id;
            document.getElementById('nome-produto-entrada').textContent = produto.nome;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEntrada'));
            modal.show();
        } catch (error) {
            this.mostrarToast('Erro ao carregar produto', 'error');
            console.error('Erro:', error);
        }
    }

    async registrarEntrada(formData) {
        const btn = document.querySelector('#formEntrada button[type="submit"]');
        const originalText = btn.innerHTML;
        
        try {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            btn.disabled = true;

            const response = await fetch('../../api/registrar_entrada.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.mostrarToast('Entrada registrada com sucesso!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEntrada'));
                modal.hide();
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || 'Erro ao registrar entrada');
            }
        } catch (error) {
            this.mostrarToast('Erro: ' + error.message, 'error');
            console.error('Erro:', error);
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    async salvarProduto(formData) {
        const btn = document.querySelector('#formProduto button[type="submit"]');
        const originalText = btn.innerHTML;
        
        try {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            btn.disabled = true;

            const response = await fetch('../../api/salvar_produto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.mostrarToast('Produto salvo com sucesso!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalProduto'));
                modal.hide();
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || 'Erro ao salvar produto');
            }
        } catch (error) {
            this.mostrarToast('Erro: ' + error.message, 'error');
            console.error('Erro:', error);
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    async toggleProduto(produtoId, novoStatus, button) {
        if (!confirm(`Deseja ${novoStatus ? 'ativar' : 'desativar'} este produto?`)) return;

        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        try {
            const response = await fetch('../../api/toggle_produto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    produto_id: produtoId, 
                    ativo: novoStatus 
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.mostrarToast(`Produto ${novoStatus ? 'ativado' : 'desativado'} com sucesso!`, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || 'Erro ao atualizar produto');
            }
        } catch (error) {
            this.mostrarToast('Erro: ' + error.message, 'error');
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
    }

    abrirModalProduto(produtoId = null) {
        if (produtoId) {
            this.editarProduto(produtoId);
        } else {
            document.getElementById('formProduto').reset();
            document.getElementById('produtoId').value = '';
            document.getElementById('modalProdutoLabel').textContent = 'Novo Produto';
            
            const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
            modal.show();
        }
    }

    async editarProduto(produtoId) {
        try {
            const response = await fetch(`../../api/produto_info.php?id=${produtoId}`);
            const produto = await response.json();
            
            document.getElementById('produtoId').value = produto.id;
            document.getElementById('nomeProduto').value = produto.nome;
            document.getElementById('categoriaProduto').value = produto.categoria_id;
            document.getElementById('precoProduto').value = produto.preco;
            document.getElementById('estoqueMinimoProduto').value = produto.estoque_minimo;
            
            document.getElementById('modalProdutoLabel').textContent = 'Editar Produto';
            
            const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
            modal.show();
        } catch (error) {
            this.mostrarToast('Erro ao carregar dados do produto', 'error');
            console.error('Erro:', error);
        }
    }

    mostrarToast(mensagem, tipo = 'info') {
        // Criar toast do Bootstrap
        const toastContainer = document.getElementById('toastContainer') || this.criarToastContainer();
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${tipo === 'error' ? 'danger' : tipo} border-0`;
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${this.getToastIcon(tipo)} me-2"></i>
                    ${mensagem}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Remover após esconder
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    criarToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    getToastIcon(tipo) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[tipo] || 'info-circle';
    }
}

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    window.estoque = new Estoque();
    window.estoque.init();
});

// Funções globais para os botões HTML
function abrirModalEntrada(produtoId) {
    if (window.estoque) {
        window.estoque.abrirModalEntrada(produtoId);
    }
}

function abrirModalProduto(produtoId = null) {
    if (window.estoque) {
        window.estoque.abrirModalProduto(produtoId);
    }
}

function editarProduto(produtoId) {
    if (window.estoque) {
        window.estoque.editarProduto(produtoId);
    }
}

function toggleProduto(produtoId, novoStatus, button) {
    if (window.estoque) {
        window.estoque.toggleProduto(produtoId, novoStatus, button);
    }
}