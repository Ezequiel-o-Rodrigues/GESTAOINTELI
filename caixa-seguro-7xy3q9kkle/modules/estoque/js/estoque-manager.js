class EstoqueManager {
    constructor() {
        this.debug = true;
        this.baseUrl = '';
        this.apiUrl = '../../api';
        this.modalInitialized = false;
    }

    init() {
        this.log('🚀 EstoqueManager inicializado');
        this.initializeModals();
        this.setupEventListeners();
        this.loadInitialData();
    }

    log(message, data = null) {
        if (this.debug) {
            console.log(`[Estoque] ${message}`, data || '');
        }
    }

    error(message, error = null) {
        console.error(`[Estoque] ❌ ${message}`, error || '');
    }

    initializeModals() {
        this.log('Inicializando modais');
        // Modais são inicializados pelo Bootstrap automaticamente
        this.modalInitialized = true;
    }

    setupEventListeners() {
        this.log('Configurando event listeners');
        
        // Formulários - usando event delegation para garantir funcionamento
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'entryForm') {
                e.preventDefault();
                this.registerEntry();
            }
            if (e.target.id === 'productForm') {
                e.preventDefault();
                this.saveProduct();
            }
        });
    }

    loadInitialData() {
        this.log('Carregando dados iniciais');
        // Dados já carregados via PHP
    }

    // ========== MODAIS ==========
    showEntryModal(productId = null) {
        this.log('Abrindo modal de entrada', { productId });
        
        try {
            // Resetar formulário
            const form = document.getElementById('entryForm');
            if (form) form.reset();
            
            if (productId) {
                this.loadProductForEntry(productId);
            } else {
                const productIdField = document.getElementById('entryProductId');
                const productNameField = document.getElementById('entryProductName');
                if (productIdField) productIdField.value = '';
                if (productNameField) productNameField.textContent = '';
            }
            
            const modalElement = document.getElementById('entryModal');
            if (!modalElement) {
                throw new Error('Modal de entrada não encontrado');
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
        } catch (error) {
            this.error('Erro ao abrir modal de entrada', error);
            this.showAlert('Erro ao abrir formulário de entrada', 'error');
        }
    }

    showProductModal(productId = null) {
        this.log('Abrindo modal de produto', { productId });
        
        try {
            const title = document.getElementById('productModalLabel');
            const form = document.getElementById('productForm');
            
            if (!title) {
                throw new Error('Elemento productModalLabel não encontrado');
            }
            if (!form) {
                throw new Error('Elemento productForm não encontrado');
            }
            
            if (productId) {
                title.textContent = 'Editar Produto';
                this.loadProductData(productId);
            } else {
                title.textContent = 'Novo Produto';
                form.reset();
                const productIdField = document.getElementById('productId');
                if (productIdField) {
                    productIdField.value = '';
                }
            }
            
            const modalElement = document.getElementById('productModal');
            if (!modalElement) {
                throw new Error('Modal de produto não encontrado');
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
        } catch (error) {
            this.error('Erro ao abrir modal de produto', error);
            this.showAlert('Erro ao abrir formulário de produto', 'error');
        }
    }

    // ========== OPERAÇÕES DE PRODUTO ==========
    async loadProductData(productId) {
        this.log('Carregando dados do produto', { productId });
        
        try {
            const response = await fetch(`${this.apiUrl}/produto_info.php?id=${productId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const product = await response.json();
            this.log('Dados do produto carregados', product);

            // Preencher formulário com verificações
            this.setFormValue('productId', product.id);
            this.setFormValue('productName', product.nome);
            this.setFormValue('productCategory', product.categoria_id);
            this.setFormValue('productPrice', product.preco);
            this.setFormValue('productMinStock', product.estoque_minimo);
            this.setFormValue('productInitialStock', product.estoque_atual || 0);

        } catch (error) {
            this.error('Erro ao carregar produto', error);
            this.showAlert('Erro ao carregar dados do produto', 'error');
        }
    }

    async loadProductForEntry(productId) {
        this.log('Carregando produto para entrada', { productId });
        
        try {
            const response = await fetch(`${this.apiUrl}/produto_info.php?id=${productId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const product = await response.json();
            this.log('Produto carregado para entrada', product);

            this.setFormValue('entryProductId', product.id);
            this.setTextContent('entryProductName', product.nome);

        } catch (error) {
            this.error('Erro ao carregar produto para entrada', error);
            this.showAlert('Erro ao carregar produto', 'error');
        }
    }

    // ========== UTILITÁRIOS DE FORMULÁRIO ==========
    setFormValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.value = value;
        } else {
            this.error(`Elemento não encontrado: ${elementId}`);
        }
    }

    setTextContent(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = text;
        } else {
            this.error(`Elemento não encontrado: ${elementId}`);
        }
    }

    async saveProduct() {
        this.log('Salvando produto...');
        
        try {
            const formData = {
                id: document.getElementById('productId')?.value || null,
                nome: document.getElementById('productName')?.value,
                categoria_id: document.getElementById('productCategory')?.value,
                preco: document.getElementById('productPrice')?.value,
                estoque_minimo: document.getElementById('productMinStock')?.value,
                estoque_inicial: document.getElementById('productInitialStock')?.value || 0
            };

            this.log('Dados do formulário', formData);

            // Validação básica
            if (!formData.nome || !formData.categoria_id) {
                throw new Error('Preencha todos os campos obrigatórios');
            }

            const button = document.querySelector('#productModal .btn-primary');
            if (!button) {
                throw new Error('Botão de salvar não encontrado');
            }

            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            button.disabled = true;

            const response = await fetch(`${this.apiUrl}/salvar_produto.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            this.log('Resposta da API', { status: response.status });

            const result = await response.json();
            this.log('Resultado do salvamento', result);

            if (result.success) {
                this.showAlert('Produto salvo com sucesso!', 'success');
                this.hideModal('productModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || 'Erro ao salvar produto');
            }

        } catch (error) {
            this.error('Erro ao salvar produto', error);
            this.showAlert('Erro: ' + error.message, 'error');
        } finally {
            const button = document.querySelector('#productModal .btn-primary');
            if (button) {
                button.innerHTML = 'Salvar Produto';
                button.disabled = false;
            }
        }
    }

    async registerEntry() {
        this.log('Registrando entrada...');
        
        try {
            const formData = {
                produto_id: document.getElementById('entryProductId')?.value,
                quantidade: document.getElementById('entryQuantity')?.value,
                fornecedor_id: document.getElementById('entrySupplier')?.value || null,
                observacao: document.getElementById('entryNotes')?.value
            };

            this.log('Dados da entrada', formData);

            if (!formData.produto_id || !formData.quantidade || formData.quantidade <= 0) {
                throw new Error('Preencha a quantidade corretamente');
            }

            const button = document.querySelector('#entryModal .btn-primary');
            if (!button) {
                throw new Error('Botão de registrar não encontrado');
            }

            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            button.disabled = true;

            const response = await fetch(`${this.apiUrl}/registrar_entrada.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            this.log('Resposta da API', { status: response.status });

            const result = await response.json();
            this.log('Resultado do registro', result);

            if (result.success) {
                this.showAlert('Entrada registrada com sucesso!', 'success');
                this.hideModal('entryModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || 'Erro ao registrar entrada');
            }

        } catch (error) {
            this.error('Erro ao registrar entrada', error);
            this.showAlert('Erro: ' + error.message, 'error');
        } finally {
            const button = document.querySelector('#entryModal .btn-primary');
            if (button) {
                button.innerHTML = 'Registrar Entrada';
                button.disabled = false;
            }
        }
    }

    async toggleProduct(productId, newStatus) {
        const action = newStatus ? 'ativar' : 'desativar';
        this.log(`${action} produto`, { productId, newStatus });
        
        if (!confirm(`Deseja ${action} este produto?`)) {
            this.log('Ação cancelada pelo usuário');
            return;
        }

        try {
            const response = await fetch(`${this.apiUrl}/toggle_produto.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    produto_id: productId,
                    ativo: newStatus
                })
            });

            this.log('Resposta da API', { status: response.status });

            const result = await response.json();
            this.log('Resultado do toggle', result);

            if (result.success) {
                this.showAlert(`Produto ${action}do com sucesso!`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(result.message || `Erro ao ${action} produto`);
            }

        } catch (error) {
            this.error(`Erro ao ${action} produto`, error);
            this.showAlert('Erro: ' + error.message, 'error');
        }
    }

    // ========== FILTROS ==========
    filterProducts() {
        const categorySelects = document.querySelectorAll('[onchange="estoqueManager.filterProducts()"]');
        const searchInput = document.querySelector('[onkeyup="estoqueManager.filterProducts()"]');
        
        const category = categorySelects[0]?.value || 'all';
        const status = categorySelects[1]?.value || 'all';
        const search = (searchInput?.value || '').toLowerCase();

        this.log('Filtrando produtos', { category, status, search });

        document.querySelectorAll('.product-row').forEach(row => {
            const rowCategory = row.getAttribute('data-category');
            const rowStatus = row.getAttribute('data-status');
            const rowName = row.getAttribute('data-name');

            const categoryMatch = category === 'all' || rowCategory === category;
            const statusMatch = status === 'all' || rowStatus === status;
            const searchMatch = rowName.includes(search);

            if (categoryMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // ========== UTILITÁRIOS ==========
    editProduct(productId) {
        this.log('Editando produto', { productId });
        this.showProductModal(productId);
    }

    hideModal(modalId) {
        const modalElement = document.getElementById(modalId);
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }

    showAlert(message, type = 'info') {
        this.log(`Exibindo alerta: ${message}`, { type });
        
        // Usar alert simples por enquanto
        if (type === 'error') {
            alert('❌ ' + message);
        } else if (type === 'success') {
            alert('✅ ' + message);
        } else {
            alert('ℹ️ ' + message);
        }
    }

       // NOVA FUNÇÃO: Abrir modal de inventário para produto específico
    showInventoryModal(productId = null) {
        if (productId) {
            // Preencher automaticamente o produto no modal de inventário
            const produtoSelect = document.getElementById('produto_id');
            if (produtoSelect) {
                produtoSelect.value = productId;
                this.triggerEvent(produtoSelect, 'change');
            }
        }
        
        const modalElement = document.getElementById('inventarioModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

    // Função utilitária para trigger de eventos
    triggerEvent(element, eventName) {
        const event = new Event(eventName, { bubbles: true });
        element.dispatchEvent(event);
    }

}

// Adicionar função global para inventário
function abrirModalInventarioProduto(produtoId) {
    if (window.estoqueManager) {
        window.estoqueManager.showInventoryModal(produtoId);
    }
}

// Instância global
const estoqueManager = new EstoqueManager();