(() => {
    const config = window.appConfig;
    const panel = document.getElementById('open-orders-panel');
    const backdrop = document.getElementById('open-orders-backdrop');
    const toggleButton = document.getElementById('toggle-open-orders');
    const closeButton = document.getElementById('close-open-orders');
    const searchInput = document.getElementById('search-open-orders');
    const list = document.getElementById('open-orders-list');
    if (!config || !panel || !backdrop || !toggleButton || !closeButton || !searchInput || !list) return;

    let orders = [];

    function setOpen(isOpen) {
        panel.classList.toggle('is-open', isOpen);
        panel.setAttribute('aria-hidden', String(!isOpen));
        panel.inert = !isOpen;
        toggleButton.setAttribute('aria-expanded', String(isOpen));
        backdrop.hidden = !isOpen;
        if (isOpen) {
            searchInput.focus();
            loadOrders();
        } else {
            toggleButton.focus();
        }
    }

    function label(order) {
        const parts = [];
        if (order.cliente_nome) parts.push(order.cliente_nome);
        if (order.mesa_numero) parts.push(`Mesa ${order.mesa_numero}`);
        return parts.length ? parts.join(' · ') : `Comanda #${order.id}`;
    }

    function renderOrders() {
        list.removeAttribute('role');
        const term = searchInput.value.trim().toLocaleLowerCase();
        const filtered = orders.filter((order) => {
            const searchText = `${order.cliente_nome || ''} ${order.mesa_numero || ''} ${order.id}`.toLocaleLowerCase();
            return searchText.includes(term);
        });

        list.replaceChildren();
        if (filtered.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'open-orders-empty';
            empty.textContent = orders.length ? 'Nenhuma comanda corresponde à pesquisa.' : 'Não há comandas abertas.';
            list.appendChild(empty);
            return;
        }

        filtered.forEach((order) => {
            const button = document.createElement('button');
            button.type = 'button';
            const currentId = window.caixaSystem && window.caixaSystem.comandaAtual
                ? Number(window.caixaSystem.comandaAtual.id)
                : 0;
            button.className = `open-order-card${currentId === Number(order.id) ? ' is-selected' : ''}`;
            button.dataset.orderId = String(order.id);

            const title = document.createElement('strong');
            title.textContent = label(order);
            const number = document.createElement('span');
            number.className = 'open-order-meta';
            const details = [`Comanda #${order.id}`];
            if (order.garcom_nome) details.push(`Garçom: ${order.garcom_nome}`);
            number.textContent = details.join(' · ');
            const total = document.createElement('span');
            total.className = 'open-order-total';
            total.textContent = `R$ ${Number(order.valor_total || 0).toFixed(2).replace('.', ',')}`;

            button.append(title, number, total);
            list.appendChild(button);
        });
    }

    async function loadOrders() {
        try {
            const response = await fetch(config.openOrdersUrl);
            const data = await response.json();
            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'Não foi possível carregar as comandas abertas.');
            }
            orders = data.comandas;
            renderOrders();
        } catch (error) {
            list.replaceChildren();
            const alert = document.createElement('p');
            alert.className = 'open-orders-empty';
            alert.setAttribute('role', 'alert');
            alert.textContent = error.message;
            list.appendChild(alert);
        }
    }

    toggleButton.addEventListener('click', () => setOpen(panel.getAttribute('aria-hidden') === 'true'));
    closeButton.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));
    searchInput.addEventListener('input', renderOrders);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) setOpen(false);
    });

    list.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-order-id]');
        if (!button) return;
        const order = orders.find((item) => Number(item.id) === Number(button.dataset.orderId));
        if (!order) return;
        if (!window.caixaSystem || typeof window.caixaSystem.abrirComandaSelecionada !== 'function') {
            list.setAttribute('role', 'alert');
            list.textContent = 'O caixa ainda está carregando. Tente novamente.';
            return;
        }

        try {
            await window.caixaSystem.abrirComandaSelecionada(order);
            setOpen(false);
        } catch (error) {
            list.setAttribute('role', 'alert');
            list.textContent = error.message || 'Não foi possível carregar essa comanda.';
        }
    });

    window.comandasAbertasUI = {
        load: loadOrders,
        close: () => setOpen(false)
    };
    loadOrders();
    window.setInterval(loadOrders, 15000);
})();
