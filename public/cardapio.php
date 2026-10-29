    <?php
    require_once __DIR__ . '/../includes/conexao.php';

    function getMenuItems($conn, $tabela, $where = '') {
        $tabelas_validas = ['espetos', 'porcoes', 'bebidas', 'cervejas', 'opcoes_buffet'];
        
        if(!in_array($tabela, $tabelas_validas)) {
            die("Nome de tabela inválido!");
        }
        
        $sql = "SELECT * FROM $tabela WHERE ativo = 1";
        if(!empty($where)) {
            $sql .= " AND $where";
        }
        
        $result = $conn->query($sql);
        
        if (!$result) {
            error_log("Erro na consulta de $tabela: " . $conn->error);
            return [];
        }
        
        $items = [];
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    $menuData = [
        'espetos' => getMenuItems($conn, 'espetos'),
        'porcoes' => getMenuItems($conn, 'porcoes'),
        'bebidas' => getMenuItems($conn, 'bebidas'),
        'cervejas' => getMenuItems($conn, 'cervejas'),
        'buffet' => getMenuItems($conn, 'opcoes_buffet', 'ativo = 1')
    ];

    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Espetinho do Júnior</title>
        <link rel="stylesheet" href="estilo.css">
        <link href="https://fonts.googleapis.com/css2?family=Palanquin+Dark:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
        :root {
        --primary-color: #8B0000;
        --secondary-color: #1a1a1a;
        --accent-color: #d4af37;
        --text-color: #333;
        --bg-light: #f8f5f0;
        --bg-dark: #1a1a1a;
    }

    body {
        font-family: 'Roboto', sans-serif;
        color: var(--text-color);
        background-color: var(--bg-light);
        line-height: 1.5;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 95%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 10px;
    }

    /* Header mobile */
    header {
        background: linear-gradient(to right, var(--bg-dark), var(--primary-color));
        color: white;
        padding: 15px 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        text-align: center;
        margin-bottom: 5px;
    }

    .header-subtitle {
        text-align: center;
        font-size: 0.8rem;
        opacity: 0.9;
    }

    /* Seções do cardápio */
    .menu-section {
        margin: 30px 0;
    }

    .section-title {
        color: var(--primary-color);
        font-family: 'Playfair Display', serif;
        border-bottom: 2px solid var(--accent-color);
        padding-bottom: 5px;
        margin-bottom: 20px;
        font-size: 1.4rem;
        text-align: center;
    }

    /* Grid de itens - 3 colunas para mobile */
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .menu-item {
        background: white;
        border-radius: 5px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        position: relative;
    }

    .menu-item:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--accent-color);
    }

    .item-img-container {
        position: relative;
        overflow: hidden;
        height: 100px;
    }

    .item-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .item-special {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: var(--accent-color);
        color: var(--secondary-color);
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.6rem;
        font-weight: bold;
    }

    .item-info {
        padding: 8px;
    }

    .item-name {
        color: var(--secondary-color);
        margin: 0;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .item-price {
        color: var(--accent-color);
        font-weight: bold;
        font-size: 0.9rem;
        margin-top: 5px;
    }

    .item-price:before {
        content: 'R$';
        font-size: 0.7rem;
        margin-right: 2px;
    }

    /* Ajustes para descrições */
    .item-desc {
        font-size: 0.7rem;
        color: #666;
        margin: 5px 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Mostra apenas informações críticas em mobile */
    @media (max-width: 767px) {
        .item-desc {
            display: inline; /* Esconde descrição longa */
        }
        
        /* Mostra informações importantes como ml e tamanhos */
        .item-desc strong {
            display: inline;
        }
        
        .item-desc[style*="display"] {
            display: block !important;
        }

    }

    /* Ajustes para telas muito pequenas */
    @media (max-width: 359px) {
        .menu-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .item-img-container {
            height: 90px;
        }
    }

    /* Tablets - 3 colunas com mais espaço */
    @media (min-width: 768px) {
        .menu-grid {
            gap: 15px;
        }
        .item-img-container {
            height: 130px;
        }
        .item-name {
            font-size: 0.9rem;
        }
        .item-price {
            font-size: 1rem;
        }
    }

    /* Desktops pequenos - 4 colunas */
    @media (min-width: 992px) {
        .menu-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .item-img-container {
            height: 150px;
        }
        .item-info {
            padding: 12px;
        }
        .item-name {
            font-size: 1rem;
        }
    }

    /* Desktops grandes - 5 colunas */
    @media (min-width: 1200px) {
        .menu-grid {
            grid-template-columns: repeat(5, 1fr);
        }
        .item-img-container {
            height: 160px;
        }
    }

    /* Footer mobile */
    footer {
        background: var(--bg-dark);
        color: white;
        padding: 25px 0;
        text-align: center;
    }

    .footer-content {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .footer-logo {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        margin-bottom: 10px;
        color: var(--accent-color);
    }

    .footer-info {
        margin: 8px 0;
        font-size: 0.8rem;
    }

    .social-icons {
        margin-top: 15px;
    }

    .social-icon {
        color: var(--accent-color);
        margin: 0 8px;
        font-size: 1.1rem;
    }

    /* Botão de Delivery mobile */
    #deliveryBtn {
        background-color: var(--accent-color);
        color: var(--secondary-color);
        border: none;
        padding: 10px 15px;
        font-size: 0.9rem;
        border-radius: 20px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 20px auto;
        width: 90%;
        max-width: 280px;
    }

    /* Modal Delivery mobile */
    #deliveryModal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
        z-index: 1000;
    }

    #deliveryModal > div {
        background-color: white;
        margin: 15% auto;
        padding: 15px;
        border-radius: 8px;
        width: 90%;
        max-width: 350px;
        position: relative;
    }

    #closeModal {
        position: absolute;
        right: 12px;
        top: 8px;
        font-size: 20px;
        cursor: pointer;
    }

    #deliveryModal a {
        display: block;
        padding: 10px;
        margin-bottom: 8px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        text-align: center;
        font-size: 0.9rem;
    }

    /* Botão Voltar ao Topo */
    #backToTop {
        display: none;
        position: fixed;
        bottom: 15px;
        right: 15px;
        background-color: var(--accent-color);
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        text-align: center;
        line-height: 36px;
        font-size: 1rem;
        z-index: 99;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    .menu-navegacao {
                position: sticky;
                top: 0;
                background: linear-gradient(to right, var(--bg-dark), var(--primary-color));
                z-index: 1000;
                padding: 10px 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
            
            .menu-navegacao ul {
                display: flex;
                justify-content: center;
                list-style: none;
                padding: 0;
                margin: 0;
                flex-wrap: wrap;
            }
            
            .menu-navegacao li {
                margin: 0 10px;
            }
            
            .menu-navegacao a {
                color: white;
                text-decoration: none;
                font-weight: 500;
                padding: 8px 15px;
                border-radius: 20px;
                transition: all 0.3s;
                font-size: 0.9rem;
            }
            
            .menu-navegacao a:hover,
            .menu-navegacao a:focus {
                background-color: var(--accent-color);
                color: var(--secondary-color);
            }
            
            /* Ajuste o padding do main para o menu fixo */
            main.container {
                padding-top: 20px;
            }
            
            /* Adicione um id para cada seção */
            .menu-section {
                scroll-margin-top: 80px; /* Altura do menu fixo */
            }
            
            @media (max-width: 768px) {
                .menu-navegacao ul {
                    justify-content: space-around;
                }
                
                .menu-navegacao li {
                    margin: 5px;
                }
                
                .menu-navegacao a {
                    padding: 6px 10px;
                    font-size: 0.8rem;
                }
                
                .menu-section {
                    scroll-margin-top: 60px;
                }
            }

            .bebidas-filtro {
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .filtro-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        background-color: #ddd;
        color: #333;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }

    .filtro-btn.active {
        background-color: var(--primary-color);
        color: white;
    }

    .filtro-btn:hover {
        background-color: #ccc;
    }

    .filtro-btn.active:hover {
        background-color: var(--primary-color);
        opacity: 0.9;
    } 

    /* Estilos para a seção de buffet */

    .buffet-options {
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 15px;
    }

    .buffet-option {
        display: flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, 0.9);
        padding: 5px 10px;
        border-radius: 8px;
        min-width: 150px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .buffet-option:hover {
        transform: translateY(-5px);
    }

    .buffet-option i {
        font-size: 1.8rem;
        color: var(--primary-color);
    }

    .buffet-subtitle {
        color: var(--secondary-color);
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }


    </style>
    </head>
    <body>
        <header>
            <div class="container">
                <h1>ESPETINHO DO JÚNIOR</h1>
                <p class="header-subtitle">EXCELÊNCIA EM SERVIR DESDE 2005</p>
            </div>
        </header>
        
        <!-- Menu de navegação -->
        <nav class="menu-navegacao">
            <ul>
                <li><a href="#espetos">Espetos</a></li>
                <li><a href="#porcoes">Porções</a></li>
                <li><a href="#bebidas">Bebidas</a></li>
                <li><a href="#cervejas">Cervejas</a></li>
                <li><a href="#buffet">Jantinha</a></li>
                <li><a href="#delivery">Delivery</a></li>
            </ul>
        </nav>

        <main class="container">
            <!-- Seção de Espetos -->
        <section id="espetos" class="menu-section">
                <h2 class="section-title">Espetos da casa</h2>
                <div class="menu-grid">
                    <?php if (!empty($menuData['espetos'])): ?>
                        <?php foreach($menuData['espetos'] as $item): ?>
                        <div class="menu-item">
                            <div class="item-img-container">
                                <img src="<?= 
        // Verifica se a imagem existe no sistema de arquivos
        (isset($item['imagem']) && file_exists('images/menu/espetos/' . $item['imagem'])) 
        ? 'images/menu/espetos/' . htmlspecialchars($item['imagem']) 
        : 'images/menu/default.jpg' 
    ?>" 
        alt="<?= htmlspecialchars($item['tipo_carne'] ?? 'Espeto') ?>" 
        class="item-img">
                                <div class="item-special">Incluso acompanhamento!</div>
                            </div>
                            <div class="item-info">
                                <h3 class="item-name"><?= htmlspecialchars($item['tipo_carne'] ?? 'Espeto') ?></h3>
                                <?php if(!empty($item['descricao'])): ?>
                                    <p class="item-desc"><?= htmlspecialchars($item['descricao']) ?></p>
                                <?php endif; ?>
                                <p class="item-price"><?= isset($item['preco']) ? number_format($item['preco'], 2, ',', '.') : '0,00' ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-items">Nenhum espeto disponível no momento</p>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Seção de Porções -->
            <section id="porcoes" class="menu-section">
                <h2 class="section-title">Porções</h2>
                <div class="menu-grid">
                    <?php if (!empty($menuData['porcoes'])): ?>
                        <?php foreach($menuData['porcoes'] as $item): ?>
                        <div class="menu-item">
                            <div class="item-img-container">
                        <img src="images/menu/porcoes/<?= htmlspecialchars($item['imagem'] ?? 'default.jpg') ?>" 
                                        alt="<?= htmlspecialchars($item['nome'] ?? 'Porção') ?>" 
                                        class="item-img"
                                        onerror="this.onerror=null; this.src='images/menu/opcoes_buffet.jpg'">
                                <?php if($item['destaque'] == 1): ?>
                                    <div class="item-special">RECOMENDADO</div>
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <h3 class="item-name"><?= htmlspecialchars($item['nome'] ?? 'Porção') ?></h3>
                                <?php if(!empty($item['descricao'])): ?>
                                    <p class="item-desc"><?= htmlspecialchars($item['descricao']) ?></p>
                                <?php endif; ?>
                                <?php if(isset($item['tamanho'])): ?>
                                    <p class="item-desc"><strong>Tamanho:</strong> <?= htmlspecialchars($item['tamanho']) ?></p>
                                <?php endif; ?>
                                <p class="item-price"><?= isset($item['preco']) ? number_format($item['preco'], 2, ',', '.') : '0,00' ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-items">Nenhuma porção disponível no momento</p>
                    <?php endif; ?>
                </div>
            </section>

    <!-- Seção de Bebidas -->
    <section id="bebidas" class="menu-section">
        <h2 class="section-title">Bebidas e Refrigerantes</h2>
        <div class="bebidas-filtro" style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
            <button class="filtro-btn active" data-categoria="todas">Todas</button>
            <button class="filtro-btn" data-categoria="refrigerante">Refrigerantes</button>
            <button class="filtro-btn" data-categoria="suco">Sucos</button>
            <button class="filtro-btn" data-categoria="energetico">Energéticos</button>
            <button class="filtro-btn" data-categoria="agua">Águas</button>
        </div>
        <div class="menu-grid">
            <?php if (!empty($menuData['bebidas'])): ?>
                <?php foreach($menuData['bebidas'] as $item): ?>
                <div class="menu-item" data-categoria="<?= htmlspecialchars($item['categoria'] ?? 'outros') ?>">
                    <div class="item-img-container">
                        <img src="images/menu/bebidas/<?= htmlspecialchars($item['imagem'] ?? 'default.jpg') ?>" 
                            alt="<?= htmlspecialchars($item['nome'] ?? 'Bebida') ?>" 
                            class="item-img"
                            onerror="this.onerror=null; this.src='images/menu/default.jpg'">
                    </div>
                    <div class="item-info">
                        <h3 class="item-name"><?= htmlspecialchars($item['nome'] ?? 'Bebida') ?></h3>
                        <?php if(!empty($item['descricao'])): ?>
                            <p class="item-desc"><?= htmlspecialchars($item['descricao']) ?></p>
                        <?php endif; ?>
                        <?php if(isset($item['tamanho_ml'])): ?>
                            <p style="display: block;"><strong>Tamanho:</strong> <?= htmlspecialchars($item['tamanho_ml']) ?>ml</p>
                        <?php endif; ?>
                        <p class="item-price"><?= isset($item['preco']) ? number_format($item['preco'], 2, ',', '.') : '0,00' ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-items">Nenhuma bebida disponível no momento</p>
            <?php endif; ?>
        </div>
    </section>

            <!-- Seção de Cervejas -->
            <section id="cervejas" class="menu-section">
                <h2 class="section-title">Cervejas</h2>
                <div class="menu-grid">
                    <?php if (!empty($menuData['cervejas'])): ?>
                        <?php foreach($menuData['cervejas'] as $item): ?>
                        <div class="menu-item">
                            <div class="item-img-container">
                                <img src="images/menu/cervejas/<?= htmlspecialchars($item['imagem'] ?? 'default.jpg') ?>" 
                                    alt="<?= htmlspecialchars($item['marca'] ?? 'Cerveja') ?>" 
                                    class="item-img"
                                    onerror="this.onerror=null; this.src='images/menu/default.jpg'">
                            
                            </div>
                            <div class="item-info">
                                <h3 class="item-name"><?= htmlspecialchars($item['marca'] ?? 'Cerveja') ?></h3>
                                <?php if(!empty($item['descricao'])): ?>
                                    <p style="display: block;"><?= htmlspecialchars($item['descricao']) ?></p>
                                <?php endif; ?>
                                <?php if(isset($item['tamanho_ml'])): ?>
                                    <p style="display: block;"><strong>Tamanho:</strong> <?= htmlspecialchars($item['tamanho_ml']) ?>ml</p>
                                <?php endif; ?>
                                <p class="item-price"><?= isset($item['preco']) ? number_format($item['preco'], 2, ',', '.') : '0,00' ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-items">Nenhuma cerveja disponível no momento</p>
                    <?php endif; ?>
                </div>
            </section>

        <section id="buffet" class="menu-section">
        <div class="buffet-header">
            <h2 class="section-title">Jantinha Exclusiva</h2>
            <div class="buffet-options">
                <div class="buffet-option">
                    <i class="fas fa-utensils"></i>
                    <div>
                        <h4 class="buffet-subtitle">Self Service + Espetinho</h4>
                        <p class="item-price">R$28,00</p>
                    </div>
                </div>
                <div class="buffet-option">
                    <i class="fas fa-utensils"></i>
                    <div>
                        <h4 class="buffet-subtitle">Self Service + Bife</h4>
                        <p class="item-price">R$30,00</p>
                    </div>
                </div>
            </div>
        </div>
                <div class="menu-grid">
                    <?php if (!empty($menuData['buffet'])): ?>
                        <?php foreach($menuData['buffet'] as $item): ?>
                            <div class="menu-item">
                                <div class="item-img-container">
                                <div class="item-special">Buffet do dia!</div>
                                <?php 
                                $imagemPath = 'images/menu/opcoes_buffet/' . htmlspecialchars($item['imagem'] ?? 'default.png');
                                $imagemPath = file_exists($imagemPath) ? $imagemPath : 'images/menu/default.jpg';
                                ?>
                                <img src="<?= $imagemPath ?>" 
                                    alt="<?= htmlspecialchars($item['nome'] ?? 'Opção Buffet') ?>" 
                                    class="item-img">
                            </div>
                            <div class="item-info">
                                <h3 class="item-name"><?= htmlspecialchars($item['nome'] ?? 'Opção Buffet') ?></h3>
                                <?php if(!empty($item['descricao'])): ?>
                                    <p class="item-desc"><?= htmlspecialchars($item['descricao']) ?></p>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-items">Nenhuma opção de buffet disponível no momento</p>
                    <?php endif; ?>
                </div>
            </section>
            <!-- Seção Delivery -->
            <section id="delivery" class="menu-section" style="text-align: center; margin-top: 60px;">
                <button id="deliveryBtn" style="background-color: var(--accent-color); color: var(--secondary-color); 
                border: none; padding: 15px 30px; font-size: 1.2rem; border-radius: 50px; 
                cursor: pointer; font-weight: bold; transition: all 0.3s; box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                display: flex; align-items: center; margin: 0 auto;">
            <i class="fas fa-motorcycle" style="margin-right: 10px;"></i>
            Fazemos Delivery Também!
        </button>
        
<!-- Modal Delivery (hidden by default) -->
<div id="deliveryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; 
        width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); overflow: auto;">
    <div style="background-color: white; margin: 10% auto; padding: 30px; 
            border-radius: 10px; max-width: 500px; position: relative; text-align: center;">
        <span id="closeModal" style="position: absolute; right: 20px; top: 10px; 
                font-size: 28px; cursor: pointer;">&times;</span>
        
        <h2 style="color: var(--primary-color); font-family: 'Playfair Display', serif; 
                margin-bottom: 30px;">Escolha sua plataforma</h2>
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <a href="https://www.ifood.com.br/delivery/morrinhos-go/espetinho-do-junior-jantinhas-vila-santos-dumont-i/5ef0eab0-bc60-4bd1-8ec5-69cdc4314be4" 
            target="_blank" 
            style="background-color: #ea1d2c; color: white; padding: 15px; 
                    border-radius: 8px; text-decoration: none; font-weight: bold;
                    display: flex; align-items: center; justify-content: center;
                    transition: transform 0.3s;">
                <img src="https://t2.tudocdn.net/652297?w=646&h=284" 
                style="width: 30px; margin-right: 10px;" alt="iFood">
                Pedir pelo iFood
            </a>
            
            <a href="https://wa.me/556492397675" 
            target="_blank" 
            style="background-color: #25D366; color: white; padding: 15px; 
                    border-radius: 8px; text-decoration: none; font-weight: bold;
                    display: flex; align-items: center; justify-content: center;
                    transition: transform 0.3s;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" 
                style="width: 30px; margin-right: 10px;" alt="WhatsApp">
                Pedir pelo WhatsApp
            </a>
        </div>
        
        <p style="margin-top: 30px; color: #666; font-size: 0.9rem;">
            Horário de Delivery: 18h às 23h
        </p>
    </div>
</div>
</section>

            <a href="#" id="backToTop"><i class="fas fa-arrow-up"></i></a>
        </main>

        <footer>
            <div class="footer-content">
                <div class="footer-logo">CHURRASCARIA DO JÚNIOR</div>
                <p class="footer-info"><i class="fas fa-map-marker-alt"></i> Av. 101, 474-524, Morrinhos GO, 75654-252, Brazil.</p>
                <p class="footer-info"><i class="fas fa-phone"></i> (64) 99239-7675</p>
                <p class="footer-info"><i class="fas fa-envelope"></i> espetinhojunior2@gmail.com</p>
                
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                </div>
                
                <p class="footer-info" style="margin-top: 30px; font-size: 0.9rem;">
                    &copy; <?= date('Y') ?> Churrascaria do Júnior. Todos os direitos reservados.
                    <br>
                </p>
                <p class="footer-info" style="margin-top: 30px; font-size: 0.9rem;">
                    &copy; <?= date('Y') ?> Site desenvolvido por: 
                    <a href="https://www.instagram.com/ezequiel.o.rod?igsh=MTVvcGd2YXN2cDY4YQ%3D%3D" 
                    target="_blank" 
                    style="color: var(--accent-color); text-decoration: none; font-weight: 600;">
                    Ezequiel Oliveira
                    </a>
                </p>
            </div>
        </footer>

        <script>
            // Botão Voltar ao Topo
            window.addEventListener('scroll', function() {
                var backToTop = document.getElementById('backToTop');
                if (window.pageYOffset > 300) {
                    backToTop.style.display = 'block';
                } else {
                    backToTop.style.display = 'none';
                }
            });
            
            document.getElementById('backToTop').addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({top: 0, behavior: 'smooth'});
            });

            // Script para controlar o modal
            const deliveryBtn = document.getElementById('deliveryBtn');
            const deliveryModal = document.getElementById('deliveryModal');
            const closeModal = document.getElementById('closeModal');
            
            deliveryBtn.addEventListener('click', function() {
                deliveryModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
            
            closeModal.addEventListener('click', function() {
                deliveryModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
            
            window.addEventListener('click', function(event) {
                if (event.target == deliveryModal) {
                    deliveryModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });

            // Filtro de bebidas
            document.addEventListener('DOMContentLoaded', function() {
                const filtroBtns = document.querySelectorAll('.filtro-btn');
                const bebidas = document.querySelectorAll('.menu-item[data-categoria]');
                
                filtroBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        filtroBtns.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        
                        const categoria = this.getAttribute('data-categoria');
                        
                        bebidas.forEach(bebida => {
                            if (categoria === 'todas' || bebida.getAttribute('data-categoria') === categoria) {
                                bebida.style.display = 'block';
                            } else {
                                bebida.style.display = 'none';
                            }
                        });
                    });
                });
            });
        </script>
    </body>
    </html>