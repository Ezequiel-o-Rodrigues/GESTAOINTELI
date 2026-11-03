<?php
require_once __DIR__ . '/../../includes/permissions.php';
requireModuleAccess('estoque');

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ' . PathConfig::url('login.php'));
    exit;
}

if (!isset($_SESSION['csrf_produto'])) {
    $_SESSION['csrf_produto'] = bin2hex(random_bytes(32));
}

$database = new Database();
$db = $database->getConnection();

// Processar registro de inventário ANTES do header
$action = $_POST['action'] ?? '';
if ($action == 'registrar_inventario') {
    try {
        $produto_id = $_POST['produto_id'];
        $quantidade_fisica = $_POST['quantidade_fisica'];
        $observacao = $_POST['observacao'];
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        
        // Chamar a stored procedure
        $stmt = $db->prepare("CALL registrar_inventario_estoque(?, ?, ?, ?)");
        $stmt->execute([$produto_id, $quantidade_fisica, $observacao, $usuario_id]);
        
        $_SESSION['sucesso'] = "Inventário registrado com sucesso! Estoque atualizado.";
        header('Location: index.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['erro'] = "Erro ao registrar inventário: " . $e->getMessage();
        header('Location: index.php');
        exit();
    }
}

require_once '../../includes/header.php';

// Buscar produtos ativos
$query_produtos = "SELECT p.*, c.nome as categoria_nome 
                   FROM produtos p 
                   JOIN categorias c ON p.categoria_id = c.id 
                   WHERE p.ativo = 1 
                   ORDER BY c.nome, p.nome";
$stmt_produtos = $db->prepare($query_produtos);
$stmt_produtos->execute();
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as categorias
$query_categorias = "SELECT * FROM categorias ORDER BY nome";
$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->execute();
$todas_categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Buscar fornecedores
$query_fornecedores = "SELECT * FROM fornecedores ORDER BY nome";
$stmt_fornecedores = $db->prepare($query_fornecedores);
$stmt_fornecedores->execute();
$fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_ASSOC);

// Buscar alertas de estoque
$alertas_criticos = [];
$alertas_aviso = [];
$alertas_count = 0;

foreach ($produtos as $produto) {
    if ($produto['estoque_atual'] == 0) {
        $alertas_criticos[] = $produto;
        $alertas_count++;
    } elseif ($produto['estoque_atual'] <= $produto['estoque_minimo']) {
        $alertas_aviso[] = $produto;
        $alertas_count++;
    }
}

$stockReactProducts = array_map(static function ($produto) {
    return [
        'id' => (int) $produto['id'],
        'nome' => (string) $produto['nome'],
        'categoria_id' => (int) $produto['categoria_id'],
        'categoria_nome' => (string) $produto['categoria_nome'],
        'preco' => (float) $produto['preco'],
        'estoque_atual' => (float) $produto['estoque_atual'],
        'estoque_minimo' => (float) $produto['estoque_minimo'],
    ];
}, $produtos);

$stockReactCategories = array_map(static function ($categoria) {
    return [
        'id' => (int) $categoria['id'],
        'nome' => (string) $categoria['nome'],
    ];
}, $todas_categorias);
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">
                    <i class="fas fa-boxes text-primary"></i> Gestão de Estoque
                </h1>
                <div>
                    <button class="btn btn-primary" onclick="estoqueManager.showProductModal()">Novo Produto</button>
                    <button class="btn btn-info ms-2" onclick="abrirModalInventario()">Inventário físico</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    <?php if ($alertas_count > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning" id="alertaEstoque">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="alert-heading mb-0">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Alertas de Estoque (<?= $alertas_count ?>)
                    </h5>
                    <div>
                        <span class="badge bg-danger"><?= $alertas_count ?></span>
                        <button class="btn btn-sm btn-dark ms-2" onclick="toggleAlertaEstoque()" id="btnToggleAlerta" title="Minimizar alertas">
                            Recolher
                        </button>
                    </div>
                </div>
                <hr>
                <div class="row" id="conteudoAlerta">
                    <?php if (!empty($alertas_criticos)): ?>
                    <div class="col-md-6">
                        <h6><i class="fas fa-skull-crossbones text-danger"></i> Crítico</h6>
                        <?php foreach($alertas_criticos as $produto): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?= htmlspecialchars($produto['nome']) ?></span>
                            <div>
                                <span class="badge bg-danger">Zerado</span>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="estoqueManager.showEntryModal(<?= $produto['id'] ?>)">Registrar entrada</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($alertas_aviso)): ?>
                    <div class="col-md-6">
                        <h6><i class="fas fa-exclamation-triangle text-warning"></i> Atenção</h6>
                        <?php foreach($alertas_aviso as $produto): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?= htmlspecialchars($produto['nome']) ?></span>
                            <div>
                                <span class="badge bg-warning">Baixo</span>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="estoqueManager.showEntryModal(<?= $produto['id'] ?>)">Registrar entrada</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div
        id="react-app"
        data-screen="stock-list"
        data-products-id="stock-react-products"
        data-categories-id="stock-react-categories"
    ></div>
    <script id="stock-react-products" type="application/json"><?= json_encode($stockReactProducts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
    <script id="stock-react-categories" type="application/json"><?= json_encode($stockReactCategories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
</div>

<!-- Modal Entrada -->
<div class="modal fade" id="entryModal" tabindex="-1" aria-labelledby="entryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="entryModalLabel">Registrar Entrada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="entryForm">
                    <input type="hidden" id="entryProductId">
                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <div id="entryProductName" class="form-control-plaintext fw-bold"></div>
                    </div>
                    <div class="mb-3">
                        <label for="entryQuantity" class="form-label">Quantidade</label>
                        <input type="number" id="entryQuantity" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="entrySupplier" class="form-label">Fornecedor</label>
                        <select id="entrySupplier" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach($fornecedores as $fornecedor): ?>
                            <option value="<?= $fornecedor['id'] ?>"><?= htmlspecialchars($fornecedor['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="entryNotes" class="form-label">Observação</label>
                        <textarea id="entryNotes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="estoqueManager.registerEntry()">
                    Registrar Entrada
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Produto -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Novo Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="productForm" data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_produto'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="productId">
                    <div class="mb-3">
                        <label for="productName" class="form-label">Nome</label>
                        <input type="text" id="productName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="productCategory" class="form-label">Categoria</label>
                        <select id="productCategory" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach($todas_categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">A categoria é compartilhada com relatórios; reclassificar altera o agrupamento das vendas históricas.</div>
                    </div>
                    <div class="mb-3">
                        <label for="productPrice" class="form-label">Preço</label>
                        <input type="number" id="productPrice" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="productMinStock" class="form-label">Estoque Mínimo</label>
                        <input type="number" id="productMinStock" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="productInitialStock" class="form-label">Estoque Inicial</label>
                        <input type="number" id="productInitialStock" class="form-control" min="0" value="0">
                    </div>
                    <hr>
                    <h6>Apresentação no cardápio público</h6>
                    <p class="form-text">Nome e descrição são informativos. Preço e categoria continuam vindo do produto no caixa. Se houver opções relacionadas, elas aparecem no lugar do card principal.</p>
                    <div class="mb-3">
                        <label for="productPublicName" class="form-label">Nome público (opcional)</label>
                        <input type="text" id="productPublicName" class="form-control" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="productPublicDescription" class="form-label">Descrição pública (opcional)</label>
                        <textarea id="productPublicDescription" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="productImage" class="form-label">Imagem do produto (opcional, JPG/PNG/GIF/WebP, até 5 MB)</label>
                        <input type="file" id="productImage" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opções relacionadas (informativas; compartilham produto, preço e estoque)</label>
                        <div id="productOptions"></div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="estoqueManager.addProductOption()">
                            Adicionar opção
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="estoqueManager.saveProduct()">
                    Salvar Produto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Inventário Físico -->
<div class="modal fade" id="inventarioModal" tabindex="-1" aria-labelledby="inventarioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="inventarioModalLabel">
                    Registrar inventário físico
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="registrar_inventario">
                <div class="modal-body">
                    
                    <!-- Campo de Pesquisa -->
<div class="mb-3">
    <label for="pesquisaProduto" class="form-label">
        Pesquisar produto
    </label>
    <input type="text" id="pesquisaProduto" class="form-control" 
           placeholder="Digite o nome do produto para filtrar..." 
           onkeyup="filtrarProdutos()"
           oninput="filtrarProdutos()">
    <div class="form-text">
        Digite para filtrar a lista de produtos em tempo real
    </div>
</div>
                    
                    <!-- Select de Produto com Opções Filtradas -->
                    <div class="mb-3">
                        <label for="produto_id" class="form-label">
                            Produto *
                        </label>
                        <select id="produto_id" name="produto_id" class="form-select" required 
                                onchange="atualizarInfoEstoque()" size="6">
                            <option value="">Selecione um produto...</option>
                            <?php foreach($produtos as $produto): ?>
                            <option value="<?= $produto['id'] ?>" 
                                    data-estoque-atual="<?= $produto['estoque_atual'] ?>"
                                    data-nome="<?= htmlspecialchars(strtolower($produto['nome'])) ?>">
                                <?= htmlspecialchars($produto['nome']) ?> 
                                (Estoque: <?= $produto['estoque_atual'] ?> | 
                                Categoria: <?= htmlspecialchars($produto['categoria_nome']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Use a pesquisa acima para encontrar rapidamente o produto
                        </div>
                    </div>
                    
                    <!-- Informações do Estoque Atual -->
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <div>
                                <strong>Estoque atual no sistema: <span id="estoque_atual_sistema">-</span></strong>
                                <br>
                                <small>Quantidade registrada no sistema</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quantidade Física -->
                    <div class="mb-3">
                        <label for="quantidade_fisica" class="form-label">
                            Quantidade física contada *
                        </label>
                        <input type="number" id="quantidade_fisica" name="quantidade_fisica" 
                               class="form-control" min="0" required 
                               oninput="calcularDiferenca()"
                               placeholder="Digite a quantidade contada fisicamente">
                    </div>
                    
                    <!-- Diferenca Calculada -->
                    <div class="alert alert-warning" id="diferencaAlert">
                        <div class="d-flex align-items-center">
                            <div>
                                <strong>Diferença: <span id="diferenca_calculada">0</span></strong>
                                <br>
                                <small id="diferencaDescricao">Quantidade física - Estoque sistema</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observação -->
                    <div class="mb-3">
                        <label for="observacao" class="form-label">
                            Observação
                        </label>
                        <textarea id="observacao" name="observacao" class="form-control" rows="3" 
                                  placeholder="Ex: Conferência mensal, ajuste de quebra, produto vencido, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        Registrar inventário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Estoque Manager -->
<script src="js/estoque-manager-fixed.js?v=<?= time() ?>"></script>

<script>
window.estoqueManager.apiUrl = <?= json_encode(rtrim(PathConfig::api(), '/')) ?>;
window.estoqueManager.csrfToken = <?= json_encode($_SESSION['csrf_produto']) ?>;
</script>

<script>
// Funções para Inventário Físico
function abrirModalInventario() {
    const modal = new bootstrap.Modal(document.getElementById('inventarioModal'));
    modal.show();
    // Focar no campo de pesquisa
    setTimeout(() => {
        document.getElementById('pesquisaProduto').focus();
    }, 500);
}

function abrirModalInventarioProduto(produtoId) {
    const produtoSelect = document.getElementById('produto_id');
    produtoSelect.value = produtoId;
    atualizarInfoEstoque();
    
    const modal = new bootstrap.Modal(document.getElementById('inventarioModal'));
    modal.show();
}

// FUNÇÃO DE PESQUISA DE PRODUTOS
// FUNÇÃO DE PESQUISA SIMPLES E EFETIVA
function filtrarProdutos() {
    const termo = document.getElementById('pesquisaProduto').value.toLowerCase().trim();
    const select = document.getElementById('produto_id');
    const options = select.getElementsByTagName('option');
    
    let resultadosEncontrados = 0;
    
    for (let i = 1; i < options.length; i++) {
        const textoProduto = options[i].textContent.toLowerCase();
        
        if (termo === '' || textoProduto.includes(termo)) {
            options[i].style.display = '';
            resultadosEncontrados++;
            
            // Destacar texto encontrado
            if (termo !== '') {
                const regex = new RegExp(termo, 'gi');
                const novoTexto = options[i].textContent.replace(regex, match => `<mark>${match}</mark>`);
                options[i].innerHTML = novoTexto;
            } else {
                // Restaurar texto original quando não há pesquisa
                options[i].innerHTML = options[i].textContent;
            }
        } else {
            options[i].style.display = 'none';
        }
    }
    
    // Feedback visual
    if (termo !== '' && resultadosEncontrados === 0) {
        console.log(`Nenhum produto encontrado para: ${termo}`);
    }
}

// Função para criar mensagem de resultados
function criarMensagemResultados() {
    const mensagem = document.createElement('div');
    mensagem.id = 'mensagemResultados';
    mensagem.className = 'alert alert-warning mt-2';
    mensagem.style.display = 'none';
    
    const selectContainer = document.getElementById('produto_id').parentNode;
    selectContainer.appendChild(mensagem);
    
    return mensagem;
}

function atualizarInfoEstoque() {
    const produtoSelect = document.getElementById('produto_id');
    const selectedOption = produtoSelect.options[produtoSelect.selectedIndex];
    
    if (!selectedOption || selectedOption.value === '') {
        document.getElementById('estoque_atual_sistema').textContent = '-';
        document.getElementById('quantidade_fisica').placeholder = 'Digite a quantidade contada fisicamente';
        calcularDiferenca();
        return;
    }
    
    const estoqueAtual = selectedOption.getAttribute('data-estoque-atual');
    const produtoNome = selectedOption.textContent.split(' (')[0]; // Pega apenas o nome
    
    document.getElementById('estoque_atual_sistema').textContent = estoqueAtual || '0';
    
    // Atualizar placeholder da quantidade física
    const qtdFisicaInput = document.getElementById('quantidade_fisica');
    qtdFisicaInput.placeholder = `Quantidade contada de ${produtoNome}`;
    
    calcularDiferenca();
}

function calcularDiferenca() {
    const estoqueSistema = parseInt(document.getElementById('estoque_atual_sistema').textContent) || 0;
    const quantidadeFisica = parseInt(document.getElementById('quantidade_fisica').value) || 0;
    const diferenca = quantidadeFisica - estoqueSistema;
    
    const diferencaElement = document.getElementById('diferenca_calculada');
    const diferencaAlert = document.getElementById('diferencaAlert');
    const diferencaDescricao = document.getElementById('diferencaDescricao');
    
    diferencaElement.textContent = diferenca;
    
    // Estilização dinâmica baseada na diferença
    if (diferenca > 0) {
        // Diferença positiva (sobra)
        diferencaElement.className = 'text-success fw-bold';
        diferencaAlert.className = 'alert alert-success';
        diferencaDescricao.textContent = `Sobra: ${diferenca} unidades a mais que o sistema`;
        diferencaElement.innerHTML = `+${diferenca}`;
    } else if (diferenca < 0) {
        // Diferença negativa (falta)
        diferencaElement.className = 'text-danger fw-bold';
        diferencaAlert.className = 'alert alert-danger';
        diferencaDescricao.textContent = `Falta: ${Math.abs(diferenca)} unidades a menos que o sistema`;
        diferencaElement.innerHTML = diferenca;
    } else {
        // Sem diferença
        diferencaElement.className = 'text-muted';
        diferencaAlert.className = 'alert alert-info';
        diferencaDescricao.textContent = 'Quantidades iguais - Sem diferenças';
        diferencaElement.innerHTML = '0';
    }
}

// Limpar pesquisa e restaurar lista quando modal fechar
document.getElementById('inventarioModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('pesquisaProduto').value = '';
    
    // Restaurar todos os produtos visíveis
    const select = document.getElementById('produto_id');
    const options = select.getElementsByTagName('option');
    
    for (let i = 0; i < options.length; i++) {
        options[i].style.display = '';
        // Restaurar texto original se foi modificado
        const textoOriginal = options[i].getAttribute('data-texto-original');
        if (textoOriginal) {
            options[i].textContent = textoOriginal;
        }
    }
    
    // Esconder mensagem de resultados
    const mensagemResultados = document.getElementById('mensagemResultados');
    if (mensagemResultados) {
        mensagemResultados.style.display = 'none';
    }
});

// Inicializar textos originais quando modal abrir
document.getElementById('inventarioModal').addEventListener('show.bs.modal', function () {
    const select = document.getElementById('produto_id');
    const options = select.getElementsByTagName('option');
    
    for (let i = 0; i < options.length; i++) {
        if (!options[i].getAttribute('data-texto-original')) {
            options[i].setAttribute('data-texto-original', options[i].textContent);
        }
    }
    
    // Focar no campo de pesquisa
    setTimeout(() => {
        document.getElementById('pesquisaProduto').focus();
    }, 500);
});

// Função para toggle dos alertas
function toggleAlertaEstoque() {
    const conteudo = document.getElementById('conteudoAlerta');
    const btn = document.getElementById('btnToggleAlerta');
    
    if (conteudo.style.display === 'none') {
        conteudo.style.display = 'block';
        btn.textContent = 'Recolher';
        btn.title = 'Minimizar alertas';
    } else {
        conteudo.style.display = 'none';
        btn.textContent = 'Expandir';
        btn.title = 'Expandir alertas';
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    estoqueManager.init();
    
    // Event listeners para inventário
    document.getElementById('quantidade_fisica')?.addEventListener('input', calcularDiferenca);
    
    // Enter na pesquisa foca no select
    document.getElementById('pesquisaProduto')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('produto_id').focus();
        }
    });
});
</script>

<style>
.stock-alert { border-left: 4px solid #f39c12; background: #fff3cd; }
.stock-critical { border-left: 4px solid #e74c3c; background: #f8d7da; }
.product-card { transition: transform 0.2s; border: 1px solid #dee2e6; }
.product-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.low-stock { color: #f39c12; font-weight: bold; }
.no-stock { color: #e74c3c; font-weight: bold; }
.status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
.status-normal { background: #d4edda; color: #155724; }
.status-low { background: #fff3cd; color: #856404; }
.status-critical { background: #f8d7da; color: #721c24; }

/* Alertas de estoque - botões visíveis */
#alertaEstoque .btn {
    z-index: 10;
    position: relative;
    background: white;
    border: 1px solid #0d6efd;
    color: #0d6efd;
}

#alertaEstoque .btn:hover {
    background: #0d6efd;
    color: white;
}

#alertaEstoque .btn-outline-primary {
    border-color: #0d6efd;
    color: #0d6efd;
    background: white;
}

#alertaEstoque .btn-outline-primary:hover {
    background: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

/* Estilos para os botões com emojis */
.btn-group-sm .btn {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
}

/* Destaque para itens pesquisados */
mark {
    background-color: #fff3cd;
    padding: 0.1rem 0.2rem;
    border-radius: 0.2rem;
}

/* Select estilizado */
#produto_id {
    min-height: 150px;
}

#produto_id option {
    padding: 0.5rem;
    border-bottom: 1px solid #f8f9fa;
}

#produto_id option:hover {
    background-color: #e9ecef;
}

/* Alertas dinâmicos */
.alert-success {
    border-left: 4px solid #28a745;
}

.alert-danger {
    border-left: 4px solid #dc3545;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}

/* Status badges melhorados */
.status-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 50rem;
    font-size: 0.75em;
    font-weight: 600;
}

.status-normal { 
    background: #d1edff; 
    color: #004085; 
    border: 1px solid #b3d7ff;
}

.status-low { 
    background: #fff3cd; 
    color: #856404; 
    border: 1px solid #ffeaa7;
}

.status-critical { 
    background: #f8d7da; 
    color: #721c24; 
    border: 1px solid #f1b0b7;
}

/* Hover effects nos botões */
.btn-outline-primary:hover { background-color: #0d6efd; color: white; }
.btn-outline-success:hover { background-color: #198754; color: white; }
.btn-outline-info:hover { background-color: #0dcaf0; color: white; }
.btn-outline-warning:hover { background-color: #ffc107; color: black; }
</style>
<?php require_once '../../includes/footer.php'; ?>