(() => {
    const config = window.garcomConfig;
    const message = document.getElementById('waiterMessage');
    const ordersList = document.getElementById('ordersList');
    const ordersCount = document.getElementById('ordersCount');
    const activeOrder = document.getElementById('activeOrder');
    const activeOrderTitle = document.getElementById('activeOrderTitle');
    const activeOrderDetail = document.getElementById('activeOrderDetail');
    const orderItems = document.getElementById('orderItems');
    const orderTotal = document.getElementById('orderTotal');
    const newOrderForm = document.getElementById('newOrderForm');
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
        ordersList.removeAttribute('role');
        const term = searchOrders.value.trim().toLocaleLowerCase();
        const matchingOrders = orders.filter((order) =>
            `${order.cliente_nome || ''} ${order.mesa_numero || ''} ${order.id}`.toLocaleLowerCase().includes(term)
        );
        ordersList.replaceChildren();
        if (matchingOrders.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'waiter-empty';
            empty.textContent = orders.length
                ? 'Nenhuma comanda corresponde à pesquisa.'
                : 'Você ainda não tem comandas abertas.';
            ordersList.appendChild(empty);
            return;
        }

        matchingOrders.forEach((order) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `waiter-order-button${selectedOrder && Number(selectedOrder.id) === Number(order.id) ? ' is-selected' : ''}`;
            button.dataset.orderId = String(order.id);

            const title = document.createElement('span');
            title.className = 'waiter-order-title';
            title.textContent = orderLabel(order);
            const number = document.createElement('span');
            number.className = 'waiter-order-meta';
            number.textContent = `Comanda #${order.id}`;
            const total = document.createElement('span');
            total.className = 'waiter-order-total';
            total.textContent = formatCurrency(order.valor_total);

            button.append(title, number, total);
            ordersList.appendChild(button);
        });
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
            quantity.setAttribute('aria-label', `Quantidade de ${item.nome_produto}`);
            const price = document.createElement('span');
            price.className = 'waiter-item-price';
            price.textContent = formatCurrency(item.subtotal);

            const actions = document.createElement('div');
            actions.className = 'waiter-item-actions';
            const save = document.createElement('button');
            save.type = 'button';
            save.className = 'waiter-icon-button';
            save.title = 'Salvar quantidade';
            save.setAttribute('aria-label', `Salvar quantidade de ${item.nome_produto}`);
            save.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i>';
            save.addEventListener('click', () => updateQuantity(item.id, quantity.value));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'waiter-icon-button waiter-icon-button--remove';
            remove.title = 'Remover item';
            remove.setAttribute('aria-label', `Remover ${item.nome_produto}`);
            remove.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
            remove.addEventListener('click', () => removeItem(item.id));

            actions.append(save, remove);
            row.append(name, quantity, price, actions);
            orderItems.appendChild(row);
        });
        orderTotal.textContent = formatCurrency(total);
    }

    function filterProducts() {
        const term = productSearch.value.trim().toLocaleLowerCase();
        const category = productCategory.value;
        const cards = Array.from(document.querySelectorAll('[data-product-card]'));
        let visibleCount = 0;

        cards.forEach((card) => {
            const matchesName = card.dataset.productName.toLocaleLowerCase().includes(term);
            const matchesCategory = !category || card.dataset.productCategory === category;
            card.hidden = !matchesName || !matchesCategory;
            if (!card.hidden) visibleCount++;
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
                showMessage('A comanda selecionada não está mais aberta.', false);
            } else {
                updateActiveOrderHeading(selectedOrder);
            }
        }
        renderOrders();
    }

    function updateActiveOrderHeading(order) {
        activeOrderTitle.textContent = orderLabel(order);
        activeOrderDetail.textContent = `Comanda #${order.id}`;
    }

    async function selectOrder(order) {
        selectedOrder = order;
        updateActiveOrderHeading(order);
        showMessage('', false);
        renderOrders();
        const data = await request(`${config.itemsUrl}?comanda_id=${encodeURIComponent(order.id)}`);
        renderItems(data.itens);
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

    async function updateQuantity(itemId, value) {
        const quantity = Number(value);
        if (!Number.isInteger(quantity) || quantity < 1) {
            showMessage('Informe uma quantidade inteira maior que zero.', true);
            return;
        }
        if (!selectedOrder || isLoading) return;
        isLoading = true;
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
        if (!clienteNome && !mesaNumero) {
            showMessage('Informe o nome do cliente ou o número da mesa.', true);
            return;
        }

        const submitButton = newOrderForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        try {
            const data = await request(config.ordersUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_nome: clienteNome, mesa_numero: mesaNumero })
            });
            newOrderForm.reset();
            await loadOrders(data.comanda_id);
            showMessage(data.message, false);
            activeOrder.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            submitButton.disabled = false;
        }
    });

    ordersList.addEventListener('click', async (event) => {
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
    });

    toggleOrders.addEventListener('click', () =>
        setOrdersSidebarOpen(ordersSidebar.getAttribute('aria-hidden') === 'true')
    );
    closeOrders.addEventListener('click', () => setOrdersSidebarOpen(false));
    ordersBackdrop.addEventListener('click', () => setOrdersSidebarOpen(false));
    searchOrders.addEventListener('input', renderOrders);
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
})();
