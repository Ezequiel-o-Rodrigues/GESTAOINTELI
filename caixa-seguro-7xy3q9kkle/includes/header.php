<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';

// Verificar se o usuário está logado
$usuarioLogado = isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
$usuarioNome = $_SESSION['usuario_nome'] ?? '';
$usuarioEmail = $_SESSION['usuario_email'] ?? '';
$usuarioPerfil = $_SESSION['usuario_perfil'] ?? '';
$tituloPagina = $tituloPagina ?? 'Sistema Restaurante';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$paginaInicio = rtrim(dirname($scriptName), '/.') === rtrim(PathConfig::url(), '/');
$urlGerenciarCardapio = rtrim(dirname(PathConfig::url()), '/') . '/admin-seguro-7xy3q9bk/gerenciar_cardapio.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- ✅ Bootstrap via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { corePlugins: { preflight: false } };
    </script>
    
    <!-- ✅ CSS personalizado - se existir na raiz -->
    <?php if (file_exists(__DIR__ . '/../css/style.css')): ?>
        <link rel="stylesheet" href="<?= PathConfig::url('css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
    <?php elseif (file_exists(__DIR__ . '/../style.css')): ?>
        <link rel="stylesheet" href="<?= PathConfig::url('style.css') ?>?v=<?= filemtime(__DIR__ . '/../style.css') ?>">
    <?php else: ?>
        <style>
            /* Estilos básicos de fallback */
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0; 
                padding: 0;
                background-color: #f8f9fa;
            }
            .header { 
                background: #343a40; 
                padding: 1rem; 
                color: white; 
            }
            .header h1 { margin: 0; }
            .main-nav a { 
                color: white; 
                margin-right: 20px; 
                text-decoration: none;
                font-weight: 500;
            }
            .main-nav a:hover { text-decoration: underline; }
            .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
            .footer { background: #343a40; color: white; padding: 1rem; text-align: center; margin-top: 2rem; }
            
            /* Estilos para a bolinha do usuário */
            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: linear-gradient(45deg, #667eea, #764ba2);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                font-size: 14px;
                cursor: pointer;
                border: 2px solid rgba(255,255,255,0.3);
                transition: all 0.3s ease;
            }
            
            .user-avatar:hover {
                transform: scale(1.05);
                border-color: rgba(255,255,255,0.6);
            }
            
            .dropdown-menu {
                min-width: 250px;
            }
            
            .dropdown-header {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #dee2e6;
            }
        </style>
    <?php endif; ?>
    
    <script>
        const BASE_URL = '<?= PathConfig::url() ?>';
        const API_BASE = '<?= PathConfig::api() ?>';
    </script>
    
    <!-- ✅ Path Config JS - se existir -->
    <?php if (file_exists(__DIR__ . '/../js/path-config.js')): ?>
        <script src="<?= PathConfig::url('js/path-config.js') ?>"></script>
    <?php endif; ?>
</head>
<body class="bg-slate-50">
    <div class="page-layout">
    <header class="header bg-slate-900 text-white shadow-lg">
        <div class="dashboard-header-inner mx-auto grid w-full max-w-screen-2xl grid-cols-[1fr_auto_1fr] items-center gap-6 px-6 py-3">
            <a href="<?= PathConfig::url('index.php') ?>" class="flex min-w-0 items-center gap-3 text-white no-underline">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-500 text-white">
                    <i data-lucide="utensils" class="h-6 w-6"></i>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-base font-bold tracking-tight">Sistema Restaurante</span>
                    <span class="block truncate text-xs text-slate-400">Espetinho do Júnior</span>
                </span>
            </a>

            <nav class="main-nav dashboard-nav flex items-center justify-center gap-1" aria-label="Navegação principal">
                <a href="<?= PathConfig::url('index.php') ?>"<?= $paginaInicio ? ' aria-current="page"' : '' ?> class="dashboard-nav-link flex items-center gap-2 rounded-lg <?= $paginaInicio ? 'border-b-2 border-orange-500 text-orange-400' : 'text-slate-300' ?> px-3 py-3 text-sm font-medium no-underline">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i><span>Início</span>
                </a>
                <a href="<?= htmlspecialchars($urlGerenciarCardapio, ENT_QUOTES, 'UTF-8') ?>" class="dashboard-nav-link flex items-center gap-2 rounded-lg px-3 py-3 text-sm font-medium text-slate-300 no-underline">
                    <i data-lucide="book-open" class="h-4 w-4"></i><span>Cardápio</span>
                </a>
                <a href="<?= PathConfig::modules('caixa/') ?>" class="dashboard-nav-link flex items-center gap-2 rounded-lg px-3 py-3 text-sm font-medium text-slate-300 no-underline">
                    <i data-lucide="wallet" class="h-4 w-4"></i><span>Caixa</span>
                </a>
                <a href="<?= PathConfig::modules('estoque/') ?>" class="dashboard-nav-link flex items-center gap-2 rounded-lg px-3 py-3 text-sm font-medium text-slate-300 no-underline">
                    <i data-lucide="package" class="h-4 w-4"></i><span>Estoque</span>
                </a>
                <a href="<?= PathConfig::modules('relatorios/') ?>" class="dashboard-nav-link flex items-center gap-2 rounded-lg px-3 py-3 text-sm font-medium text-slate-300 no-underline">
                    <i data-lucide="chart-no-axes-column-increasing" class="h-4 w-4"></i><span>Relatórios</span>
                </a>
                <a href="<?= PathConfig::modules('admin/') ?>" class="dashboard-nav-link flex items-center gap-2 rounded-lg px-3 py-3 text-sm font-medium text-slate-300 no-underline">
                    <i data-lucide="settings" class="h-4 w-4"></i><span>Admin</span>
                </a>
            </nav>

            <?php if ($usuarioLogado): ?>
            <div
                id="userMenuRoot"
                class="dashboard-user-dropdown justify-self-end"
                data-user-name="<?= htmlspecialchars($usuarioNome, ENT_QUOTES, 'UTF-8') ?>"
                data-user-email="<?= htmlspecialchars($usuarioEmail, ENT_QUOTES, 'UTF-8') ?>"
                data-user-initials="<?= htmlspecialchars(getInitials($usuarioNome), ENT_QUOTES, 'UTF-8') ?>"
                data-is-admin="<?= $usuarioPerfil === 'admin' ? 'true' : 'false' ?>"
                data-admin-url="<?= htmlspecialchars(PathConfig::modules('admin/'), ENT_QUOTES, 'UTF-8') ?>"
                data-logout-url="<?= htmlspecialchars(PathConfig::url('logout.php'), ENT_QUOTES, 'UTF-8') ?>"
            ></div>
            <?php else: ?>
            <div class="justify-self-end">
                <a href="<?= PathConfig::url('login.php') ?>" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-in-right"></i> Entrar
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Modal Meu Perfil -->
    <div class="modal fade" id="userProfileModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person"></i> Meu Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <!-- Avatar Grande -->
                    <div class="mb-3">
                        <div class="user-avatar" style="width: 80px; height: 80px; font-size: 24px; margin: 0 auto;">
                            <?= getInitials($usuarioNome) ?>
                        </div>
                    </div>
                    
                    <form id="profileForm">
                        <div class="mb-3 text-start">
                            <label class="form-label"><small><strong>Nome</strong></small></label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($usuarioNome) ?>" readonly>
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label"><small><strong>Email</strong></small></label>
                            <input type="email" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($usuarioEmail) ?>" readonly>
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label"><small><strong>Perfil</strong></small></label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($usuarioPerfil) ?>" readonly>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="bi bi-info-circle"></i> 
                            Para alterar suas informações, entre em contato com o administrador.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-lock"></i> Alterar Senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label class="form-label"><small><strong>Senha Atual</strong></small></label>
                            <input type="password" class="form-control form-control-sm" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><small><strong>Nova Senha</strong></small></label>
                            <input type="password" class="form-control form-control-sm" id="newPassword" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><small><strong>Confirmar Nova Senha</strong></small></label>
                            <input type="password" class="form-control form-control-sm" id="confirmPassword" required>
                        </div>
                        
                        <div id="passwordAlert" class="alert d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="savePasswordBtn">
                        <i class="bi bi-check-lg"></i> Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/react@18.3.1/umd/react.production.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@18.3.1/umd/react-dom.production.min.js" crossorigin="anonymous"></script>
    <script>
    (() => {
        const root = document.getElementById('userMenuRoot');
        if (!root) return;
        if (!window.React || !window.ReactDOM) {
            console.error('O menu do usuário não pôde iniciar porque React não foi carregado.');
            return;
        }

        const h = React.createElement;
        const user = {
            name: root.dataset.userName,
            email: root.dataset.userEmail,
            initials: root.dataset.userInitials,
            isAdmin: root.dataset.isAdmin === 'true',
            adminUrl: root.dataset.adminUrl,
            logoutUrl: root.dataset.logoutUrl
        };

        function UserMenu() {
            const [open, setOpen] = React.useState(false);
            const trigger = React.useRef(null);
            const firstAction = React.useRef(null);

            React.useEffect(() => {
                if (!open) return undefined;
                firstAction.current?.focus();
                const closeOnOutsideClick = (event) => {
                    if (!root.contains(event.target)) setOpen(false);
                };
                const closeOnEscape = (event) => {
                    if (event.key === 'Escape') {
                        setOpen(false);
                        trigger.current?.focus();
                    }
                };
                document.addEventListener('click', closeOnOutsideClick);
                document.addEventListener('keydown', closeOnEscape);
                return () => {
                    document.removeEventListener('click', closeOnOutsideClick);
                    document.removeEventListener('keydown', closeOnEscape);
                };
            }, [open]);

            const menuAction = (icon, label, props = {}) => h(
                'a',
                {
                    className: 'dashboard-user-action',
                    role: 'menuitem',
                    onClick: () => setOpen(false),
                    ...props
                },
                h('i', { className: `bi ${icon}`, 'aria-hidden': 'true' }),
                h('span', null, label)
            );

            return h(
                'div',
                { className: 'dashboard-user-menu' },
                h(
                    'button',
                    {
                        ref: trigger,
                        type: 'button',
                        className: 'dashboard-user-button',
                        'aria-label': 'Abrir menu do usuário',
                        'aria-expanded': open,
                        'aria-haspopup': 'menu',
                        onClick: () => setOpen((isOpen) => !isOpen)
                    },
                    h(
                        'span',
                        { className: 'dashboard-user-menu-icon', 'aria-hidden': 'true' },
                        h('span'),
                        h('span'),
                        h('span')
                    )
                ),
                open && h(
                    'div',
                    { className: 'dashboard-user-popover', role: 'menu', 'aria-label': 'Menu do usuário' },
                    h(
                        'div',
                        { className: 'dashboard-user-summary' },
                        h('span', { className: 'dashboard-user-initials', 'aria-hidden': 'true' }, user.initials),
                        h(
                            'div',
                            { className: 'dashboard-user-details' },
                            h('strong', null, user.name),
                            h('span', null, user.email)
                        )
                    ),
                    h('div', { className: 'dashboard-user-divider' }),
                    menuAction('bi-person', 'Meu perfil', {
                        ref: firstAction,
                        href: '#userProfileModal',
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#userProfileModal'
                    }),
                    menuAction('bi-lock', 'Alterar senha', {
                        href: '#changePasswordModal',
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#changePasswordModal'
                    }),
                    user.isAdmin && menuAction('bi-gear', 'Administração', { href: user.adminUrl }),
                    h('div', { className: 'dashboard-user-divider' }),
                    menuAction('bi-box-arrow-right', 'Sair', {
                        className: 'dashboard-user-action dashboard-user-action--danger',
                        href: user.logoutUrl
                    })
                )
            );
        }

        ReactDOM.createRoot(root).render(h(UserMenu));
    })();
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Script para alteração de senha
    document.addEventListener('DOMContentLoaded', function() {
        const changePasswordForm = document.getElementById('changePasswordForm');
        const savePasswordBtn = document.getElementById('savePasswordBtn');
        const passwordAlert = document.getElementById('passwordAlert');
        
        savePasswordBtn.addEventListener('click', function() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validações
            if (!currentPassword || !newPassword || !confirmPassword) {
                showAlert('Preencha todos os campos', 'danger');
                return;
            }
            
            if (newPassword.length < 6) {
                showAlert('A nova senha deve ter pelo menos 6 caracteres', 'danger');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showAlert('As senhas não coincidem', 'danger');
                return;
            }
            
            // Simular envio (implementar API depois)
            showAlert('Funcionalidade em desenvolvimento...', 'info');
            
            // Aqui você implementaria a chamada para a API
            // fetch('<?= PathConfig::api("change-password.php") ?>', {
            //     method: 'POST',
            //     body: JSON.stringify({
            //         currentPassword: currentPassword,
            //         newPassword: newPassword
            //     })
            // }).then(...)
        });
        
        function showAlert(message, type) {
            passwordAlert.className = `alert alert-${type}`;
            passwordAlert.textContent = message;
            passwordAlert.classList.remove('d-none');
        }
        
        // Limpar alerta quando modal for fechado
        const changePasswordModal = document.getElementById('changePasswordModal');
        changePasswordModal.addEventListener('hidden.bs.modal', function() {
            changePasswordForm.reset();
            passwordAlert.classList.add('d-none');
        });
    });
    </script>

    <main class="container flex-1">