(() => {
    const config = window.garcomConfig;
    const message = document.getElementById('waiterMessage');
    const ordersList = document.getElementById('ordersList');
    const dashboardOrders = document.getElementById('dashboardOrders');
    const ordersDashboard = document.getElementById('ordersDashboard');
    const orderDetail = document.getElementById('orderDetail');
    const ordersCount = document.getElementById('ordersCount');
    const activeOrderTitle = document.getElementById('activeOrderTitle');
    const activeOrderDetail = document.getElementById('activeOrderDetail');
    const orderItems = document.getElementById('orderItems');
    const orderTotal = document.getElementById('orderTotal');
    const newOrderForm = document.getElementById('newOrderForm');
    const createOrderError = document.getElementById('createOrderError');
    const createOrderDialog = document.getElementById('createOrderDialog');
    const openCreateOrder = document.getElementById('openCreateOrder');
    const closeCreateOrder = document.getElementById('closeCreateOrder');
    const cancelCreateOrder = document.getElementById('cancelCreateOrder');
    const backToOrders = document.getElementById('backToOrders');
    const productSearch = document.getElementById('productSearch');
    const productCategory = document.getElementById('productCategory');
    const productsCount = document.getElementById('productsCount');
    const productsEmpty = document.getElementById('productsEmpty');
    const ordersSidebar = document.getElementById('ordersSidebar');
    const ordersBackdrop = document.getElementById('ordersBackdrop');
    const toggleOrders = document.getElementById('toggleOrders');
    const closeOrders = document.getElementById('closeOrders');
    const searchOrders = document.getElementById('searchOrders');
    let orders = [];
    let selectedOrder = null;
    let isLoading = false;
    const quantityTimers = new Map();

    const formatCurrency = (value) => `R$ ${Number(value || 0).toFixed(2).replace('.', ',')}`;

    function showMessage(text, isError) {
        message.textContent = text;
        message.classList.toggle('is-error', Boolean(isError));
    }

    async function request(url, options) {
        const response = await fetch(url, options);
        const data = await response.json();
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'Não foi possível concluir a operação.');
        }
        return data;
    }

    function orderLabel(order) {
        const details = [];
        if (order.cliente_nome) details.push(order.cliente_nome);
        if (order.mesa_numero) details.push(`Mesa ${order.mesa_numero}`);
        return details.length ? details.join(' · ') : `Comanda #${order.id}`;
    }

    function renderOrders() {
        ordersCount.textContent = String(orders.length);
        const term = searchOrders.value.trim().toLocaleLowerCase();
        const matchingOrders = orders.filter((order) =>
            `${order.cliente_nome || ''} ${order.mesa_numero || ''} ${order.id}`.toLocaleLowerCase().includes(term)
        );
        dashboardOrders.replaceChildren();
        ordersList.replaceChildren();
        if (orders.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'waiter-empty';
            empty.textContent = 'Não há comandas abertas neste momento.';
            dashboardOrders.appendChild(empty);
            ordersList.appendChild(empty.cloneNode(true));
        }

        orders.forEach((order) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `waiter-order-button${selectedOrder && Number(selectedOrder.id) === Number(order.id) ? ' is-selected' : ''}`;
            button.dataset.orderId = String(order.id);

            const title = document.createElement('span');
            title.className = 'waiter-order-title';
            title.textContent = order.cliente_nome || `Comanda #${order.id}`;
            const number = document.createElement('span');
            number.className = 'waiter-order-meta';
            const details = order.mesa_numero
                ? `Mesa ${order.mesa_numero} · Comanda #${order.id}`
                : `Comanda #${order.id}`;
            number.textContent = order.garcom_nome
                ? `${details} · Aberta por ${order.garcom_nome}`
                : details;
            const total = document.createElement('span');
            total.className = 'waiter-order-total';
            total.textContent = formatCurrency(order.valor_total);

            button.append(title, number, total);
            dashboardOrders.appendChild(button);

            const sidebarButton = button.cloneNode(true);
            sidebarButton.classList.add('waiter-order-button--sidebar');
            if (matchingOrders.includes(order)) ordersList.appendChild(sidebarButton);
        });

        if (orders.length > 0 && matchingOrders.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'waiter-empty';
            empty.textContent = 'Nenhuma comanda corresponde à pesquisa.';
            ordersList.appendChild(empty);
        }
    }

    function showDashboard() {
        selectedOrder = null;
        ordersDashboard.hidden = false;
        orderDetail.hidden = true;
        renderOrders();
    }

    function showOrderDetail() {
        ordersDashboard.hidden = true;
        orderDetail.hidden = false;
    }

    function setOrdersSidebarOpen(isOpen) {
        ordersSidebar.classList.toggle('is-open', isOpen);
        ordersSidebar.setAttribute('aria-hidden', String(!isOpen));
        ordersSidebar.inert = !isOpen;
        toggleOrders.setAttribute('aria-expanded', String(isOpen));
        ordersBackdrop.hidden = !isOpen;
        if (isOpen) {
            searchOrders.focus();
            loadOrders().catch((error) => showMessage(error.message, true));
        } else {
            toggleOrders.focus();
        }
    }

    function renderItems(items) {
        quantityTimers.forEach((timer) => window.clearTimeout(timer));
        quantityTimers.clear();
        orderItems.replaceChildren();
        if (items.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'waiter-empty';
            empty.textContent = 'Nenhum item adicionado.';
            orderItems.appendChild(empty);
        }

        let total = 0;
        items.forEach((item) => {
            total += Number(item.subtotal || 0);
            const row = document.createElement('div');
            row.className = 'waiter-item';

            const name = document.createElement('span');
            name.className = 'waiter-item-name';
            name.textContent = item.nome_produto;
            const quantity = document.createElement('input');
            quantity.className = 'waiter-quantity';
            quantity.type = 'number';
            quantity.min = '1';
            quantity.step = '1';
            quantity.value = String(item.quantidade);
            quantity.dataset.savedValue = String(item.quantidade);
            quantity.setAttribute('aria-label', `Quantidade de ${item.nome_produto}`);
            quantity.addEventListener('input', () => scheduleQuantitySave(item.id, quantity));
            quantity.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    clearQuantityTimer(item.id);
                    updateQuantity(item.id, quantity, true);
                }
            });
            quantity.addEventListener('blur', () => {
                clearQuantityTimer(item.id);
                updateQuantity(item.id, quantity, true);
            });
            const quantityControl = document.createElement('label');
            quantityControl.className = 'waiter-quantity-control';
            const quantityLabel = document.createElement('span');
            quantityLabel.className = 'waiter-quantity-label';
            quantityLabel.textContent = 'Qtd.';
            quantityControl.append(quantityLabel, quantity);
            const price = document.createElement('span');
            price.className = 'waiter-item-price';
            price.textContent = formatCurrency(item.subtotal);

            const actions = document.createElement('div');
            actions.className = 'waiter-item-actions';
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'waiter-icon-button waiter-icon-button--remove';
            remove.title = 'Remover item';
            remove.setAttribute('aria-label', `Remover ${item.nome_produto}`);
            remove.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
            remove.addEventListener('click', () => removeItem(item.id));

            actions.append(remove);
            row.append(name, quantityControl, price, actions);
            orderItems.appendChild(row);
        });
        orderTotal.textContent = formatCurrency(total);
    }

    function clearQuantityTimer(itemId) {
        const timer = quantityTimers.get(itemId);
        if (timer) window.clearTimeout(timer);
        quantityTimers.delete(itemId);
    }

    function scheduleQuantitySave(itemId, input, delay = 500) {
        clearQuantityTimer(itemId);
        const timer = window.setTimeout(() => {
            quantityTimers.delete(itemId);
            updateQuantity(itemId, input);
        }, delay);
        quantityTimers.set(itemId, timer);
    }

    function filterProducts() {
        const term = productSearch.value.trim().toLocaleLowerCase();
        const category = productCategory.value;
        const cards = Array.from(document.querySelectorAll('[data-product-card]'));
        const groups = Array.from(document.querySelectorAll('[data-category-group]'));
        let visibleCount = 0;

        cards.forEach((card) => {
            const matchesName = card.dataset.productName.toLocaleLowerCase().includes(term);
            const matchesCategory = !category || card.dataset.productCategory === category;
            card.hidden = !matchesName || !matchesCategory;
            if (!card.hidden) visibleCount++;
        });

        groups.forEach((group) => {
            const hasVisibleProducts = group.querySelector('[data-product-card]:not([hidden])') !== null;
            group.hidden = !hasVisibleProducts;
        });

        productsCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'produto' : 'produtos'}`;
        if (productsEmpty) productsEmpty.hidden = visibleCount > 0;
    }

    async function loadOrders(selectId) {
        const data = await request(config.ordersUrl);
        orders = data.comandas;
        renderOrders();
        if (selectId) {
            const order = orders.find((item) => Number(item.id) === Number(selectId));
            if (order) await selectOrder(order);
        } else if (selectedOrder) {
            selectedOrder = orders.find((item) => Number(item.id) === Number(selectedOrder.id)) || null;
            if (!selectedOrder) {
                renderItems([]);
                activeOrderTitle.textContent = 'Comanda finalizada';
                activeOrderDetail.textContent = 'Selecione outra comanda ou abra um novo atendimento.';
                ordersDashboard.hidden = false;
                orderDetail.hidden = true;
                showMessage('A comanda selecionada não está mais aberta.', false);
            } else {
                updateActiveOrderHeading(selectedOrder);
                if (!orderDetail.hidden) {
                    const selectedId = Number(selectedOrder.id);
                    const itemsData = await request(
                        `${config.itemsUrl}?comanda_id=${encodeURIComponent(selectedId)}`
                    );
                    if (selectedOrder && Number(selectedOrder.id) === selectedId) {
                        renderItems(itemsData.itens);
                    }
                }
            }
        } else {
            ordersDashboard.hidden = false;
            orderDetail.hidden = true;
        }
        renderOrders();
    }

    function updateActiveOrderHeading(order) {
        activeOrderTitle.textContent = orderLabel(order);
        activeOrderDetail.textContent = `Comanda #${order.id}`;
    }

    async function selectOrder(order) {
        const data = await request(`${config.itemsUrl}?comanda_id=${encodeURIComponent(order.id)}`);
        selectedOrder = order;
        updateActiveOrderHeading(order);
        renderItems(data.itens);
        showOrderDetail();
        showMessage('', false);
        renderOrders();
    }

    async function refreshSelectedOrder() {
        if (!selectedOrder) return;
        const data = await request(`${config.itemsUrl}?comanda_id=${encodeURIComponent(selectedOrder.id)}`);
        renderItems(data.itens);
        await loadOrders();
    }

    async function addItem(productId) {
        if (!selectedOrder) {
            showMessage('Abra uma comanda ou selecione uma na lista antes de adicionar produtos.', true);
            return;
        }
        if (isLoading) return;
        isLoading = true;
        try {
            await request(config.itemsUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comanda_id: selectedOrder.id, produto_id: productId, quantidade: 1 })
            });
            await refreshSelectedOrder();
            showMessage('Produto adicionado à comanda.', false);
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            isLoading = false;
        }
    }

    async function updateQuantity(itemId, input, reportInvalid = false) {
        if (!input.isConnected) return;
        if (input.value === '') {
            if (reportInvalid) {
                showMessage('Informe uma quantidade inteira maior que zero.', true);
                input.value = input.dataset.savedValue;
            }
            return;
        }
        const quantity = Number(input.value);
        if (!Number.isInteger(quantity) || quantity < 1) {
            showMessage('Informe uma quantidade inteira maior que zero.', true);
            return;
        }
        if (quantity === Number(input.dataset.savedValue)) return;
        if (!selectedOrder) return;
        if (isLoading) {
            scheduleQuantitySave(itemId, input, 250);
            return;
        }
        isLoading = true;
        input.disabled = true;
        try {
            await request(config.itemsUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    comanda_id: selectedOrder.id,
                    item_id: itemId,
                    nova_quantidade: quantity
                })
            });
            await refreshSelectedOrder();
            showMessage('Quantidade atualizada.', false);
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            isLoading = false;
            if (input.isConnected) input.disabled = false;
        }
    }

    async function removeItem(itemId) {
        if (!selectedOrder || isLoading) return;
        isLoading = true;
        try {
            await request(config.removeItemUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comanda_id: selectedOrder.id, item_id: itemId })
            });
            await refreshSelectedOrder();
            showMessage('Item removido da comanda.', false);
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            isLoading = false;
        }
    }

    newOrderForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(newOrderForm);
        const clienteNome = String(formData.get('cliente_nome') || '').trim();
        const mesaNumero = String(formData.get('mesa_numero') || '').trim();
        createOrderError.hidden = true;
        createOrderError.textContent = '';
        if (!clienteNome && !mesaNumero) {
            createOrderError.textContent = 'Informe o nome do cliente ou o número da mesa.';
            createOrderError.hidden = false;
            return;
        }

        const submitButton = newOrderForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        let comandaCriada = false;
        try {
            const data = await request(config.ordersUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_nome: clienteNome, mesa_numero: mesaNumero })
            });
            comandaCriada = true;
            newOrderForm.reset();
            await loadOrders(data.comanda_id);
            createOrderDialog.close();
            showMessage(data.message, false);
            orderDetail.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            if (comandaCriada) {
                createOrderDialog.close();
                showDashboard();
                showMessage('A comanda foi criada, mas não foi possível atualizar a lista. Atualize a página antes de tentar abrir outra.', true);
            } else {
                createOrderError.textContent = error.message;
                createOrderError.hidden = false;
            }
        } finally {
            submitButton.disabled = false;
        }
    });

    async function handleOrderSelection(event) {
        const button = event.target.closest('[data-order-id]');
        if (!button) return;
        const order = orders.find((item) => Number(item.id) === Number(button.dataset.orderId));
        if (!order) return;
        try {
            await selectOrder(order);
            setOrdersSidebarOpen(false);
        } catch (error) {
            showMessage(error.message, true);
        }
    }

    ordersList.addEventListener('click', handleOrderSelection);
    dashboardOrders.addEventListener('click', handleOrderSelection);

    toggleOrders.addEventListener('click', () =>
        setOrdersSidebarOpen(ordersSidebar.getAttribute('aria-hidden') === 'true')
    );
    closeOrders.addEventListener('click', () => setOrdersSidebarOpen(false));
    ordersBackdrop.addEventListener('click', () => setOrdersSidebarOpen(false));
    searchOrders.addEventListener('input', renderOrders);
    openCreateOrder.addEventListener('click', () => createOrderDialog.showModal());
    closeCreateOrder.addEventListener('click', () => createOrderDialog.close());
    cancelCreateOrder.addEventListener('click', () => createOrderDialog.close());
    createOrderDialog.addEventListener('click', (event) => {
        if (event.target === createOrderDialog) createOrderDialog.close();
    });
    backToOrders.addEventListener('click', () => {
        showDashboard();
        showMessage('', false);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ordersSidebar.classList.contains('is-open')) {
            setOrdersSidebarOpen(false);
        }
    });

    document.querySelectorAll('[data-add-product]').forEach((button) => {
        button.addEventListener('click', () => addItem(Number(button.dataset.addProduct)));
    });

    productSearch.addEventListener('input', filterProducts);
    productCategory.addEventListener('change', filterProducts);

    filterProducts();
    loadOrders().catch((error) => showMessage(error.message, true));
    window.setInterval(() => {
        loadOrders().catch((error) => showMessage(error.message, true));
    }, 15000);

    // Expõe selectOrder para o módulo MapaMesas
    window._garcomSelectOrder = (order) => selectOrder(order).catch((e) => showMessage(e.message, true));
})();

// ── Mapa de Mesas ──────────────────────────────────────────────────────────
if (window.garcomConfig && window.garcomConfig.usarMapaMesas) {
    (() => {
        const cfg        = window.garcomConfig;
        const canvas     = document.getElementById('mapaCanvas');
        const multiDialog = document.getElementById('mesaMultiDialog');
        const multiLista  = document.getElementById('mesaMultiLista');
        if (!canvas) return;

        async function carregarMapa() {
            try {
                const r = await fetch(cfg.mapaMesasUrl);
                const d = await r.json();
                if (!d.success) return;
                renderMapa(d.mesas);
            } catch (e) { /* silencioso no polling */ }
        }

        function renderMapa(mesas) {
            canvas.replaceChildren();
            // Calcular dimensão mínima do canvas
            let maxX = 0, maxY = 0;
            mesas.forEach(m => {
                if (m.posicao_x + 90 > maxX) maxX = m.posicao_x + 90;
                if (m.posicao_y + 90 > maxY) maxY = m.posicao_y + 90;
            });
            canvas.style.minWidth  = Math.max(maxX, 400) + 'px';
            canvas.style.minHeight = Math.max(maxY, 400) + 'px';

            mesas.forEach(m => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `mesa-btn ${m.status_mesa}`;
                btn.style.left = m.posicao_x + 'px';
                btn.style.top  = m.posicao_y + 'px';
                btn.innerHTML  = `<span>${escapeHtmlMapa(m.numero_mesa)}</span><small>${escapeHtmlMapa(m.ambiente)}</small>`;
                btn.setAttribute('aria-label', `Mesa ${m.numero_mesa} – ${m.status_mesa}`);
                btn.addEventListener('click', () => clicarMesa(m));
                canvas.appendChild(btn);
            });
        }

        async function clicarMesa(mesa) {
            if (mesa.status_mesa === 'livre') {
                await abrirComandaMesa(mesa);
            } else {
                // Buscar comandas abertas desta mesa
                const r = await fetch(cfg.ordersUrl);
                const d = await r.json();
                if (!d.success) return;
                const abertas = d.comandas.filter(c => String(c.mesa_id) === String(mesa.mesa_id));
                if (abertas.length === 1) {
                    selecionarComandaDoMapa(abertas[0]);
                } else {
                    mostrarMultiComanda(abertas);
                }
            }
        }

        async function abrirComandaMesa(mesa) {
            try {
                const r = await fetch(cfg.novaComandaUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mesa_id: mesa.mesa_id })
                });
                const d = await r.json();
                if (!d.success) throw new Error(d.message);
                // Recarregar mapa e selecionar a comanda criada
                await carregarMapa();
                const rOrders = await fetch(cfg.ordersUrl);
                const dOrders = await rOrders.json();
                if (dOrders.success) {
                    const nova = dOrders.comandas.find(c => Number(c.id) === Number(d.comanda_id));
                    if (nova && window._garcomSelectOrder) window._garcomSelectOrder(nova);
                }
            } catch (e) {
                alert(e.message);
            }
        }

        function selecionarComandaDoMapa(comanda) {
            if (window._garcomSelectOrder) window._garcomSelectOrder(comanda);
        }

        function mostrarMultiComanda(abertas) {
            multiLista.replaceChildren();
            abertas.forEach(c => {
                const btn = document.createElement('button');
                btn.className = 'waiter-button waiter-button--secondary';
                btn.style.cssText = 'width:100%;margin-bottom:8px';
                btn.textContent = c.cliente_nome ? `${c.cliente_nome} – Comanda #${c.id}` : `Comanda #${c.id}`;
                btn.addEventListener('click', () => {
                    multiDialog.hidden = true;
                    selecionarComandaDoMapa(c);
                });
                multiLista.appendChild(btn);
            });
            multiDialog.hidden = false;
        }

        function escapeHtmlMapa(str) {
            const el = document.createElement('span');
            el.textContent = str == null ? '' : String(str);
            return el.innerHTML;
        }

        document.getElementById('btnAtualizarMapa')?.addEventListener('click', carregarMapa);

        carregarMapa();
        setInterval(carregarMapa, 15000);
    })();
}
