<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/permissions.php';

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
<body>
    <div class="page-layout">
    <header class="header">
        <div class="dashboard-header-inner">
            <a href="<?= PathConfig::url('index.php') ?>" class="dashboard-brand">
                <span class="dashboard-brand-mark">
                    <i class="bi bi-shop" aria-hidden="true"></i>
                </span>
                <span class="dashboard-brand-copy">
                    <span class="dashboard-brand-title">Sistema Restaurante</span>
                    <span class="dashboard-brand-subtitle">Espetinho do Júnior</span>
                </span>
            </a>

            <nav
                id="mainNavRoot"
                class="main-nav dashboard-nav"
                aria-label="Navegação principal"
                data-role="<?= htmlspecialchars($usuarioPerfil, ENT_QUOTES, 'UTF-8') ?>"
                data-home-url="<?= htmlspecialchars(PathConfig::url('index.php'), ENT_QUOTES, 'UTF-8') ?>"
                data-home-active="<?= $paginaInicio ? 'true' : 'false' ?>"
                data-menu-url="<?= htmlspecialchars($urlGerenciarCardapio, ENT_QUOTES, 'UTF-8') ?>"
                data-cash-url="<?= htmlspecialchars(PathConfig::modules('caixa/'), ENT_QUOTES, 'UTF-8') ?>"
                data-stock-url="<?= htmlspecialchars(PathConfig::modules('estoque/'), ENT_QUOTES, 'UTF-8') ?>"
                data-reports-url="<?= htmlspecialchars(PathConfig::modules('relatorios/'), ENT_QUOTES, 'UTF-8') ?>"
                data-admin-url="<?= htmlspecialchars(PathConfig::modules('admin/'), ENT_QUOTES, 'UTF-8') ?>"
            ></nav>

            <?php if ($usuarioLogado): ?>
            <div
                id="userMenuRoot"
                class="dashboard-user-dropdown"
                data-user-name="<?= htmlspecialchars($usuarioNome, ENT_QUOTES, 'UTF-8') ?>"
                data-user-email="<?= htmlspecialchars($usuarioEmail, ENT_QUOTES, 'UTF-8') ?>"
                data-user-initials="<?= htmlspecialchars(getInitials($usuarioNome), ENT_QUOTES, 'UTF-8') ?>"
                data-is-admin="<?= $usuarioPerfil === 'admin' ? 'true' : 'false' ?>"
                data-admin-url="<?= htmlspecialchars(PathConfig::modules('admin/'), ENT_QUOTES, 'UTF-8') ?>"
                data-logout-url="<?= htmlspecialchars(PathConfig::url('logout.php'), ENT_QUOTES, 'UTF-8') ?>"
            ></div>
            <?php else: ?>
            <div class="dashboard-login">
                <a href="<?= PathConfig::url('login.php') ?>" class="btn btn-outline-light btn-sm">
                    Entrar
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
                        Salvar
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

            const menuAction = (label, props = {}) => h(
                'a',
                {
                    className: 'dashboard-user-action',
                    role: 'menuitem',
                    onClick: () => setOpen(false),
                    ...props
                },
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
                    'Menu'
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
                    menuAction('Meu perfil', {
                        ref: firstAction,
                        href: '#userProfileModal',
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#userProfileModal'
                    }),
                    menuAction('Alterar senha', {
                        href: '#changePasswordModal',
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#changePasswordModal'
                    }),
                    user.isAdmin && menuAction('Administração', { href: user.adminUrl }),
                    h('div', { className: 'dashboard-user-divider' }),
                    menuAction('Sair', {
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

    <main class="container">