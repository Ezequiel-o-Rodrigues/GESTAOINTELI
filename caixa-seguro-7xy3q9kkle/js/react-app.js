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

    if (navRoot) ReactDOM.createRoot(navRoot).render(h(NavigationApp));
    if (root) {
        const screen = root.dataset.screen;
        const app = screen === 'login'
            ? h(LoginApp)
            : screen === 'dashboard'
                ? h(DashboardApp)
                : screen === 'cash-header'
                    ? h(CashHeaderApp)
                    : null;
        if (app) ReactDOM.createRoot(root).render(app);
    }
})();
