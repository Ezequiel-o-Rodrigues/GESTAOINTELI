<?php
// Configuração para subdomínio (raiz)
$base_path = '/';

// Usar caminhos absolutos com __DIR__
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Iniciar a sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// INICIALIZAR VARIÁVEIS COM VALORES PADRÃO
$comanda_aberta = null;
$produtos_por_categoria = [];
$total_comanda = 0;
$categorias = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Buscar TODOS os produtos ativos com suas categorias
    $query = "SELECT p.*, c.nome as categoria_nome, c.id as categoria_id 
              FROM produtos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.ativo = 1 
              ORDER BY c.nome, p.nome";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar produtos por categoria
    foreach ($produtos as $produto) {
        $categoria_id = $produto['categoria_id'];
        if (!isset($produtos_por_categoria[$categoria_id])) {
            $produtos_por_categoria[$categoria_id] = [
                'categoria_nome' => $produto['categoria_nome'],
                'produtos' => []
            ];
        }
        $produtos_por_categoria[$categoria_id]['produtos'][] = $produto;
    }

    // Buscar comanda aberta atual
    $query_comanda = "SELECT * FROM comandas WHERE status = 'aberta' ORDER BY id DESC LIMIT 1";
    $stmt_comanda = $db->prepare($query_comanda);
    $stmt_comanda->execute();
    $comanda_aberta = $stmt_comanda->fetch(PDO::FETCH_ASSOC);
    
    // Definir total da comanda
    if ($comanda_aberta) {
        $total_comanda = number_format($comanda_aberta['valor_total'] ?? 0, 2, ',', '.');
    }

    // BUSCAR CATEGORIAS PARA O SELECT
    $query_categorias = "SELECT * FROM categorias ORDER BY nome";
    $stmt_categorias = $db->prepare($query_categorias);
    $stmt_categorias->execute();
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Log do erro sem quebrar a aplicação
    error_log("Erro ao carregar dados: " . $e->getMessage());
    $produtos_por_categoria = [];
    $comanda_aberta = null;
    $total_comanda = '0,00';
    $categorias = [];
}
  // BUSCAR GARÇONS ATIVOS
    $query_garcons = "SELECT id, nome, codigo FROM garcons WHERE ativo = 1 ORDER BY codigo";
    $stmt_garcons = $db->prepare($query_garcons);
    $stmt_garcons->execute();
    $garcons = $stmt_garcons->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caixa - Sistema Restaurante</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            height: 100vh;
        }

        /* CABEÇALHO */
        .mini-header {
            background: #2c3e50;
            color: white;
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
        }

        .mini-header h1 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .btn-voltar {
            background: #34495e;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
        }

        /* COMANDA HORIZONTAL - NOVO LAYOUT */
        .comanda-horizontal {
            background: white;
            border-bottom: 2px solid #3498db;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            height: 70px;
            flex-shrink: 0;
        }

        .comanda-info-horizontal {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 120px;
        }

        .comanda-numero {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1rem;
        }

        .itens-comanda-horizontal {
            flex: 1;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 5px 0;
            min-height: 50px;
            align-items: center;
        }

        .item-comanda-horizontal {
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 20px;
            padding: 6px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .item-nome {
            font-weight: 600;
            color: #2c3e50;
        }

        .item-quantidade {
            background: #3498db;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .item-quantidade:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        .item-preco {
            font-weight: bold;
            color: #27ae60;
        }

        .btn-remover {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            cursor: pointer;
            padding: 0;
        }

        .btn-remover:hover {
            background: #c0392b;
        }

        .total-comanda {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1rem;
            min-width: 100px;
            text-align: right;
        }

        .empty-comanda {
            color: #95a5a6;
            font-style: italic;
            font-size: 0.85rem;
        }

        /* BOTÕES DE AÇÃO */
        .botoes-comanda {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            height: 32px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        /* CONTEÚDO PRINCIPAL - MAIS ESPAÇO PARA PRODUTOS */
        .conteudo-principal {
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        /* FILTROS */
        .filtros-container {
            background: white;
            padding: 8px 12px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 150px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.85rem;
            height: 32px;
        }

        .categoria-filtro {
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.85rem;
            background: white;
            height: 32px;
            min-width: 120px;
        }

        .contador-produtos {
            color: #7f8c8d;
            font-size: 0.8rem;
            white-space: nowrap;
        }

       /* PRODUTOS - NOVO ESTILO COM CORES POR CATEGORIA */
.produtos-scroll-container {
    flex: 1;
    overflow-y: auto;
    padding: 5px;
    height: 100%;
}

/* Remove os títulos de categoria com fundo azul */
.categoria-produtos {
    margin-bottom: 8px;
}

.categoria-titulo {
    display: none; /* Esconde os títulos azuis */
}

.produtos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
    margin-bottom: 10px;
}

.produto-card {
    padding: 8px;
    border: 0.5px solid; /* Borda mais espessa para o efeito tabela periódica */
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.5s ease;
    background: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 90px;
    position: relative;
    overflow: hidden;
}

/* Efeito de brilho suave no hover */
.produto-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    filter: brightness(1.05);
}

/* Mini indicador de categoria no topo */
.produto-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: currentColor;
    opacity: 0.7;
}

.produto-nome {
    font-weight: 600;
    font-size: 0.75rem;
    line-height: 1.2;
    margin-bottom: 4px;
    color: #000000ff;
    z-index: 1;
}

.produto-preco {
    font-weight: bold;
    font-size: 0.8rem;
    margin: 3px 0;
    z-index: 1;
}

.produto-estoque {
    font-size: 0.7rem;
    z-index: 1;
    color: #007a1bff;
}

.estoque-baixo {
    font-weight: bold;
    color: #ff1900ff;
}

/* CORES POR CATEGORIA - Inspirado na Tabela Periódica */
/* Alimenticio - Verde (como metais alcalinos) */
.produto-card[data-produto-categoria="1"] {
    border-color: #128040ff;
    background: linear-gradient(135deg, #006e0062, #0e8b239c);
    color: #27ae60;
}

.produto-card[data-produto-categoria="1"] .produto-preco {
    color: #229954;
}

/* Bebidas não alcoólicas - Azul (como gases nobres) */
.produto-card[data-produto-categoria="3"] {
    border-color: #3498db;
    background: linear-gradient(135deg, #023a8386, #abccf8ad);
    color: #3498db;
}

.produto-card[data-produto-categoria="3"] .produto-preco {
    color: #2980b9;
}

/* Bebidas alcoólicas - Vermelho/Laranja (como metais de transição) */
.produto-card[data-produto-categoria="4"] {
    border-color: #ff1900ff;
    background: linear-gradient(135deg, #8a040485, #fadbd8);
    color: #e74c3c;
}

.produto-card[data-produto-categoria="4"] .produto-preco {
    color: #cb4335;
}

/* Diversos - Roxo (como lantanídeos) */
.produto-card[data-produto-categoria="5"] {
    border-color: #8e44ad;
    background: linear-gradient(135deg, #faf8ff, #e8daef);
    color: #8e44ad;
}

.produto-card[data-produto-categoria="5"] .produto-preco {
    color: #7d3c98;
}

/* Estoque baixo - destaque adicional */
.produto-card.estoque-baixo {
    animation: pulse-alert 10s infinite;
    border-width: 3px;
}

@keyframes pulse-alert {
    0% { border-color: currentColor; }
    50% { border-color: #e74c3c; }
    100% { border-color: currentColor; }
}


        /* SCROLL HORIZONTAL PARA COMANDA */
        .itens-comanda-horizontal::-webkit-scrollbar {
            height: 4px;
        }

        .itens-comanda-horizontal::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .itens-comanda-horizontal::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .comanda-horizontal {
                flex-direction: column;
                height: auto;
                padding: 8px;
                gap: 8px;
            }
            
            .itens-comanda-horizontal {
                order: 2;
                width: 100%;
            }
            
            .botoes-comanda {
                order: 1;
                width: 100%;
                justify-content: space-between;
            }
            
            .produtos-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }

        .btn-garcom {
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .btn-garcom:hover {
            background: #e3f2fd;
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }

        .btn-garcom:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- CABEÇALHO -->
    <div class="mini-header">
        <h1>💰 Caixa Rápido</h1>
        <a href="<?php echo $base_path; ?>index.php" class="btn-voltar">🏠 Início</a>
    </div>

    <!-- COMANDA HORIZONTAL - NOVA POSIÇÃO -->
    <div class="comanda-horizontal">
        <div class="comanda-info-horizontal">
            <span class="comanda-numero" id="numero-comanda">
                <?php echo $comanda_aberta ? '#' . $comanda_aberta['id'] : '--'; ?>
            </span>
            <button class="btn btn-primary" onclick="novaComanda()">Nova</button>
        </div>
        
        <div class="itens-comanda-horizontal" id="itens-comanda">
            <div class="empty-comanda">
                <?php echo $comanda_aberta ? 'Carregando itens...' : 'Nenhuma comanda aberta'; ?>
            </div>
        </div>
        
        <div class="botoes-comanda">
            <span class="total-comanda" id="total-comanda">
                R$ <?php echo $total_comanda; ?>
            </span>
            <button class="btn btn-success" onclick="finalizarComanda()" id="btn-finalizar" 
                    <?php echo (!$comanda_aberta) ? 'disabled' : ''; ?>>
                💰 Finalizar
            </button>
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL - MAIS ESPAÇO PARA PRODUTOS -->
    <div class="conteudo-principal">
        <div class="filtros-container">
            <input type="text" id="search-produto" class="search-input" placeholder="🔍 Buscar..." onkeyup="filtrarProdutos()">
            <select id="filtro-categoria" class="categoria-filtro" onchange="filtrarProdutos()">
                <option value="">Todas categorias</option>
                <?php foreach($categorias as $categoria): ?>
                <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <span id="contador-produtos" class="contador-produtos"></span>
        </div>

        <!-- Na parte dos produtos (mantenha tudo igual, só observe o data-categoria) -->
<div class="produtos-scroll-container" id="produtos-container">
    <?php if (!empty($produtos_por_categoria)): ?>
        <?php foreach($produtos_por_categoria as $categoria_id => $categoria_data): ?>
        <div class="categoria-produtos" data-categoria="<?= $categoria_id ?>">
            <!-- O título da categoria será escondido pelo CSS -->
            <div class="categoria-titulo" style="display: none;">
                <?= htmlspecialchars($categoria_data['categoria_nome']) ?>
                <span class="contador-categoria">(<?= count($categoria_data['produtos']) ?>)</span>
            </div>
            <div class="produtos-grid">
                <?php foreach($categoria_data['produtos'] as $produto): ?>
                <div class="produto-card" 
                     data-produto-id="<?= $produto['id'] ?>"
                     data-produto-nome="<?= htmlspecialchars($produto['nome']) ?>"
                     data-produto-preco="<?= $produto['preco'] ?>"
                     data-produto-categoria="<?= $categoria_id ?>" 
                     data-produto-estoque="<?= $produto['estoque_atual'] ?>"
                     onclick="adicionarProduto(<?= $produto['id'] ?>, '<?= htmlspecialchars(addslashes($produto['nome'])) ?>', <?= $produto['preco'] ?>)">
                    
                    <div class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></div>
                    <div class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                    <div class="produto-estoque <?= $produto['estoque_atual'] <= $produto['estoque_minimo'] ? 'estoque-baixo' : '' ?>">
                        Est: <?= $produto['estoque_atual'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #7f8c8d;">
            <div style="font-size: 3rem; margin-bottom: 10px;">📦</div>
            <h3>Nenhum produto cadastrado</h3>
            <p>Cadastre produtos no sistema primeiro</p>
        </div>
    <?php endif; ?>
</div>
        <!-- JavaScript -->
    <script>
          // Passar variáveis PHP para JavaScript - VERIFICAR SE JÁ EXISTE
    if (typeof window.appConfig === 'undefined') {
        window.appConfig = {
            comandaAtualId: <?php echo isset($comanda_aberta) && $comanda_aberta ? $comanda_aberta['id'] : 'null'; ?>,
            basePath: '<?php echo $base_path; ?>'
        };
    }
    </script>   

        <!-- === MODAL SELECÃO GARÇOM DINÂMICO - BUSCA DO BD === -->
    <div id="modalGarcom" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 25px; border-radius: 10px; width: 450px; max-width: 90vw; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-height: 80vh; overflow-y: auto;">
            <div style="font-size: 2rem; margin-bottom: 10px;">🛎️</div>
            <h3 style="margin-bottom: 15px; color: #2c3e50;">Selecione o Garçom</h3>
            <p style="color: #7f8c8d; margin-bottom: 25px;">Clique no garçom responsável pelo atendimento:</p>
            
            <!-- Grid de Botões dos Garçons - DINÂMICO -->
            <div id="gridGarcons" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 25px;">
                <?php if (!empty($garcons)): ?>
                    <?php foreach($garcons as $garcom): ?>
                    <button class="btn-garcom" data-garcom="<?= $garcom['codigo'] ?>" onclick="selecionarGarcom('<?= $garcom['codigo'] ?>', '<?= addslashes($garcom['nome']) ?>')">
                        <div style="font-size: 1.5rem; margin-bottom: 5px;">👨‍💼</div>
                        <div style="font-weight: bold; font-size: 1.1rem;"><?= htmlspecialchars($garcom['codigo']) ?></div>
                        <div style="font-size: 0.8rem; color: #7f8c8d;"><?= htmlspecialchars($garcom['nome']) ?></div>
                    </button>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #7f8c8d;">
                        <div style="font-size: 2rem; margin-bottom: 10px;">😕</div>
                        <p>Nenhum garçom cadastrado</p>
                        <button onclick="fecharModalGarcom()" style="background: #95a5a6; color: white; border: none; padding: 8px 16px; border-radius: 4px; margin-top: 10px; cursor: pointer;">
                            Fechar
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div style="border-top: 1px solid #ecf0f1; padding-top: 15px;">
                <button onclick="fecharModalGarcom()" 
                        style="background: #95a5a6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    ❌ Cancelar
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL COMPROVANTE -->
    <div id="modalComprovante" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; width: 400px; max-width: 90vw; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <div style="font-size: 3rem; margin-bottom: 15px; color: #27ae60;">✅</div>
            <h3 style="margin-bottom: 15px; color: #2c3e50;">Venda Finalizada!</h3>
            <p id="totalVenda" style="color: #7f8c8d; margin-bottom: 20px;">Total: <strong>R$ 0,00</strong></p>
            <p style="color: #7f8c8d; margin-bottom: 25px;">Deseja imprimir o comprovante?</p>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button id="btnImprimirComprovante" 
                        style="background: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    🖨️ Imprimir
                </button>
                <button id="btnPularImpressao" 
                        style="background: #95a5a6; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    ❌ Não Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript do Modal -->
    <script>
        // Variável global para armazenar o garçom selecionado
        let garcomSelecionado = null;
        let garcomNomeSelecionado = null;

        function abrirModalGarcom() {
            document.getElementById('modalGarcom').style.display = 'flex';
            garcomSelecionado = null;
            garcomNomeSelecionado = null;
            // Resetar seleção visual
            document.querySelectorAll('.btn-garcom').forEach(btn => {
                btn.style.background = '#f8f9fa';
                btn.style.borderColor = '#ddd';
                btn.style.color = 'inherit';
            });
        }

        function fecharModalGarcom() {
            document.getElementById('modalGarcom').style.display = 'none';
            garcomSelecionado = null;
            garcomNomeSelecionado = null;
        }

        function selecionarGarcom(codigo, nome) {
            garcomSelecionado = codigo;
            garcomNomeSelecionado = nome;
            
            // Efeito visual de seleção
            document.querySelectorAll('.btn-garcom').forEach(btn => {
                if (btn.getAttribute('data-garcom') === codigo) {
                    btn.style.background = '#3498db';
                    btn.style.borderColor = '#2980b9';
                    btn.style.color = 'white';
                } else {
                    btn.style.background = '#f8f9fa';
                    btn.style.borderColor = '#ddd';
                    btn.style.color = 'inherit';
                }
            });
            
            // Criar comanda automaticamente após 300ms (feedback visual)
            setTimeout(() => {
                criarComandaComGarcom();
            }, 300);
        }

        async function criarComandaComGarcom() {
            if (!garcomSelecionado) {
                alert('Selecione um garçom primeiro!');
                return;
            }
            
            try {
                // Fazer requisição direta
                const response = await fetch('criar_comanda.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        garcom: garcomSelecionado
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Atualizar CaixaSystem se disponível
                    if (window.caixaSystem) {
                        window.caixaSystem.comandaAtual = { id: data.comanda_id };
                        window.caixaSystem.itensComanda = [];
                        window.caixaSystem.atualizarUIComanda();
                    }
                    
                    fecharModalGarcom();
                    
                    // Mostrar toast se disponível
                    if (window.caixaSystem && typeof window.caixaSystem.mostrarToast === 'function') {
                        window.caixaSystem.mostrarToast(data.message, 'success');
                    }
                } else {
                    throw new Error(data.message || 'Erro ao criar comanda');
                }
            } catch (error) {
                console.error('Erro ao criar comanda:', error);
                alert('Erro ao criar comanda: ' + error.message);
                fecharModalGarcom();
            }
        }

        // Função novaComanda (chamada pelo botão)
        function novaComanda() {
            // Verificar se já existe uma comanda aberta
            if (window.caixaSystem && window.caixaSystem.comandaAtual) {
                if (confirm('Já existe uma comanda aberta. Deseja cancelá-la e criar uma nova?')) {
                    window.caixaSystem.limparComanda();
                    abrirModalGarcom();
                }
            } else {
                abrirModalGarcom();
            }
        }
    </script>

    <script>
        // Wrapper global para compatibilidade com os elementos inline do template
        // Chamado pelos onclick nos cards: adicionarProduto(produtoId, nome, preco)
        function adicionarProduto(produtoId, nome, preco) {
            if (window.caixaSystem && typeof window.caixaSystem.adicionarItem === 'function') {
                // Delega completamente pro CaixaSystem (que já gerencia confirmação e criação de comanda)
                window.caixaSystem.adicionarItem(produtoId, 1);
                return;
            }

            // Fallback (CaixaSystem ainda não carregou)
            console.warn('CaixaSystem ainda não pronto. Tente novamente em alguns momentos.');
        }

        // Delegar chamadas de busca pra CaixaSystem se existir
        function filtrarProdutos() {
            const el = document.getElementById('search-produto') || document.getElementById('busca-produto');
            const termo = el ? el.value : '';
            if (window.caixaSystem && typeof window.caixaSystem.filtrarProdutos === 'function') {
                window.caixaSystem.filtrarProdutos(termo);
            }
        }

        // Delegar finalização de comanda pro CaixaSystem
        function finalizarComanda() {
            if (window.caixaSystem && typeof window.caixaSystem.finalizarComanda === 'function') {
                window.caixaSystem.finalizarComanda();
            } else {
                alert('Sistema ainda não está pronto. Tente novamente em alguns momentos.');
            }
        }

        // Delegar cancelamento de comanda pro CaixaSystem
        function cancelarComanda() {
            if (window.caixaSystem && typeof window.caixaSystem.cancelarComanda === 'function') {
                window.caixaSystem.cancelarComanda();
            }
        }
    </script>

    <!-- Script de impressão -->
<script src="<?php echo $base_path; ?>modules/caixa/impressao-service.js"></script>

<script>
// Teste de compatibilidade Web USB
document.addEventListener('DOMContentLoaded', function() {
    if (navigator.usb) {
        console.log('Web USB API suportada');
        // Opcional: mostrar status da impressora
        const statusElement = document.createElement('div');
        statusElement.style.cssText = 'position: fixed; bottom: 10px; right: 10px; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; border: 1px solid #dee2e6;';
        statusElement.innerHTML = '🖨️ Web USB Disponível';
        document.body.appendChild(statusElement);
    } else {
        console.warn('Web USB não suportada');
    }
});
</script>

    <!-- Carregamento do CaixaSystem (com proteção contra duplicação) -->
    <script src="<?php echo $base_path; ?>modules/caixa/caixa.js?v=<?= time() ?>"></script>
    
    <script>
    // Event listeners para modal de comprovante
    document.addEventListener('DOMContentLoaded', function() {
        const btnImprimir = document.getElementById('btnImprimirComprovante');
        const btnPular = document.getElementById('btnPularImpressao');
        
        if (btnImprimir) {
            btnImprimir.addEventListener('click', function() {
                const modal = document.getElementById('modalComprovante');
                const comprovanteId = modal ? modal.dataset.comprovanteId : null;
                
                if (comprovanteId && window.caixaSystem) {
                    window.caixaSystem.imprimirComprovante(comprovanteId);
                }
                
                if (window.caixaSystem) {
                    window.caixaSystem.fecharModalComprovante();
                }
            });
        }
        
        if (btnPular) {
            btnPular.addEventListener('click', function() {
                if (window.caixaSystem) {
                    window.caixaSystem.fecharModalComprovante();
                }
            });
        }
    });
    </script>
    
    <script>
// SISTEMA DE EMERGÊNCIA - Garante funcionamento mesmo com versão antiga
console.log('🔧 Verificando serviço de impressão...');

// Aguardar carregamento completo
setTimeout(function() {
    // Se o serviço não existe ou tem métodos USB, substituir
    if (!window.impressaoService || window.impressaoService.conectarImpressora) {
        console.log('🔄 Substituindo serviço problemático...');
        
        // Remover serviço antigo
        if (window.impressaoService) {
            delete window.impressaoService;
        }
        
        // Criar serviço novo
        window.impressaoService = {
            imprimirComprovante: function(conteudo) {
                console.log('🎯 Imprimindo (emergência)...');
                try {
                    const texto = conteudo.replace(/\x1B\[[0-9;]*[A-Za-z]/g, '').replace(/\x0A/g, '\n');
                    const janela = window.open('', '_blank', 'width=400,height=600');
                    if (janela) {
                        janela.document.write('<h3>Espetinho do Junior</h3><pre>' + texto + '</pre><button onclick="window.print()">Imprimir</button>');
                        janela.document.close();
                    }
                    return Promise.resolve({success: true, message: 'Comprovante aberto!'});
                } catch (error) {
                    return Promise.resolve({success: false, message: 'Erro: ' + error.message});
                }
            }
        };
        
        console.log('✅ Serviço de emergência ativado!');
    } else {
        console.log('✅ Serviço OK!');
    }
}, 1000);
</script>
    
</body>
</html>