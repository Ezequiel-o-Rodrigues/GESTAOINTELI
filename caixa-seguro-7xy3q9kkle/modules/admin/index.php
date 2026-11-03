<?php
require_once __DIR__ . '/../../includes/permissions.php';
requireModuleAccess('admin');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['csrf_admin_user'])) {
    $_SESSION['csrf_admin_user'] = bin2hex(random_bytes(32));
}

function ensureAnotherActiveAdmin(PDO $db, $userId) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin' AND ativo = 1 AND id <> ?");
    $stmt->execute([(int) $userId]);

    if ((int) $stmt->fetchColumn() < 1) {
        throw new Exception('O sistema precisa manter pelo menos um administrador ativo.');
    }
}

$action = $_POST['action'] ?? '';

// Handlers: salvar/atualizar usuário, alternar ativo, deletar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (in_array($action, ['save_user', 'toggle_user', 'delete_user'], true)
            && !hash_equals($_SESSION['csrf_admin_user'], (string) ($_POST['csrf_token'] ?? ''))) {
            throw new Exception('A sessão do formulário expirou. Atualize a página e tente novamente.');
        }

        if ($action === 'save_user') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $perfil = strtolower(trim((string) ($_POST['perfil'] ?? 'caixa')));
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $senha = (string) ($_POST['senha'] ?? '');

            if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Informe um nome e um e-mail válido.');
            }
            if (!in_array($perfil, ['admin', 'caixa', 'estoque'], true)) {
                throw new Exception('Perfil inválido. Use administrador, caixa ou estoque.');
            }
            if (($id === null || $senha !== '') && strlen($senha) < 8) {
                throw new Exception('A senha deve ter pelo menos 8 caracteres.');
            }

            if ($id) {
                $stmt = $db->prepare('SELECT perfil, ativo FROM usuarios WHERE id = ?');
                $stmt->execute([$id]);
                $usuarioAtual = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$usuarioAtual) {
                    throw new Exception('Usuário não encontrado.');
                }
                if ((int) $id === (int) ($_SESSION['usuario_id'] ?? 0) && ($perfil !== 'admin' || !$ativo)) {
                    throw new Exception('Não é possível remover seu próprio acesso de administrador.');
                }
                if ($usuarioAtual['perfil'] === 'admin' && (int) $usuarioAtual['ativo'] === 1
                    && ($perfil !== 'admin' || !$ativo)) {
                    ensureAnotherActiveAdmin($db, $id);
                }

                if (!empty($senha)) {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE usuarios SET nome = ?, email = ?, perfil = ?, ativo = ?, senha = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $perfil, $ativo, $hash, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE usuarios SET nome = ?, email = ?, perfil = ?, ativo = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $perfil, $ativo, $id]);
                }
                $_SESSION['sucesso'] = 'Usuário atualizado com sucesso.';
            } else {
                if (empty($senha)) throw new Exception('Senha é obrigatória para novo usuário.');
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$nome, $email, $hash, $perfil, $ativo]);
                $_SESSION['sucesso'] = 'Usuário criado com sucesso.';
            }
            header('Location: index.php'); exit;
        }

        if ($action === 'toggle_user') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
                throw new Exception('Não é possível desativar sua própria conta.');
            }
            $stmt = $db->prepare('SELECT perfil, ativo FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) {
                throw new Exception('Usuário não encontrado.');
            }
            if ($usuario['perfil'] === 'admin' && (int) $usuario['ativo'] === 1) {
                ensureAnotherActiveAdmin($db, $id);
            }
            $stmt = $db->prepare('UPDATE usuarios SET ativo = NOT ativo WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['sucesso'] = 'Status do usuário alterado.';
            header('Location: index.php'); exit;
        }

        if ($action === 'delete_user') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
                throw new Exception('Não é possível remover sua própria conta.');
            }
            $stmt = $db->prepare('SELECT perfil, ativo FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) {
                throw new Exception('Usuário não encontrado.');
            }
            if ($usuario['perfil'] === 'admin' && (int) $usuario['ativo'] === 1) {
                ensureAnotherActiveAdmin($db, $id);
            }
            $stmt = $db->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['sucesso'] = 'Usuário removido.';
            header('Location: index.php'); exit;
        }

        if ($action === 'save_commission') {
    $rate_percent = floatval($_POST['commission_rate'] ?? 3);
    if ($rate_percent < 0 || $rate_percent > 100) {
        throw new Exception('Taxa de comissão deve estar entre 0% e 100%.');
    }
    
    $rate = $rate_percent / 100; // Converter de porcentagem para decimal
    
    // Usar a NOVA tabela configuracoes_sistema
    $stmt = $db->prepare("INSERT INTO configuracoes_sistema (chave, valor, descricao) VALUES ('commission_rate', ?, 'Taxa de comissão dos garçons') ON DUPLICATE KEY UPDATE valor = ?, updated_at = NOW()");
    $stmt->execute([$rate, $rate]);
    $_SESSION['sucesso'] = 'Taxa de comissão atualizada para ' . number_format($rate_percent, 1) . '%.';
    header('Location: index.php'); exit;
}

        if ($action === 'save_garcom') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $nome = trim($_POST['nome'] ?? '');
            $codigo = trim($_POST['codigo'] ?? '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if (empty($nome)) throw new Exception('Nome é obrigatório.');

            if ($id) {
                $stmt = $db->prepare("UPDATE garcons SET nome = ?, codigo = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$nome, $codigo, $ativo, $id]);
                $_SESSION['sucesso'] = 'Garçom atualizado com sucesso.';
            } else {
                $stmt = $db->prepare("INSERT INTO garcons (nome, codigo, ativo, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$nome, $codigo, $ativo]);
                $_SESSION['sucesso'] = 'Garçom criado com sucesso.';
            }
            header('Location: index.php'); exit;
        }

        if ($action === 'toggle_garcom') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare('UPDATE garcons SET ativo = NOT ativo WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['sucesso'] = 'Status do garçom alterado.';
            header('Location: index.php'); exit;
        }

        if ($action === 'remove_garcom') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare('UPDATE garcons SET ativo = 0 WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['sucesso'] = 'Garçom removido da lista.';
            header('Location: index.php'); exit;
        }
    } catch (Exception $e) {
        $_SESSION['erro'] = 'Erro: ' . $e->getMessage();
        header('Location: index.php'); exit;
    }
}

// Buscar usuários
$stmt = $db->prepare('SELECT id, nome, email, perfil, ativo, created_at FROM usuarios ORDER BY id DESC');
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mostrar somente garçons ativos; os inativos permanecem no banco para preservar o histórico.
$stmt = $db->prepare('SELECT id, nome, codigo, ativo, created_at FROM garcons WHERE ativo = 1 ORDER BY nome');
$stmt->execute();
$garcons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar configuração de comissão - NOVO CÓDIGO
try {
    $stmt = $db->prepare('SELECT valor FROM configuracoes_sistema WHERE chave = "commission_rate"');
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config && isset($config['valor'])) {
        $current_commission = floatval($config['valor']);
    } else {
        // Se não existir, criar com valor padrão
        $stmt = $db->prepare("INSERT INTO configuracoes_sistema (chave, valor, descricao) VALUES ('commission_rate', '0.03', 'Taxa de comissão dos garçons')");
        $stmt->execute();
        $current_commission = 0.03;
    }
} catch (Exception $e) {
    // Fallback para valor padrão em caso de erro
    $current_commission = 0.03;
    error_log("Erro ao buscar taxa de comissão: " . $e->getMessage());
}

require_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Administração</h1>
    </div>

    <?php if (isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['sucesso'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['erro'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
            <div>
                <h2 class="h5 mb-1"><i class="bi bi-people" aria-hidden="true"></i> Usuários do sistema</h2>
                <p class="mb-0 text-muted small">Gerencie contas e atribua um dos três perfis de acesso.</p>
            </div>
            <button type="button" class="btn btn-primary" id="btn-novo-usuario"><i class="bi bi-person-plus" aria-hidden="true"></i> Novo usuário</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                    <tr><td colspan="6" class="py-4 text-center text-muted">Nenhum usuário cadastrado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars([
                            'admin' => 'Administrador',
                            'caixa' => 'Caixa',
                            'estoque' => 'Estoque'
                        ][strtolower(trim((string) $usuario['perfil']))] ?? 'Perfil inválido', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge bg-<?= $usuario['ativo'] ? 'success' : 'secondary' ?>"><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td><?= !empty($usuario['created_at']) ? date('d/m/Y', strtotime($usuario['created_at'])) : '-' ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-edit-user
                                    data-id="<?= (int) $usuario['id'] ?>"
                                    data-nome="<?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-perfil="<?= htmlspecialchars($usuario['perfil'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-ativo="<?= (int) $usuario['ativo'] ?>"
                                ><i class="bi bi-pencil" aria-hidden="true"></i> Editar</button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Alterar o status deste usuário?')">
                                    <input type="hidden" name="action" value="toggle_user">
                                    <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_admin_user'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi <?= $usuario['ativo'] ? 'bi-person-slash' : 'bi-person-check' ?>" aria-hidden="true"></i> <?= $usuario['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Remover este usuário? A ação não pode ser desfeita.')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_admin_user'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Remover</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="userModalTitle"><i class="bi bi-person-plus" aria-hidden="true"></i> Novo usuário</h2>
                        <p class="mb-0 mt-1 small text-muted">Defina os dados de acesso e o perfil da conta.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="id" id="userId">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_admin_user'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label for="userNome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="userNome" name="nome" required maxlength="120">
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="userEmail" name="email" required maxlength="190">
                        </div>
                        <div class="mb-3">
                            <label for="userPerfil" class="form-label">Perfil</label>
                            <select class="form-select" id="userPerfil" name="perfil" required>
                                <option value="admin">Administrador</option>
                                <option value="caixa">Caixa</option>
                                <option value="estoque">Estoque</option>
                            </select>
                            <div class="form-text">O perfil determina quais módulos e operações estarão disponíveis para a conta.</div>
                        </div>
                        <div class="mb-3">
                            <label for="userSenha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="userSenha" name="senha" minlength="8" autocomplete="new-password">
                            <div class="form-text">Obrigatória para novos usuários; deixe em branco para manter a senha atual.</div>
                        </div>
                        <div class="form-check stock-account-status">
                            <input class="form-check-input" type="checkbox" id="userAtivo" name="ativo" checked>
                            <label class="form-check-label" for="userAtivo">Conta ativa</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle" aria-hidden="true"></i> Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i> Salvar usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Card: Configurações -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-sliders" aria-hidden="true"></i> Configurações do sistema</h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="save_commission">
                <div class="col-md-4">
                    <label for="commission_rate" class="form-label">Taxa de Comissão dos Garçons (%)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="commission_rate" name="commission_rate" 
                               value="<?= $current_commission * 100 ?>" min="0" max="100" step="0.1" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Taxa atual: <?= number_format($current_commission * 100, 1) ?>%</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i> Salvar configuração</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card: Desempenho dos Garçons -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="bi bi-graph-up" aria-hidden="true"></i> Desempenho dos garçons</h5>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" onclick="setPeriodoRapido('hoje')">Hoje</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setPeriodoRapido('semana')">Esta Semana</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setPeriodoRapido('mes')">Este Mês</button>
                    </div>
                    <input type="date" id="data-inicio-garcons" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" style="width: 150px;">
                    <input type="date" id="data-fim-garcons" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" style="width: 150px;">
                    <button class="btn btn-primary btn-sm" onclick="atualizarDesempenhoGarcons()"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Atualizar</button>
                </div>
            </div>
            <div id="garcons-performance">
                <div class="text-center py-4" id="garcons-loading">Carregando dados...</div>
            </div>
        </div>
    </div>

    <!-- Card: Gerenciar Garçons -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Gerenciar garçons</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#garcomModal" onclick="novoGarcom()"><i class="bi bi-person-plus" aria-hidden="true"></i> Novo garçom</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($garcons)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nenhum garçom ativo cadastrado.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach($garcons as $garcom): ?>
                        <tr>
                            <td><?= htmlspecialchars($garcom['nome']) ?></td>
                            <td><?= htmlspecialchars($garcom['codigo'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $garcom['ativo'] ? 'success' : 'secondary' ?>">
                                    <?= $garcom['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($garcom['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editarGarcom(<?= $garcom['id'] ?>, '<?= htmlspecialchars($garcom['nome']) ?>', '<?= htmlspecialchars($garcom['codigo'] ?? '') ?>', <?= $garcom['ativo'] ?>)" data-bs-toggle="modal" data-bs-target="#garcomModal"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Alternar status?')">
                                    <input type="hidden" name="action" value="toggle_garcom">
                                    <input type="hidden" name="id" value="<?= $garcom['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Alternar status</button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Remover este garçom da lista? O histórico será preservado.')">
                                    <input type="hidden" name="action" value="remove_garcom">
                                    <input type="hidden" name="id" value="<?= $garcom['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Remover</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- Modal Garçom -->
<div class="modal fade" id="garcomModal" tabindex="-1" aria-labelledby="garcomModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="garcomModalTitle"><i class="bi bi-person-badge" aria-hidden="true"></i> Novo Garçom</h5>
                    <p class="mb-0 mt-1 small text-muted">Cadastre os dados usados para identificar o atendimento.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" id="garcomForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_garcom">
                    <input type="hidden" name="id" id="garcom_id">
                    
                    <div class="mb-3">
                        <label for="garcom_nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="garcom_nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="garcom_codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="garcom_codigo" name="codigo">
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="garcom_ativo" name="ativo" checked>
                        <label class="form-check-label" for="garcom_ativo">Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle" aria-hidden="true"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i> Salvar garçom</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS (bundle com Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= PathConfig::modules('admin/') ?>admin.js"></script>

<!-- Fetch and render performance dos garçons -->
<script>
function formatCurrency(v) {
    return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatPeriod(dataInicio, dataFim) {
    if (dataInicio === dataFim) {
        return new Date(dataInicio + 'T00:00:00').toLocaleDateString('pt-BR');
    }
    return new Date(dataInicio + 'T00:00:00').toLocaleDateString('pt-BR') + ' a ' + new Date(dataFim + 'T00:00:00').toLocaleDateString('pt-BR');
}

function carregarDesempenhoGarcons() {
    const container = document.getElementById('garcons-performance');
    const loading = document.getElementById('garcons-loading');
    const dataInicio = document.getElementById('data-inicio-garcons').value;
    const dataFim = document.getElementById('data-fim-garcons').value;
    const apiUrl = '<?= PathConfig::api("garcons.php") ?>';
    
    const url = `${apiUrl}?data_inicio=${dataInicio}&data_fim=${dataFim}`;
    
    if (!loading) {
        container.innerHTML = '<div class="text-center py-4">Carregando dados...</div>';
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = '<div class="alert alert-danger">Erro: ' + data.error + '</div>';
                return;
            }

            const avg = data.average || 0;
            const periodo = formatPeriod(data.data_inicio, data.data_fim);
            const commissionRate = data.commission_rate_percent || 3;
            let html = '';
            html += '<p class="small text-muted">Período: <strong>' + periodo + '</strong> • Comandas com garçons: <strong>' + data.total_comandas + '</strong> • Garçons ativos: <strong>' + data.active_garcons + '</strong> • Média: <strong>' + avg + '</strong> • Comissão: <strong>' + commissionRate + '%</strong></p>';
            html += '<div class="table-responsive"><table class="table table-sm align-middle">';
            html += '<thead><tr><th>Garçom</th><th>Comandas</th><th>Performance</th><th>Vendas</th><th>Comissão</th><th>Classificação</th></tr></thead><tbody>';

            data.garcons.forEach(g => {
                const pct = g.percent_of_average !== null ? g.percent_of_average : 0;
                const barValue = Math.min(pct, 200);
                const badgeClass = g.badge ? 'badge bg-' + g.badge : 'badge bg-secondary';
                html += '<tr>';
                html += '<td>' + (g.codigo ? ('<small class="text-muted">' + g.codigo + '</small> ') : '') + g.nome + '</td>';
                html += '<td>' + g.comandas + '</td>';
                html += '<td style="min-width:220px">';
                html += '<div class="d-flex align-items-center">';
                html += '<div class="progress flex-grow-1 me-2" style="height:12px"><div class="progress-bar" role="progressbar" style="width:' + barValue + '%" aria-valuenow="' + barValue + '" aria-valuemin="0" aria-valuemax="200"></div></div>';
                html += '<small class="text-muted">' + (g.percent_of_average !== null ? g.percent_of_average + '%':'-') + '</small>';
                html += '</div></td>';
                html += '<td>' + formatCurrency(g.vendas_total) + '</td>';
                html += '<td>' + formatCurrency(g.comissao) + '</td>';
                html += '<td><span class="' + badgeClass + '">' + g.classification + '</span></td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        })
        .catch(err => {
            container.innerHTML = '<div class="alert alert-danger">Erro ao carregar dados</div>';
            console.error(err);
        });
}

function atualizarDesempenhoGarcons() {
    carregarDesempenhoGarcons();
}

function setPeriodoRapido(periodo) {
    const dataInicioInput = document.getElementById('data-inicio-garcons');
    const dataFimInput = document.getElementById('data-fim-garcons');
    const hoje = new Date();
    
    switch(periodo) {
        case 'hoje':
            const hojeStr = hoje.toISOString().split('T')[0];
            dataInicioInput.value = hojeStr;
            dataFimInput.value = hojeStr;
            break;
            
        case 'semana':
            const inicioSemana = new Date(hoje);
            inicioSemana.setDate(hoje.getDate() - hoje.getDay() + 1); // Segunda-feira
            dataInicioInput.value = inicioSemana.toISOString().split('T')[0];
            dataFimInput.value = hoje.toISOString().split('T')[0];
            break;
            
        case 'mes':
            const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
            dataInicioInput.value = inicioMes.toISOString().split('T')[0];
            dataFimInput.value = hoje.toISOString().split('T')[0];
            break;
    }
    
    carregarDesempenhoGarcons();
}

document.addEventListener('DOMContentLoaded', function () {
    carregarDesempenhoGarcons();
});

function novoGarcom() {
    definirTituloGarcom('Novo garçom', 'bi-person-plus');
    document.getElementById('garcomForm').reset();
    document.getElementById('garcom_id').value = '';
    document.getElementById('garcom_ativo').checked = true;
}

function editarGarcom(id, nome, codigo, ativo) {
    definirTituloGarcom('Editar garçom', 'bi-pencil-square');
    document.getElementById('garcom_id').value = id;
    document.getElementById('garcom_nome').value = nome;
    document.getElementById('garcom_codigo').value = codigo;
    document.getElementById('garcom_ativo').checked = ativo == 1;
}

function definirTituloGarcom(texto, icone) {
    const titulo = document.getElementById('garcomModalTitle');
    const elementoIcone = document.createElement('i');
    elementoIcone.className = `bi ${icone}`;
    elementoIcone.setAttribute('aria-hidden', 'true');
    titulo.replaceChildren(elementoIcone, document.createTextNode(` ${texto}`));
}
</script>

<?php require_once '../../includes/footer.php'; ?>