(() => {
    const root = document.getElementById('react-app');
    const navRoot = document.getElementById('mainNavRoot');
    if (!root && !navRoot) return;
    if (!window.React || !window.ReactDOM) {
        console.error('A interface React não pôde iniciar porque React não foi carregado.');
        [root, navRoot].filter(Boolean).forEach((element) => {
            element.textContent = 'A interface não pôde ser carregada. Atualize a página.';
        });
        return;
    }

    const h = React.createElement;

    function NavigationApp() {
        const role = navRoot.dataset.role;
        const links = [
            { key: 'home', title: 'Início', icon: 'bi-speedometer2', href: navRoot.dataset.homeUrl, active: navRoot.dataset.homeActive === 'true' },
            role === 'admin' && { key: 'menu', title: 'Cardápio', icon: 'bi-journal-text', href: navRoot.dataset.menuUrl },
            (role === 'admin' || role === 'caixa') && { key: 'caixa', title: 'Caixa', icon: 'bi-cash-stack', href: navRoot.dataset.cashUrl },
            (role === 'admin' || role === 'estoque') && { key: 'estoque', title: 'Estoque', icon: 'bi-box-seam', href: navRoot.dataset.stockUrl },
            ['admin', 'caixa', 'estoque'].includes(role) && { key: 'relatorios', title: 'Relatórios', icon: 'bi-bar-chart-line', href: navRoot.dataset.reportsUrl },
            role === 'admin' && { key: 'admin', title: 'Admin', icon: 'bi-gear', href: navRoot.dataset.adminUrl },
        ].filter(Boolean);

        return h(React.Fragment, null, ...links.map((link) => h('a', {
            key: link.key,
            href: link.href,
            'aria-current': link.active ? 'page' : undefined,
            className: `dashboard-nav-link${link.active ? ' dashboard-nav-link--active' : ''}`
        },
            h('i', { className: `bi ${link.icon}`, 'aria-hidden': 'true' }),
            h('span', null, link.title)
        )));
    }

    function LoginApp() {
        const [submitting, setSubmitting] = React.useState(false);
        const error = root.dataset.error;
        const notice = root.dataset.notice;
        const email = root.dataset.email;
        const cardapio = root.dataset.cardapio === 'true';

        return h('main', { className: 'login-page' },
            h('section', { className: 'login-card', 'aria-labelledby': 'login-title' },
                h('div', { className: 'login-brand' },
                    h('span', { className: 'login-brand-mark', 'aria-hidden': 'true' }, 'G'),
                    h('p', { className: 'login-eyebrow' }, 'GESTÃO RESTAURANTE'),
                    h('h1', { id: 'login-title' }, 'GestãoInteli JNR'),
                    h('p', null, 'Acesse sua operação com segurança.')
                ),
                h('div', { className: 'login-content' },
                    error && h('div', { className: 'login-alert login-alert--error', role: 'alert' }, error),
                    notice && h('div', { className: 'login-alert', role: 'status' }, notice),
                    h('form', {
                        method: 'post',
                        action: window.location.pathname + window.location.search,
                        onSubmit: () => setSubmitting(true)
                    },
                        cardapio && h('input', { type: 'hidden', name: 'destino', value: 'cardapio' }),
                        h('label', { className: 'login-label', htmlFor: 'email' }, 'E-mail cadastrado'),
                        h('input', {
                            className: 'login-input',
                            type: 'email',
                            id: 'email',
                            name: 'email',
                            required: true,
                            autoComplete: 'username',
                            autoFocus: true,
                            defaultValue: email,
                            placeholder: 'voce@exemplo.com'
                        }),
                        h('label', { className: 'login-label', htmlFor: 'senha' }, 'Senha'),
                        h('input', {
                            className: 'login-input',
                            type: 'password',
                            id: 'senha',
                            name: 'senha',
                            required: true,
                            autoComplete: 'current-password',
                            placeholder: 'Digite sua senha'
                        }),
                        h('button', { className: 'login-submit', type: 'submit', disabled: submitting },
                            submitting ? 'Entrando...' : 'Entrar'
                        )
                    ),
                    h('p', { className: 'login-help' }, 'Para recuperar sua senha, entre em contato com o suporte.')
                )
            )
        );
    }

    function DashboardApp() {
        const role = root.dataset.role;
        const firstName = root.dataset.firstName || 'equipe';
        const roleLabels = { admin: 'Administrador', caixa: 'Operador de caixa', estoque: 'Operador de estoque' };
        const modules = [
            { key: 'caixa', title: 'Frente de caixa', description: 'Abra comandas, registre itens e conclua vendas.', href: root.dataset.cashUrl, accent: 'orange' },
            { key: 'estoque', title: 'Controle de estoque', description: 'Acompanhe produtos, entradas e inventários.', href: root.dataset.stockUrl, accent: 'amber' },
            { key: 'relatorios', title: 'Relatórios', description: 'Consulte vendas, desempenho e indicadores.', href: root.dataset.reportsUrl, accent: 'blue' },
            { key: 'admin', title: 'Administração', description: 'Gerencie usuários, garçons e configurações.', href: root.dataset.adminUrl, accent: 'violet' },
            { key: 'cardapio', title: 'Cardápio público', description: 'Atualize os itens exibidos aos clientes.', href: root.dataset.menuUrl, accent: 'green' },
        ].filter((module) => module.key === 'cardapio'
            ? role === 'admin'
            : role === 'admin' || (role === 'caixa' && ['caixa', 'relatorios'].includes(module.key))
                || (role === 'estoque' && ['estoque', 'relatorios'].includes(module.key)));

        return h('main', { className: 'dashboard-page' },
            h('section', { className: 'dashboard-welcome' },
                h('div', null,
                    h('p', { className: 'dashboard-eyebrow' }, roleLabels[role] || 'Sistema operacional'),
                    h('h1', null, `Olá, ${firstName}`),
                    h('p', null, 'Escolha um módulo para continuar.')
                )
            ),
            h('section', { className: 'module-grid', 'aria-label': 'Módulos disponíveis' },
                ...modules.map((module) => h('a', {
                    key: module.key,
                    className: `module-card module-card--${module.accent}`,
                    href: module.href
                },
                    h('span', { className: 'module-card-kicker' }, 'MÓDULO'),
                    h('h2', null, module.title),
                    h('p', null, module.description),
                    h('span', { className: 'module-card-action' }, 'Acessar módulo')
                ))
            )
        );
    }

    function CashHeaderApp() {
        return h('div', { className: 'mini-header' },
            h('h1', null, 'Caixa rápido'),
            h('a', { href: root.dataset.homeUrl }, 'Início')
        );
    }

    function StockApp() {
        const [category, setCategory] = React.useState('all');
        const [status, setStatus] = React.useState('all');
        const [search, setSearch] = React.useState('');
        const productsElement = document.getElementById(root.dataset.productsId);
        const categoriesElement = document.getElementById(root.dataset.categoriesId);

        if (!productsElement || !categoriesElement) {
            return h('p', { className: 'alert alert-danger', role: 'alert' },
                'Não foi possível carregar a lista de produtos. Atualize a página.'
            );
        }

        let products;
        let categories;
        try {
            products = JSON.parse(productsElement.textContent);
            categories = JSON.parse(categoriesElement.textContent);
            if (!Array.isArray(products) || !Array.isArray(categories)) {
                throw new TypeError('Os dados do estoque devem ser listas.');
            }
        } catch (error) {
            console.error('Não foi possível ler os dados de estoque.', error);
            return h('p', { className: 'alert alert-danger', role: 'alert' },
                'Não foi possível carregar a lista de produtos. Atualize a página.'
            );
        }

        const getStockStatus = (product) => {
            const currentStock = Number(product.estoque_atual);
            const minimumStock = Number(product.estoque_minimo);
            return currentStock === 0 ? 'critical' : currentStock <= minimumStock ? 'low' : 'normal';
        };

        const filteredProducts = products.filter((product) => {
            const productStatus = getStockStatus(product);
            const matchesCategory = category === 'all' || String(product.categoria_id) === category;
            const matchesStatus = status === 'all' || productStatus === status;
            const matchesSearch = String(product.nome).toLocaleLowerCase('pt-BR').includes(search.trim().toLocaleLowerCase('pt-BR'));

            return matchesCategory && matchesStatus && matchesSearch;
        });

        const formatCurrency = (value) => new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(Number(value) || 0);

        const rows = filteredProducts.map((product) => {
            const currentStock = Number(product.estoque_atual);
            const minimumStock = Number(product.estoque_minimo);
            const productStatus = getStockStatus(product);
            const statusLabels = { critical: 'Crítico', low: 'Baixo', normal: 'Normal' };

            return h('tr', { key: product.id },
                h('td', null, product.id),
                h('td', null, h('strong', null, product.nome)),
                h('td', null, product.categoria_nome),
                h('td', null, formatCurrency(product.preco)),
                h('td', { className: productStatus === 'critical' ? 'no-stock' : productStatus === 'low' ? 'low-stock' : '' }, currentStock),
                h('td', null, minimumStock),
                h('td', null, h('span', { className: `status-badge status-${productStatus}` }, statusLabels[productStatus])),
                h('td', null,
                    h('div', { className: 'stock-row-actions' },
                        h('button', {
                            type: 'button',
                            className: 'btn btn-sm btn-outline-primary',
                            onClick: () => window.estoqueManager.editProduct(Number(product.id))
                        }, 'Editar'),
                        h('button', {
                            type: 'button',
                            className: 'btn btn-sm btn-outline-success',
                            onClick: () => window.estoqueManager.showEntryModal(Number(product.id))
                        }, 'Registrar entrada'),
                        h('button', {
                            type: 'button',
                            className: 'btn btn-sm btn-outline-info',
                            onClick: () => window.abrirModalInventarioProduto(Number(product.id))
                        }, 'Inventário'),
                        h('button', {
                            type: 'button',
                            className: 'btn btn-sm btn-outline-warning',
                            onClick: () => window.estoqueManager.toggleProduct(Number(product.id), 0)
                        }, 'Desativar')
                    )
                )
            );
        });

        return h(React.Fragment, null,
            h('section', { className: 'card mb-4', 'aria-labelledby': 'stock-filter-title' },
                h('div', { className: 'card-header' },
                    h('h2', { id: 'stock-filter-title', className: 'h5 mb-0' }, 'Filtrar produtos')
                ),
                h('div', { className: 'card-body' },
                    h('div', { className: 'stock-filter-grid' },
                        h('div', null,
                            h('label', { className: 'form-label', htmlFor: 'stock-category-filter' }, 'Categoria'),
                            h('select', {
                                id: 'stock-category-filter',
                                className: 'form-select',
                                value: category,
                                onChange: (event) => setCategory(event.target.value)
                            },
                                h('option', { value: 'all' }, 'Todas as categorias'),
                                ...categories.map((item) => h('option', { key: item.id, value: String(item.id) }, item.nome))
                            )
                        ),
                        h('div', null,
                            h('label', { className: 'form-label', htmlFor: 'stock-status-filter' }, 'Status'),
                            h('select', {
                                id: 'stock-status-filter',
                                className: 'form-select',
                                value: status,
                                onChange: (event) => setStatus(event.target.value)
                            },
                                h('option', { value: 'all' }, 'Todos'),
                                h('option', { value: 'normal' }, 'Normal'),
                                h('option', { value: 'low' }, 'Baixo'),
                                h('option', { value: 'critical' }, 'Crítico')
                            )
                        ),
                        h('div', null,
                            h('label', { className: 'form-label', htmlFor: 'stock-search' }, 'Buscar produto'),
                            h('input', {
                                id: 'stock-search',
                                className: 'form-control',
                                type: 'search',
                                value: search,
                                onChange: (event) => setSearch(event.target.value),
                                placeholder: 'Digite o nome do produto'
                            })
                        )
                    )
                )
            ),
            h('section', { className: 'card', 'aria-labelledby': 'stock-list-title' },
                h('div', { className: 'card-header d-flex align-items-center justify-content-between gap-2' },
                    h('h2', { id: 'stock-list-title', className: 'h5 mb-0' }, 'Produtos'),
                    h('span', { className: 'text-muted small', 'aria-live': 'polite' },
                        `${filteredProducts.length} de ${products.length} produtos`
                    )
                ),
                h('div', { className: 'table-responsive' },
                    filteredProducts.length
                        ? h('table', { className: 'table table-hover align-middle mb-0' },
                            h('thead', { className: 'table-light' },
                                h('tr', null,
                                    ...['ID', 'Produto', 'Categoria', 'Preço', 'Estoque', 'Mínimo', 'Status', 'Ações'].map((label) => h('th', { key: label, scope: 'col' }, label))
                                )
                            ),
                            h('tbody', null, ...rows)
                        )
                        : h('p', { className: 'stock-empty-state', role: 'status' },
                            products.length ? 'Nenhum produto corresponde aos filtros.' : 'Nenhum produto ativo cadastrado.'
                        )
                )
            )
        );
    }

    if (navRoot) ReactDOM.createRoot(navRoot).render(h(NavigationApp));
    if (root) {
        const screen = root.dataset.screen;
        const app = screen === 'login'
            ? h(LoginApp)
            : screen === 'dashboard'
                ? h(DashboardApp)
                : screen === 'cash-header'
                    ? h(CashHeaderApp)
                    : screen === 'stock-list'
                        ? h(StockApp)
                    : null;
        if (app) ReactDOM.createRoot(root).render(app);
    }
})();
