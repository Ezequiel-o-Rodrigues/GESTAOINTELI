class EstoqueManager {
    constructor() {
        this.debug = true;
        this.baseUrl = '';
        this.apiUrl = '../../api';
        this.modalInitialized = false;
        
        // Log para debug
        console.log('[EstoqueManager] Inicializado com apiUrl:', this.apiUrl);
    }

    init() {
        this.log('EstoqueManager inicializado');
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
        console.error(`[Estoque] ${message}`, error || '');
    }

    initializeModals() {
        this.log('Inicializando modais');
        this.modalInitialized = true;
    }

    setupEventListeners() {
        this.log('Configurando event listeners');
        
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
    }

    showEntryModal(productId = null) {
        this.log('Abrindo modal de entrada', { productId });
        
        try {
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

            form.reset();
            this.renderProductOptions([]);
            delete form.dataset.originalCategory;
            const productIdField = document.getElementById('productId');
            if (productIdField) {
                productIdField.value = productId || '';
            }

            if (productId) {
                this.setModalTitle(title, 'Editar produto', 'bi-pencil-square');
                this.loadProductData(productId);
            } else {
                this.setModalTitle(title, 'Novo produto', 'bi-box-seam');
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

    async loadProductData(productId) {
        this.log('Carregando dados do produto', { productId });
        
        try {
            const url = `${this.apiUrl}/produto_info.php?id=${productId}`;
            console.log('[EstoqueManager] URL para carregar produto:', url);
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const product = await response.json();
            this.log('Dados do produto carregados', product);

            this.setFormValue('productId', product.id);
            this.setFormValue('productName', product.nome);
            this.setFormValue('productCategory', product.categoria_id);
            const productForm = document.getElementById('productForm');
            if (productForm) {
                productForm.dataset.originalCategory = String(product.categoria_id);
            }
            this.setFormValue('productPrice', product.preco);
            this.setFormValue('productMinStock', product.estoque_minimo);
            this.setFormValue('productInitialStock', product.estoque_atual || 0);
            if (document.getElementById('productPublicName')) {
                this.setFormValue('productPublicName', product.nome_publico);
                this.setFormValue('productPublicDescription', product.descricao_publica);
            }
            this.renderProductOptions(product.opcoes || []);

        } catch (error) {
            this.error('Erro ao carregar produto', error);
            this.showAlert('Erro ao carregar dados do produto', 'error');
        }
    }

    async loadProductForEntry(productId) {
        this.log('Carregando produto para entrada', { productId });
        
        try {
            const url = `${this.apiUrl}/produto_info_simple.php?id=${productId}`;
            this.log('URL da requisição:', url);
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status} - ${response.statusText}`);
            }
            
            const result = await response.json();
            this.log('Resposta da API:', result);

            // Usar os dados retornados, mesmo se success for false
            this.setFormValue('entryProductId', result.id || productId);
            this.setTextContent('entryProductName', result.nome || `Produto ID: ${productId}`);
            
            if (!result.success) {
                this.log('Produto não encontrado, mas modal será aberto:', result.message);
            }

        } catch (error) {
            this.error('Erro ao carregar produto para entrada', error);
            
            // Fallback: mesmo com erro, permitir que o modal abra com ID do produto
            this.setFormValue('entryProductId', productId);
            this.setTextContent('entryProductName', `Produto ID: ${productId}`);
            
            // Não mostrar alerta de erro, apenas log
            this.log('Usando fallback para produto:', productId);
        }
    }

    setFormValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.value = value || '';
            this.log(`Campo ${elementId} preenchido com: ${value}`);
        } else {
            this.error(`Elemento não encontrado: ${elementId}`);
        }
    }

    setTextContent(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = text || '';
            this.log(`Texto ${elementId} definido como: ${text}`);
        } else {
            this.error(`Elemento não encontrado: ${elementId}`);
        }
    }

    setModalTitle(element, text, iconClass) {
        const icon = document.createElement('i');
        icon.className = `bi ${iconClass}`;
        icon.setAttribute('aria-hidden', 'true');
        element.replaceChildren(icon, document.createTextNode(` ${text}`));
    }

    async saveProduct() {
        this.log('Salvando produto...');
        
        try {
            const formData = new FormData();
            const productId = document.getElementById('productId')?.value || '';
            const productName = document.getElementById('productName')?.value || '';
            const productCategory = document.getElementById('productCategory')?.value || '';
            if (!productName || !productCategory) {
                throw new Error('Preencha todos os campos obrigatórios');
            }
            const productForm = document.getElementById('productForm');
            if (
                productId
                && productForm?.dataset.originalCategory
                && productCategory !== productForm.dataset.originalCategory
                && !confirm('A categoria é compartilhada e reclassificar altera o agrupamento das vendas históricas. Faça backup antes de continuar. Deseja prosseguir?')
            ) {
                return;
            }

            formData.append('id', productId);
            formData.append('nome', productName);
            formData.append('categoria_id', productCategory);
            formData.append('preco', document.getElementById('productPrice')?.value || '');
            formData.append('estoque_minimo', document.getElementById('productMinStock')?.value || '');
            formData.append('estoque_inicial', document.getElementById('productInitialStock')?.value || '0');
            formData.append('csrf_token', this.csrfToken || '');

            const publicName = document.getElementById('productPublicName');
            if (publicName) {
                formData.append('nome_publico', publicName.value);
                formData.append('descricao_publica', document.getElementById('productPublicDescription')?.value || '');
                formData.append('opcoes_json', JSON.stringify(this.getProductOptions()));
                const image = document.getElementById('productImage')?.files?.[0];
                if (image) {
                    formData.append('imagem', image);
                }
            }

            const button = document.querySelector('#productModal .btn-primary');
            if (!button) {
                throw new Error('Botão de salvar não encontrado');
            }

            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span> Salvando...';
            button.disabled = true;

            const url = `${this.apiUrl}/salvar_produto.php`;
            console.log('[EstoqueManager] URL para salvar produto:', url);

            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            this.log('Resposta da API', { status: response.status, url: response.url });

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
                button.innerHTML = '<i class="bi bi-check2-circle" aria-hidden="true"></i> Salvar Produto';
                button.disabled = false;
            }
        }
    }

    renderProductOptions(options) {
        const container = document.getElementById('productOptions');
        if (!container) return;

        container.replaceChildren();
        options.forEach((option) => this.addProductOption(option));
    }

    addProductOption(option = {}) {
        const container = document.getElementById('productOptions');
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'product-option border rounded p-2 mb-2';
        row.dataset.cardId = option.id || '';

        const nameLabel = document.createElement('label');
        nameLabel.className = 'form-label';
        nameLabel.textContent = 'Nome da opção';
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.className = 'form-control option-name';
        nameInput.maxLength = 100;
        nameInput.required = true;
        nameInput.value = option.nome_exibicao || '';
        nameLabel.append(nameInput);

        const descriptionLabel = document.createElement('label');
        descriptionLabel.className = 'form-label mt-2';
        descriptionLabel.textContent = 'Descrição da opção (opcional)';
        const descriptionInput = document.createElement('input');
        descriptionInput.type = 'text';
        descriptionInput.className = 'form-control option-description';
        descriptionInput.maxLength = 1000;
        descriptionInput.value = option.descricao || '';
        descriptionLabel.append(descriptionInput);

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-outline-danger btn-sm mt-2';
        const removeIcon = document.createElement('i');
        removeIcon.className = 'bi bi-trash';
        removeIcon.setAttribute('aria-hidden', 'true');
        removeButton.append(removeIcon, document.createTextNode(' Remover opção'));
        removeButton.addEventListener('click', () => row.remove());

        row.append(nameLabel, descriptionLabel, removeButton);
        container.append(row);
    }

    getProductOptions() {
        return Array.from(document.querySelectorAll('#productOptions .product-option')).map((row) => ({
            id: row.dataset.cardId || null,
            nome_exibicao: row.querySelector('.option-name').value,
            descricao: row.querySelector('.option-description').value
        }));
    }

    async registerEntry() {
        this.log('Registrando entrada...');
        
        try {
            const produtoIdElement = document.getElementById('entryProductId');
            const quantidadeElement = document.getElementById('entryQuantity');
            const fornecedorElement = document.getElementById('entrySupplier');
            const observacaoElement = document.getElementById('entryNotes');
            
            const formData = {
                produto_id: produtoIdElement?.value || '',
                quantidade: quantidadeElement?.value || '',
                fornecedor_id: fornecedorElement?.value || null,
                observacao: observacaoElement?.value || ''
            };

            this.log('Dados da entrada', formData);

            // Validações mais específicas
            if (!formData.produto_id || formData.produto_id === '') {
                throw new Error('Selecione um produto');
            }
            
            if (!formData.quantidade || formData.quantidade === '' || isNaN(formData.quantidade)) {
                throw new Error('Digite uma quantidade válida');
            }
            
            const quantidade = parseInt(formData.quantidade);
            if (quantidade <= 0) {
                throw new Error('A quantidade deve ser maior que zero');
            }
            
            // Atualizar formData com quantidade convertida
            formData.quantidade = quantidade;

            const button = document.querySelector('#entryModal .btn-primary');
            if (!button) {
                throw new Error('Botão de registrar não encontrado');
            }

            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span> Registrando...';
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
                button.innerHTML = '<i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Registrar Entrada';
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

        const appearance = {
            success: { icon: 'bi-check-circle', title: 'Concluído' },
            error: { icon: 'bi-exclamation-triangle', title: 'Não foi possível concluir' },
            warning: { icon: 'bi-exclamation-circle', title: 'Atenção' },
            info: { icon: 'bi-info-circle', title: 'Informação' }
        }[type] || { icon: 'bi-info-circle', title: 'Informação' };

        let container = document.getElementById('app-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'app-toast-container';
            container.className = 'app-toast-container';
            container.setAttribute('aria-label', 'Notificações');
            document.body.append(container);
        }

        const toast = document.createElement('div');
        toast.className = `app-toast app-toast--${type}`;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

        const icon = document.createElement('i');
        icon.className = `bi ${appearance.icon} app-toast-icon`;
        icon.setAttribute('aria-hidden', 'true');

        const content = document.createElement('div');
        content.className = 'app-toast-content';
        const title = document.createElement('strong');
        title.textContent = appearance.title;
        const text = document.createElement('span');
        text.textContent = message;
        content.append(title, text);

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'app-toast-close';
        closeButton.setAttribute('aria-label', 'Fechar notificação');
        const closeIcon = document.createElement('i');
        closeIcon.className = 'bi bi-x-lg';
        closeIcon.setAttribute('aria-hidden', 'true');
        closeButton.append(closeIcon);
        closeButton.addEventListener('click', () => toast.remove());

        toast.append(icon, content, closeButton);
        container.append(toast);
        window.setTimeout(() => toast.remove(), type === 'error' ? 8000 : 5000);
    }

    showInventoryModal(productId = null) {
        if (productId) {
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

    triggerEvent(element, eventName) {
        const event = new Event(eventName, { bubbles: true });
        element.dispatchEvent(event);
    }
}

function abrirModalInventarioProduto(produtoId) {
    if (window.estoqueManager) {
        window.estoqueManager.showInventoryModal(produtoId);
    }
}

window.estoqueManager = new EstoqueManager();