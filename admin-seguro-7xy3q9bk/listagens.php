<?php
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagens - Cardápio JNR</title>
    <style>
    /* ESTILOS GERAIS */
    :root {
        --bg-dark: #1a1a2e;
        --bg-panel: #16213e;
        --text-primary: #e6e6e6;
        --text-secondary: #a9a9a9;
        --accent-color: #4a6fa5;
        --border-color: #2c3e50;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --card-bg: rgba(30, 30, 46, 0.8);
        --danger-color: #f44336;
        --inactive-color: #6c757d;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--bg-dark);
        color: var(--text-primary);
        background-image: linear-gradient(to bottom right, #1a1a2e, #16213e, #0f3460);
        min-height: 100vh;
        line-height: 1.6;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .page-title {
        color: var(--text-primary);
        margin: 0 0 20px 0;
        font-size: 2.5rem;
        background: linear-gradient(to right, #4a6fa5, #7eb4e2);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    /* ESTILOS DOS CARDS - IGUAL A FORMULARIOS.PHP */
    .card {
        background-color: var(--card-bg);
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 25px;
        border-left: 5px solid var(--accent-color);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
    }
    
    .card-title {
        margin-top: 0;
        margin-bottom: 25px;
        font-size: 1.5rem;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }
    
    /* ESTILOS ESPECÍFICOS DAS LISTAGENS */
    .item-list {
        border: 1px solid var(--border-color);
        border-radius: 5px;
        overflow: hidden;
    }
    
    .item-row {
        display: grid;
        grid-template-columns: 60px 2fr 1fr 80px 80px;
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        align-items: center;
    }
    
    .item-row.item-header {
        background-color: var(--bg-panel);
        font-weight: bold;
        color: var(--accent-color);
    }
    
    .item-row:nth-child(even) {
        background-color: rgba(40, 40, 60, 0.5);
    }
    
    .item-row.inactive {
        opacity: 0.7;
        background-color: rgba(108, 117, 125, 0.2);
    }
    
    /* BOTÕES */
    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-primary {
        background: linear-gradient(to right, #4a6fa5, #5a86c1);
        color: white;
        box-shadow: 0 4px 10px rgba(74, 111, 165, 0.4);
    }
    
    .btn-primary:hover {
        background: linear-gradient(to right, #3a5a8c, #4a6fa5);
        box-shadow: 0 6px 15px rgba(74, 111, 165, 0.6);
        transform: translateY(-2px);
    }
    
    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 16px;
        padding: 5px 10px;
        transition: all 0.3s;
    }
    
    .delete-btn {
        color: var(--danger-color);
    }
    
    .toggle-btn {
        color: var(--warning-color);
    }
    
    .active-btn {
        color: var(--success-color);
    }
    
    .action-btn:hover {
        transform: scale(1.2);
    }
    
    .action-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* BOTÃO VOLTAR */
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-back.highlight {
        color: #ffffff;
        background-color: rgb(35, 25, 92);
        padding: 8px 16px;
        border-radius: 4px;
    }
    
    .btn-back.highlight:hover {
        background-color: rgb(45, 35, 110);
    }
    
    /* FILTROS */
    .filter-section {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .filter-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    
    .filter-select, .filter-input {
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid var(--border-color);
        background-color: var(--bg-panel);
        color: var(--text-primary);
        min-width: 200px;
    }
    
    /* MENSAGENS E TOAST */
    .toast {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 24px;
        border-radius: 4px;
        color: white;
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 1000;
        background-color: var(--bg-panel);
        border-left: 4px solid;
    }
    
    .toast.success {
        border-left-color: var(--success-color);
    }
    
    .toast.error {
        border-left-color: var(--danger-color);
    }
    
    .toast.warning {
        border-left-color: var(--warning-color);
    }
    
    .toast.show {
        opacity: 1;
        bottom: 30px;
    }
    
    .fa-spinner {
        animation: fa-spin 1s infinite linear;
    }
    
    @keyframes fa-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="page-title">Listagens - Cardápio JNR</h1>
        </div>

        <!-- Filtros -->
        <div class="card">
            <h2 class="card-title">🔍 Filtros</h2>
            <div class="filter-section">
                <div class="filter-group">
                    <label for="category" class="filter-label">Categoria</label>
                    <select id="category" class="filter-select" onchange="filterItems()">
                        <option value="all">Todas as categorias</option>
                        <option value="espetos">Espetos</option>
                        <option value="porcoes">Porções</option>
                        <option value="bebidas">Bebidas</option>
                        <option value="cervejas">Cervejas</option>
                        <option value="opcoes_buffet">Opções de Buffet</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status" class="filter-label">Status</label>
                    <select id="status" class="filter-select" onchange="filterItems()">
                        <option value="all">Todos os status</option>
                        <option value="active">Ativos no cardápio</option>
                        <option value="inactive">Ocultos do cardápio</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search" class="filter-label">Buscar</label>
                    <input type="text" id="search" class="filter-input" placeholder="Digite para buscar..." onkeyup="filterItems()">
                </div>
            </div>
        </div>

        <!-- Seção de Espetos -->
        <div class="card" id="espetos-card"> 
            <h2 class="card-title">📋 Espetos</h2>
            <div class="item-list">
                <div class="item-row item-header">
                    <div>ID</div>
                    <div>Tipo de Carne</div>
                    <div>Preço</div>
                    <div>Status</div>
                    <div>Ações</div>
                </div>
                <?php
                $res = $conn->query("SELECT * FROM espetos ORDER BY ativo DESC, tipo_carne ASC");
                while ($row = $res->fetch_assoc()) {
                    $activeClass = $row['ativo'] ? '' : 'inactive';
                    $activeStatus = $row['ativo'] ? 'Ativo' : 'Oculto';
                    $activeIcon = $row['ativo'] ? 'fa-eye-slash' : 'fa-eye';
                    $activeTitle = $row['ativo'] ? 'Ocultar do cardápio' : 'Mostrar no cardápio';
                    
                    echo '
                    <div class="item-row '.$activeClass.'" data-category="espetos" data-status="'.($row['ativo'] ? 'active' : 'inactive').'" data-search="'.htmlspecialchars($row['tipo_carne']).'">
                        <div>'.$row['id_espeto'].'</div>
                        <div>'.htmlspecialchars($row['tipo_carne']).'</div>
                        <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                        <div>'.$activeStatus.'</div>
                        <div class="action-buttons">
                            <button class="action-btn toggle-btn" onclick="toggleItem('.$row['id_espeto'].', \'espetos\', \'id_espeto\', this)" title="'.$activeTitle.'">
                                <i class="fas '.$activeIcon.'"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="confirmDelete('.$row['id_espeto'].', \'espetos\', \'id_espeto\')" title="Excluir permanentemente">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>';
                }
                ?>
            </div>
        </div>

        <!-- Seção de Opções de Buffet -->
        <div class="card" id="opcoes_buffet-card">
            <h2 class="card-title">📋 Opções de Buffet</h2>
            <div class="item-list">
                <div class="item-row item-header">
                    <div>ID</div>
                    <div>Nome</div>
                    <div>Descrição</div>
                    <div>Status</div>
                    <div>Ações</div>
                </div>
                <?php
                $res = $conn->query("SELECT * FROM opcoes_buffet ORDER BY ativo DESC, nome ASC");
                while ($row = $res->fetch_assoc()) {
                    $activeClass = $row['ativo'] ? '' : 'inactive';
                    $activeStatus = $row['ativo'] ? 'Ativo' : 'Oculto';
                    $activeIcon = $row['ativo'] ? 'fa-eye-slash' : 'fa-eye';
                    $activeTitle = $row['ativo'] ? 'Ocultar do cardápio' : 'Mostrar no cardápio';
                    
                    echo '
                    <div class="item-row '.$activeClass.'" data-category="opcoes_buffet" data-status="'.($row['ativo'] ? 'active' : 'inactive').'" data-search="'.htmlspecialchars($row['nome']).'">
                        <div>'.$row['id_opcao'].'</div>
                        <div>'.htmlspecialchars($row['nome']).'</div>
                        <div>'.htmlspecialchars($row['descricao']).'</div>
                        <div>'.$activeStatus.'</div>
                        <div class="action-buttons">
                            <button class="action-btn toggle-btn" onclick="toggleItem('.$row['id_opcao'].', \'opcoes_buffet\', \'id_opcao\', this)" title="'.$activeTitle.'">
                                <i class="fas '.$activeIcon.'"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="confirmDelete('.$row['id_opcao'].', \'opcoes_buffet\', \'id_opcao\')" title="Excluir permanentemente">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>';
                }
                ?>
            </div>
        </div>

        <!-- Seção de Porções -->
        <div class="card" id="porcoes-card">
            <h2 class="card-title">📋 Porções</h2>
            <div class="item-list">
                <div class="item-row item-header">
                    <div>ID</div>
                    <div>Nome (Tamanho)</div>
                    <div>Preço</div>
                    <div>Status</div>
                    <div>Ações</div>
                </div>
                <?php
                $res = $conn->query("SELECT * FROM porcoes ORDER BY ativo DESC, nome ASC");
                while ($row = $res->fetch_assoc()) {
                    $activeClass = $row['ativo'] ? '' : 'inactive';
                    $activeStatus = $row['ativo'] ? 'Ativo' : 'Oculto';
                    $activeIcon = $row['ativo'] ? 'fa-eye-slash' : 'fa-eye';
                    $activeTitle = $row['ativo'] ? 'Ocultar do cardápio' : 'Mostrar no cardápio';
                    
                    echo '
                    <div class="item-row '.$activeClass.'" data-category="porcoes" data-status="'.($row['ativo'] ? 'active' : 'inactive').'" data-search="'.htmlspecialchars($row['nome']).'">
                        <div>'.$row['id_porcao'].'</div>
                        <div>'.htmlspecialchars($row['nome']).' ('.htmlspecialchars($row['tamanho']).')</div>
                        <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                        <div>'.$activeStatus.'</div>
                        <div class="action-buttons">
                            <button class="action-btn toggle-btn" onclick="toggleItem('.$row['id_porcao'].', \'porcoes\', \'id_porcao\', this)" title="'.$activeTitle.'">
                                <i class="fas '.$activeIcon.'"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="confirmDelete('.$row['id_porcao'].', \'porcoes\', \'id_porcao\')" title="Excluir permanentemente">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>';
                }
                ?>
            </div>
        </div>

        <!-- Seção de Bebidas -->
        <div class="card" id="bebidas-card">
            <h2 class="card-title">📋 Bebidas</h2>
            <div class="item-list">
                <div class="item-row item-header">
                    <div>ID</div>
                    <div>Nome (Tamanho)</div>
                    <div>Preço</div>
                    <div>Status</div>
                    <div>Ações</div>
                </div>
                <?php
                $res = $conn->query("SELECT * FROM bebidas ORDER BY ativo DESC, nome ASC");
                while ($row = $res->fetch_assoc()) {
                    $activeClass = $row['ativo'] ? '' : 'inactive';
                    $activeStatus = $row['ativo'] ? 'Ativo' : 'Oculto';
                    $activeIcon = $row['ativo'] ? 'fa-eye-slash' : 'fa-eye';
                    $activeTitle = $row['ativo'] ? 'Ocultar do cardápio' : 'Mostrar no cardápio';
                    
                    echo '
                    <div class="item-row '.$activeClass.'" data-category="bebidas" data-status="'.($row['ativo'] ? 'active' : 'inactive').'" data-search="'.htmlspecialchars($row['nome']).'">
                        <div>'.$row['id_bebida'].'</div>
                        <div>'.htmlspecialchars($row['nome']).' ('.htmlspecialchars($row['tamanho_ml']).'ml)</div>
                        <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                        <div>'.$activeStatus.'</div>
                        <div class="action-buttons">
                            <button class="action-btn toggle-btn" onclick="toggleItem('.$row['id_bebida'].', \'bebidas\', \'id_bebida\', this)" title="'.$activeTitle.'">
                                <i class="fas '.$activeIcon.'"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="confirmDelete('.$row['id_bebida'].', \'bebidas\', \'id_bebida\')" title="Excluir permanentemente">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>';
                }
                ?>
            </div>
        </div>

        <!-- Seção de Cervejas -->
        <div class="card" id="cervejas-card">
            <h2 class="card-title">📋 Cervejas</h2>
            <div class="item-list">
                <div class="item-row item-header">
                    <div>ID</div>
                    <div>Marca (Tamanho)</div>
                    <div>Preço</div>
                    <div>Status</div>
                    <div>Ações</div>
                </div>
                <?php
                $res = $conn->query("SELECT * FROM cervejas ORDER BY ativo DESC, marca ASC");
                while ($row = $res->fetch_assoc()) {
                    $activeClass = $row['ativo'] ? '' : 'inactive';
                    $activeStatus = $row['ativo'] ? 'Ativo' : 'Oculto';
                    $activeIcon = $row['ativo'] ? 'fa-eye-slash' : 'fa-eye';
                    $activeTitle = $row['ativo'] ? 'Ocultar do cardápio' : 'Mostrar no cardápio';
                    
                    echo '
                    <div class="item-row '.$activeClass.'" data-category="cervejas" data-status="'.($row['ativo'] ? 'active' : 'inactive').'" data-search="'.htmlspecialchars($row['marca']).'">
                        <div>'.$row['id_cerveja'].'</div>
                        <div>'.htmlspecialchars($row['marca']).' ('.htmlspecialchars($row['tamanho_ml']).'ml)</div>
                        <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                        <div>'.$activeStatus.'</div>
                        <div class="action-buttons">
                            <button class="action-btn toggle-btn" onclick="toggleItem('.$row['id_cerveja'].', \'cervejas\', \'id_cerveja\', this)" title="'.$activeTitle.'">
                                <i class="fas '.$activeIcon.'"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="confirmDelete('.$row['id_cerveja'].', \'cervejas\', \'id_cerveja\')" title="Excluir permanentemente">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <a href="painel.php" class="btn-action btn-back highlight">Voltar</a>
        </div>
    </div>

    <script>
    // Função para filtrar itens
    function filterItems() {
        const category = document.getElementById('category').value;
        const status = document.getElementById('status').value;
        const searchTerm = document.getElementById('search').value.toLowerCase();
        
        document.querySelectorAll('.item-row:not(.item-header)').forEach(row => {
            const rowCategory = row.getAttribute('data-category');
            const rowStatus = row.getAttribute('data-status');
            const rowSearch = row.getAttribute('data-search').toLowerCase();
            
            const categoryMatch = category === 'all' || rowCategory === category;
            const statusMatch = status === 'all' || rowStatus === status;
            const searchMatch = rowSearch.includes(searchTerm);
            
            if (categoryMatch && statusMatch && searchMatch) {
                row.style.display = 'grid';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Mostrar/ocultar cards baseado no filtro de categoria
        document.querySelectorAll('.card[id$="-card"]').forEach(card => {
            const cardCategory = card.id.replace('-card', '');
            
            if (category === 'all' || cardCategory === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Função para confirmar exclusão
    function confirmDelete(id, table, idColumn) {
        if (confirm('Tem certeza que deseja excluir permanentemente este item?\nEsta ação não pode ser desfeita.')) {
            deleteItem(id, table, idColumn);
        }
    }
    
    // Função para excluir item
    function deleteItem(id, table, idColumn) {
        const btn = event.target.closest('.delete-btn');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';
        btn.disabled = true;
        
        fetch('delete_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: id,
                table: table,
                idColumn: idColumn
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na rede: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showToast('Item excluído com sucesso!', 'success');
                // Remove a linha da tabela após 1 segundo
                setTimeout(() => {
                    btn.closest('.item-row').remove();
                }, 1000);
            } else {
                throw new Error(data.message || 'Erro ao excluir');
            }
        })
        .catch(error => {
            showToast('Erro: ' + error.message, 'error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        });
    }
    
    // Função para alternar status do item (ativo/inativo)
function toggleItem(id, table, idColumn, button) {
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';
    button.disabled = true;
    
    // Adiciona timeout para evitar travamento infinito
    const timeout = setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalContent;
        showToast('O servidor demorou muito para responder', 'error');
    }, 10000); // 10 segundos de timeout

    fetch('toggle_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            id: id,
            table: table,
            idColumn: idColumn
        })
    })
    .then(response => {
        clearTimeout(timeout); // Cancela o timeout
        
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }
        
        return response.json().catch(() => {
            throw new Error('Resposta não é JSON válido');
        });
    })
    .then(data => {
        if (!data || typeof data.success === 'undefined') {
            throw new Error('Resposta do servidor inválida');
        }
        
        if (data.success) {
            const row = button.closest('.item-row');
            const statusCell = row.querySelector('div:nth-child(4)');
            const isActive = data.newStatus == 1;
            
            // Atualiza a interface
            button.innerHTML = isActive ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            button.title = isActive ? 'Ocultar do cardápio' : 'Mostrar no cardápio';
            row.classList.toggle('inactive', !isActive);
            statusCell.textContent = isActive ? 'Ativo' : 'Oculto';
            row.setAttribute('data-status', isActive ? 'active' : 'inactive');
            
            showToast(isActive ? 'Item ativado no cardápio!' : 'Item ocultado do cardápio!', 
                     isActive ? 'success' : 'warning');
        } else {
            throw new Error(data.message || 'Erro ao alterar status');
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        showToast(error.message, 'error');
        button.innerHTML = originalContent;
    })
    .finally(() => {
        clearTimeout(timeout); // Garante que o timeout seja limpo
        button.disabled = false;
    });
}
    
    // Função para mostrar mensagens toast
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
    </script>
</body>
</html>