    <?php
    require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';
    require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/includes/menu-images.php';

    $produtosPorCategoria = [];
    $erroCardapio = false;

    try {
        $database = new Database();
        $db = $database->getConnection();

        if (!$db) {
            throw new RuntimeException('Não foi possível conectar ao banco de dados do caixa.');
        }

        $stmt = $db->prepare(
            "SELECT
                p.id AS produto_id,
                p.nome AS produto_nome,
                p.nome_publico,
                p.descricao_publica,
                p.preco,
                p.imagem AS produto_imagem,
                p.imagem_url AS produto_imagem_url,
                c.id AS categoria_id,
                c.nome AS categoria_nome,
                pc.id AS card_id,
                pc.nome_exibicao AS card_nome,
                pc.descricao AS card_descricao,
                pc.imagem AS card_imagem,
                pc.imagem_url AS card_imagem_url
             FROM produtos p
             INNER JOIN categorias c ON c.id = p.categoria_id
             LEFT JOIN produto_cards pc ON pc.produto_id = p.id
             WHERE p.ativo = 1
             ORDER BY
                CASE c.nome
                    WHEN 'Espetos' THEN 1
                    WHEN 'Porções' THEN 2
                    WHEN 'Jantinhas e Marmitas' THEN 3
                    WHEN 'Bebidas não alcoólicas' THEN 4
                    WHEN 'Cervejas' THEN 5
                    WHEN 'Outros' THEN 6
                    ELSE 7
                END,
                c.nome,
                p.nome,
                pc.ordem,
                pc.id"
        );
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $produto) {
            $categoriaId = (int) $produto['categoria_id'];
            if (!isset($produtosPorCategoria[$categoriaId])) {
                $produtosPorCategoria[$categoriaId] = [
                    'id' => $categoriaId,
                    'nome' => $produto['categoria_nome'],
                    'produtos' => []
                ];
            }
            $produtoId = (int) $produto['produto_id'];
            if (!isset($produtosPorCategoria[$categoriaId]['produtos'][$produtoId])) {
                $produtosPorCategoria[$categoriaId]['produtos'][$produtoId] = [
                    'id' => $produtoId,
                    'nome' => $produto['produto_nome'],
                    'nome_publico' => $produto['nome_publico'],
                    'descricao_publica' => $produto['descricao_publica'],
                    'preco' => $produto['preco'],
                    'imagem' => $produto['produto_imagem'],
                    'imagem_url' => $produto['produto_imagem_url'],
                    'cards' => []
                ];
            }
            if ($produto['card_id'] !== null) {
                $produtosPorCategoria[$categoriaId]['produtos'][$produtoId]['cards'][] = [
                    'nome' => $produto['card_nome'],
                    'descricao' => $produto['card_descricao'],
                    'imagem' => $produto['card_imagem'],
                    'imagem_url' => $produto['card_imagem_url']
                ];
            }
        }
        foreach ($produtosPorCategoria as &$categoria) {
            $categoria['produtos'] = array_values($categoria['produtos']);
        }
        unset($categoria);
    } catch (Exception $e) {
        error_log('Erro ao carregar produtos do cardápio público: ' . $e->getMessage());
        $erroCardapio = true;
        http_response_code(503);
    }
    ?>

    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Espetinho do Júnior</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:wght@400;500;600&display=swap" rel="stylesheet">
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
    <link rel="stylesheet" href="cardapio.css">
    </head>
    <body>
        <header class="hero">
            <div class="hero-content">
                <span class="hero-kicker">Brasa, tempero e tradição</span>
                <h1>ESPETINHO DO JÚNIOR</h1>
                <p class="header-subtitle">EXCELÊNCIA EM SERVIR DESDE 2005</p>
                <div class="hero-actions">
                    <span class="delivery-hours"><i class="far fa-clock" aria-hidden="true"></i> Delivery · 18h às 23h</span>
                    <button class="delivery-trigger delivery-trigger--hero" type="button" data-delivery-trigger>
                        <i class="fas fa-motorcycle" aria-hidden="true"></i> Pedir pelo delivery
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Menu de navegação -->
        <nav class="menu-navegacao" aria-label="Categorias do cardápio">
            <ul>
                <?php foreach ($produtosPorCategoria as $categoria): ?>
                    <li><a href="#categoria-<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
                <li><a href="#delivery">Delivery</a></li>
            </ul>
        </nav>

        <main class="container">
            <?php if ($erroCardapio): ?>
                <section class="menu-section">
                    <p class="no-items">O cardápio está temporariamente indisponível. Tente novamente mais tarde.</p>
                </section>
            <?php elseif (!empty($produtosPorCategoria)): ?>
                <?php foreach ($produtosPorCategoria as $categoria): ?>
                    <section id="categoria-<?= $categoria['id'] ?>" class="menu-section">
                        <?php
                        $quantidadeItens = 0;
                        foreach ($categoria['produtos'] as $produto) {
                            $quantidadeItens += 1 + count($produto['cards']);
                        }
                        ?>
                        <div class="section-heading">
                            <h2 class="section-title"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <span class="section-count"><?= $quantidadeItens ?> <?= $quantidadeItens === 1 ? 'item' : 'itens' ?></span>
                        </div>
                        <div class="menu-grid">
                            <?php foreach ($categoria['produtos'] as $produto): ?>
                                <?php
                                $apresentacoes = [[
                                    'nome' => $produto['nome_publico'] ?: $produto['nome'],
                                    'descricao' => $produto['descricao_publica'],
                                    'imagem' => $produto['imagem'],
                                    'imagem_url' => $produto['imagem_url'],
                                    'principal' => true
                                ]];
                                foreach ($produto['cards'] as $card) {
                                    $card['principal'] = false;
                                    $apresentacoes[] = $card;
                                }
                                ?>
                                <?php foreach ($apresentacoes as $apresentacao): ?>
                                    <?php
                                    $imagemUrl = menuResolveImageSource(
                                        $apresentacao['imagem'] ?? '',
                                        $apresentacao['imagem_url'] ?? '',
                                        __DIR__ . '/images/menu',
                                        'images/menu/'
                                    );
                                    if ($imagemUrl === 'images/menu/default.png') {
                                        $imagemUrl = menuResolveImageSource(
                                            $produto['imagem'] ?? '',
                                            $produto['imagem_url'] ?? '',
                                            __DIR__ . '/images/menu',
                                            'images/menu/'
                                        );
                                    }
                                    ?>
                                    <article class="menu-item <?= $apresentacao['principal'] ? 'menu-item--principal' : 'menu-item--opcao' ?>">
                                        <div class="item-img-container">
                                            <img
                                                src="<?= htmlspecialchars($imagemUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                alt="<?= htmlspecialchars($apresentacao['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                class="item-img"
                                                loading="lazy"
                                                onerror="this.onerror=null; this.src='images/menu/default.png'">
                                        </div>
                                        <div class="item-info">
                                            <span class="item-kind"><?= $apresentacao['principal'] ? 'Produto' : 'Opção · mesmo preço' ?></span>
                                            <div class="item-heading">
                                                <h3 class="item-name"><?= htmlspecialchars($apresentacao['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                                <p class="item-price"><?= number_format((float) $produto['preco'], 2, ',', '.') ?></p>
                                            </div>
                                            <?php if (!empty($apresentacao['descricao'])): ?>
                                                <p class="item-desc"><?= htmlspecialchars($apresentacao['descricao'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif; ?>
                                            <?php if (!$apresentacao['principal']): ?>
                                                <span class="item-price-note">Preço do produto principal</span>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php else: ?>
                <section class="menu-section">
                    <p class="no-items">Nenhum produto disponível no momento.</p>
                </section>
            <?php endif; ?>
            <!-- Seção Delivery -->
            <section id="delivery" class="menu-section delivery-section">
                <span class="eyebrow">Peça do seu jeito</span>
                <h2 class="section-title">Brasa boa chega até você</h2>
                <p>Consulte as opções de pedido pelo iFood ou WhatsApp. Delivery · 18h às 23h.</p>
                <button class="delivery-trigger" type="button" data-delivery-trigger>
                    <i class="fas fa-motorcycle" aria-hidden="true"></i> Escolher canal de delivery
                </button>
            </section>

            <div class="delivery-sticky">
                <button class="delivery-trigger" type="button" data-delivery-trigger>Pedir agora</button>
            </div>

            <div id="deliveryModal" class="delivery-modal" aria-hidden="true">
                <section class="delivery-modal-content" role="dialog" aria-modal="true" aria-labelledby="deliveryTitle">
                    <button id="closeModal" class="close-modal" type="button" aria-label="Fechar">&times;</button>
                    <span class="eyebrow">Delivery · 18h às 23h</span>
                    <h2 id="deliveryTitle">Escolha onde pedir</h2>
                    <div class="delivery-options">
                        <a class="delivery-option delivery-option--ifood" href="https://www.ifood.com.br/delivery/morrinhos-go/espetinho-do-junior-jantinhas-vila-santos-dumont-i/5ef0eab0-bc60-4bd1-8ec5-69cdc4314be4" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-utensils" aria-hidden="true"></i> Pedir pelo iFood
                        </a>
                        <a class="delivery-option delivery-option--whatsapp" href="https://wa.me/556492397675" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp" aria-hidden="true"></i> Pedir pelo WhatsApp
                        </a>
                    </div>
                    <p class="delivery-hours"><i class="far fa-clock" aria-hidden="true"></i> Horário de Delivery: 18h às 23h</p>
                </section>
            </div>

            <button type="button" id="backToTop" aria-label="Voltar ao topo" hidden>
                <i class="fas fa-arrow-up" aria-hidden="true"></i>
            </button>
        </main>

        <footer>
            <div class="footer-content">
                <div class="footer-logo">CHURRASCARIA DO JÚNIOR</div>
                <p class="footer-info"><i class="fas fa-map-marker-alt"></i> Av. 101, 474-524, Morrinhos GO, 75654-252, Brazil.</p>
                <p class="footer-info"><i class="fas fa-phone"></i> (64) 99239-7675</p>
                <p class="footer-info"><i class="fas fa-envelope"></i> espetinhojunior2@gmail.com</p>
                <p class="footer-info footer-legal">
                    &copy; <?= date('Y') ?> Churrascaria do Júnior. Todos os direitos reservados.
                    <br>
                </p>
                <p class="footer-info footer-credit">
                    &copy; <?= date('Y') ?> Site desenvolvido por: 
                    <a href="https://www.instagram.com/ezequiel.o.rod?igsh=MTVvcGd2YXN2cDY4YQ%3D%3D" 
                    target="_blank" rel="noopener noreferrer">
                    Ezequiel Oliveira
                    </a>
                </p>
            </div>
        </footer>

        <script>
            const deliveryModal = document.getElementById('deliveryModal');
            const closeModal = document.getElementById('closeModal');
            const deliveryTriggers = document.querySelectorAll('[data-delivery-trigger]');
            const backToTop = document.getElementById('backToTop');
            let lastDeliveryTrigger = null;

            function closeDeliveryModal() {
                deliveryModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                if (lastDeliveryTrigger) {
                    lastDeliveryTrigger.focus();
                }
            }

            deliveryTriggers.forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    lastDeliveryTrigger = trigger;
                    deliveryModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                    closeModal.focus();
                });
            });

            closeModal.addEventListener('click', closeDeliveryModal);
            deliveryModal.addEventListener('click', function (event) {
                if (event.target === deliveryModal) {
                    closeDeliveryModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && deliveryModal.getAttribute('aria-hidden') === 'false') {
                    closeDeliveryModal();
                    return;
                }
                if (event.key === 'Tab' && deliveryModal.getAttribute('aria-hidden') === 'false') {
                    const focusableElements = deliveryModal.querySelectorAll('button, a[href]');
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];
                    if (event.shiftKey && document.activeElement === firstElement) {
                        event.preventDefault();
                        lastElement.focus();
                    } else if (!event.shiftKey && document.activeElement === lastElement) {
                        event.preventDefault();
                        firstElement.focus();
                    }
                }
            });

            backToTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            window.addEventListener('scroll', function () {
                backToTop.hidden = window.scrollY < 600;
            }, { passive: true });

            const categorySections = document.querySelectorAll('.menu-section[id]');
            const categoryLinks = document.querySelectorAll('.menu-navegacao a[href^="#"]');
            if ('IntersectionObserver' in window) {
                const sectionObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }
                        categoryLinks.forEach(function (link) {
                            const isCurrent = link.getAttribute('href') === '#' + entry.target.id;
                            if (isCurrent) {
                                link.setAttribute('aria-current', 'true');
                                link.scrollIntoView({ block: 'nearest', inline: 'center' });
                            } else {
                                link.removeAttribute('aria-current');
                            }
                        });
                    });
                }, { rootMargin: '-25% 0px -65% 0px' });
                categorySections.forEach(function (section) {
                    sectionObserver.observe(section);
                });
            }
        </script>
    </body>
    </html>