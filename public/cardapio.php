    <?php
    require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';
    require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/includes/menu-images.php';

    $produtosPorCategoria = [];
    $produtosDestaque = [];
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
        $produtosDestaque = [];
        foreach ($produtosPorCategoria as $categoria) {
            foreach ($categoria['produtos'] as $produto) {
                $produtosDestaque[] = [
                    'categoria_id' => $categoria['id'],
                    'categoria_nome' => $categoria['nome'],
                    'nome' => $produto['nome_publico'] ?: $produto['nome'],
                    'descricao' => $produto['descricao_publica'],
                    'preco' => $produto['preco'],
                    'imagem' => $produto['imagem'],
                    'imagem_url' => $produto['imagem_url']
                ];
                $ultimoIndice = count($produtosDestaque) - 1;
                $imagemDestaque = menuResolveImageSource(
                    $produto['imagem'] ?? '',
                    $produto['imagem_url'] ?? '',
                    __DIR__ . '/images/menu',
                    'images/menu/'
                );
                if ($imagemDestaque === 'images/menu/default.png') {
                    foreach ($produto['cards'] as $card) {
                        $imagemOpcao = menuResolveImageSource(
                            $card['imagem'] ?? '',
                            $card['imagem_url'] ?? '',
                            __DIR__ . '/images/menu',
                            'images/menu/'
                        );
                        if ($imagemOpcao !== 'images/menu/default.png') {
                            $produtosDestaque[$ultimoIndice]['imagem'] = $card['imagem'];
                            $produtosDestaque[$ultimoIndice]['imagem_url'] = $card['imagem_url'];
                            break;
                        }
                    }
                }
                if (count($produtosDestaque) === 6) {
                    break 2;
                }
            }
        }
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
        
    <link rel="stylesheet" href="cardapio.css?v=20261030-4">
    </head>
    <body>
        <header class="hero">
            <div class="hero-content">
                <div class="hero-tools" aria-label="Atalhos">
                    <button class="hero-icon-button" id="openHub" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="siteHub">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>
                    <button class="hero-icon-button" id="openSearch" type="button" aria-label="Pesquisar produtos" aria-expanded="false" aria-controls="menuSearch">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </button>
                </div>
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

        <section class="search-panel" id="menuSearch" aria-label="Pesquisar no cardápio" hidden>
            <label for="menuSearchInput">O que você está procurando?</label>
            <div class="search-input-wrap">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input id="menuSearchInput" type="search" placeholder="Ex.: picanha, cerveja, porção..." autocomplete="off">
                <button id="closeSearch" type="button" aria-label="Fechar busca"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <p id="searchFeedback" class="search-feedback" aria-live="polite"></p>
        </section>

        <div class="hub-backdrop" id="hubBackdrop" hidden>
            <aside class="site-hub" id="siteHub" role="dialog" aria-modal="true" aria-label="Menu do cardápio" aria-hidden="true" tabindex="-1">
                <div class="hub-heading">
                    <div>
                        <span class="eyebrow">Espetinho do Júnior</span>
                        <h2>Explore o cardápio</h2>
                    </div>
                    <button class="hub-close" id="closeHub" type="button" aria-label="Fechar menu">&times;</button>
                </div>
                <nav aria-label="Atalhos do cardápio">
                    <ul>
                        <?php foreach ($produtosPorCategoria as $categoria): ?>
                            <li><a href="#categoria-<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?><i class="fas fa-arrow-right" aria-hidden="true"></i></a></li>
                        <?php endforeach; ?>
                        <li><a href="#delivery">Delivery<i class="fas fa-arrow-right" aria-hidden="true"></i></a></li>
                    </ul>
                </nav>
                <a class="hub-phone" href="tel:+556492397675"><i class="fas fa-phone" aria-hidden="true"></i> (64) 99239-7675</a>
            </aside>
        </div>

        <?php if (!$erroCardapio && !empty($produtosDestaque)): ?>
            <section class="featured-section" aria-labelledby="featuredTitle">
                <div class="featured-heading">
                    <div>
                        <span class="eyebrow">Uma seleção da casa</span>
                        <h2 id="featuredTitle">Destaques do cardápio</h2>
                    </div>
                    <div class="featured-controls">
                        <span id="featuredStatus" class="featured-status" aria-live="polite"></span>
                        <button class="featured-control" id="pauseFeatured" type="button" aria-label="Pausar destaques automáticos">
                            <i class="fas fa-pause" aria-hidden="true"></i>
                        </button>
                        <button class="featured-control" id="previousFeatured" type="button" aria-label="Produto anterior">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        </button>
                        <button class="featured-control" id="nextFeatured" type="button" aria-label="Próximo produto">
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="featured-track" id="featuredTrack" role="region" tabindex="0" aria-label="Produtos em destaque" aria-roledescription="carrossel">
                    <?php foreach ($produtosDestaque as $indice => $produto): ?>
                        <?php
                        $imagemDestaque = menuResolveImageSource(
                            $produto['imagem'] ?? '',
                            $produto['imagem_url'] ?? '',
                            __DIR__ . '/images/menu',
                            'images/menu/'
                        );
                        $imagemDestaqueAusente = $imagemDestaque === 'images/menu/default.png';
                        ?>
                        <a class="featured-card<?= $imagemDestaqueAusente ? ' featured-card--without-image' : '' ?>" href="#categoria-<?= $produto['categoria_id'] ?>" aria-label="<?= htmlspecialchars($produto['nome'] . ', ' . $produto['categoria_nome'] . ', R$ ' . number_format((float) $produto['preco'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($imagemDestaqueAusente): ?>
                                <span class="featured-image-placeholder" aria-hidden="true"><i class="fas fa-fire"></i></span>
                            <?php else: ?>
                                <img class="featured-photo" src="<?= htmlspecialchars($imagemDestaque, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="<?= $indice < 2 ? 'eager' : 'lazy' ?>">
                            <?php endif; ?>
                            <span class="featured-shade" aria-hidden="true"></span>
                            <span class="featured-copy">
                                <span class="featured-category"><?= htmlspecialchars($produto['categoria_nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="featured-name"><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($produto['descricao'])): ?>
                                    <span class="featured-description"><?= htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <span class="featured-price">R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
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
                                    $imagemAusente = $imagemUrl === 'images/menu/default.png';
                                    ?>
                                    <?php
                                    $termosPesquisa = implode(' ', [
                                        $apresentacao['nome'],
                                        $apresentacao['descricao'] ?? '',
                                        $produto['nome'],
                                        $categoria['nome']
                                    ]);
                                    ?>
                                    <article class="menu-item <?= $apresentacao['principal'] ? 'menu-item--principal' : 'menu-item--opcao' ?><?= $imagemAusente ? ' menu-item--without-image' : '' ?>" data-search="<?= htmlspecialchars($termosPesquisa, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="item-img-container">
                                            <?php if ($imagemAusente): ?>
                                                <div class="item-image-placeholder" role="img" aria-label="Foto de <?= htmlspecialchars($apresentacao['nome'], ENT_QUOTES, 'UTF-8') ?> não cadastrada">
                                                    <i class="fas fa-fire" aria-hidden="true"></i>
                                                    <span>Espetinho do Júnior</span>
                                                </div>
                                            <?php else: ?>
                                                <img
                                                    src="<?= htmlspecialchars($imagemUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                    alt="<?= htmlspecialchars($apresentacao['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                    class="item-img"
                                                    loading="lazy">
                                            <?php endif; ?>
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
            document.querySelectorAll('.featured-photo, .item-img').forEach(function (image) {
                image.addEventListener('error', function () {
                    const placeholder = document.createElement('div');
                    const isFeatured = image.classList.contains('featured-photo');
                    placeholder.className = isFeatured ? 'featured-image-placeholder' : 'item-image-placeholder';
                    placeholder.setAttribute('role', 'img');
                    placeholder.setAttribute(
                        'aria-label',
                        isFeatured ? 'Foto do produto não cadastrada' : 'Foto de ' + image.alt + ' não cadastrada'
                    );
                    placeholder.innerHTML = '<i class="fas fa-fire" aria-hidden="true"></i>';
                    if (!isFeatured) {
                        const brand = document.createElement('span');
                        brand.textContent = 'Espetinho do Júnior';
                        placeholder.append(brand);
                    }
                    image.replaceWith(placeholder);
                }, { once: true });
            });

            const hubBackdrop = document.getElementById('hubBackdrop');
            const siteHub = document.getElementById('siteHub');
            const openHub = document.getElementById('openHub');
            const closeHubButton = document.getElementById('closeHub');
            const openSearch = document.getElementById('openSearch');
            const searchPanel = document.getElementById('menuSearch');
            const searchInput = document.getElementById('menuSearchInput');
            const searchFeedback = document.getElementById('searchFeedback');
            let lastHubTrigger = null;

            function closeSiteHub(restoreFocus) {
                hubBackdrop.hidden = true;
                siteHub.setAttribute('aria-hidden', 'true');
                openHub.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('modal-open');
                if (restoreFocus && lastHubTrigger) {
                    lastHubTrigger.focus();
                }
            }

            openHub.addEventListener('click', function () {
                lastHubTrigger = openHub;
                searchPanel.hidden = true;
                openSearch.setAttribute('aria-expanded', 'false');
                hubBackdrop.hidden = false;
                siteHub.setAttribute('aria-hidden', 'false');
                openHub.setAttribute('aria-expanded', 'true');
                document.body.classList.add('modal-open');
                closeHubButton.focus();
            });
            closeHubButton.addEventListener('click', function () {
                closeSiteHub(true);
            });
            hubBackdrop.addEventListener('click', function (event) {
                if (event.target === hubBackdrop) {
                    closeSiteHub(true);
                }
            });
            siteHub.querySelectorAll('a[href^="#"]').forEach(function (link) {
                link.addEventListener('click', function () {
                    closeSiteHub(false);
                });
            });

            openSearch.addEventListener('click', function () {
                const willOpen = searchPanel.hidden;
                closeSiteHub(false);
                searchPanel.hidden = !willOpen;
                openSearch.setAttribute('aria-expanded', String(willOpen));
                if (willOpen) {
                    searchInput.focus();
                } else {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                }
            });
            document.getElementById('closeSearch').addEventListener('click', function () {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchPanel.hidden = true;
                openSearch.setAttribute('aria-expanded', 'false');
                openSearch.focus();
            });

            function normalizeSearch(value) {
                return value.toLocaleLowerCase('pt-BR').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            searchInput.addEventListener('input', function () {
                const query = normalizeSearch(searchInput.value.trim());
                const categorySections = document.querySelectorAll('.menu-section[id^="categoria-"]');
                let totalMatches = 0;
                categorySections.forEach(function (section) {
                    const cards = section.querySelectorAll('.menu-item');
                    let categoryMatches = 0;
                    cards.forEach(function (card) {
                        const matches = !query || normalizeSearch(card.dataset.search || '').includes(query);
                        card.hidden = !matches;
                        if (matches) {
                            categoryMatches += 1;
                        }
                    });
                    section.hidden = query !== '' && categoryMatches === 0;
                    totalMatches += categoryMatches;
                    const count = section.querySelector('.section-count');
                    if (count) {
                        count.textContent = categoryMatches + (categoryMatches === 1 ? ' item' : ' itens');
                    }
                    const categoryLink = document.querySelector('.menu-navegacao a[href="#' + section.id + '"]');
                    if (categoryLink) {
                        categoryLink.parentElement.hidden = query !== '' && categoryMatches === 0;
                    }
                });
                const featuredSection = document.querySelector('.featured-section');
                if (featuredSection) {
                    featuredSection.hidden = query !== '';
                }
                searchFeedback.textContent = query === ''
                    ? ''
                    : totalMatches === 0
                        ? 'Nenhum produto encontrado. Tente outro termo.'
                        : totalMatches + (totalMatches === 1 ? ' resultado encontrado.' : ' resultados encontrados.');
            });

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
                if (event.key === 'Escape' && !hubBackdrop.hidden) {
                    closeSiteHub(true);
                    return;
                }
                if (event.key === 'Escape' && !searchPanel.hidden) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                    searchPanel.hidden = true;
                    openSearch.setAttribute('aria-expanded', 'false');
                    openSearch.focus();
                    return;
                }
                if (event.key === 'Escape' && deliveryModal.getAttribute('aria-hidden') === 'false') {
                    closeDeliveryModal();
                    return;
                }
                if (event.key === 'Tab' && !hubBackdrop.hidden) {
                    const focusableElements = siteHub.querySelectorAll('button, a[href]');
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];
                    if (event.shiftKey && document.activeElement === firstElement) {
                        event.preventDefault();
                        lastElement.focus();
                    } else if (!event.shiftKey && document.activeElement === lastElement) {
                        event.preventDefault();
                        firstElement.focus();
                    }
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

            const featuredTrack = document.getElementById('featuredTrack');
            if (featuredTrack) {
                const featuredCards = Array.from(featuredTrack.querySelectorAll('.featured-card'));
                const featuredStatus = document.getElementById('featuredStatus');
                const pauseFeatured = document.getElementById('pauseFeatured');
                const featuredSection = document.querySelector('.featured-section');
                const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
                let featuredIndex = 0;
                let featuredTimer = null;
                let featuredPaused = reducedMotion.matches;
                const featuredPauseReasons = {
                    pointer: false,
                    focus: false,
                    hidden: document.hidden
                };

                function updateFeaturedStatus() {
                    const closestCard = featuredCards.reduce(function (closest, card, index) {
                        const trackLeft = featuredTrack.getBoundingClientRect().left;
                        const cardPosition = card.getBoundingClientRect().left - trackLeft + featuredTrack.scrollLeft;
                        const closestPosition = featuredCards[closest].getBoundingClientRect().left - trackLeft + featuredTrack.scrollLeft;
                        return Math.abs(cardPosition - featuredTrack.scrollLeft) < Math.abs(closestPosition - featuredTrack.scrollLeft)
                            ? index
                            : closest;
                    }, 0);
                    featuredIndex = closestCard;
                    featuredStatus.textContent = String(closestCard + 1).padStart(2, '0') + ' / ' + String(featuredCards.length).padStart(2, '0');
                }

                if (featuredCards.length < 2) {
                    pauseFeatured.hidden = true;
                    document.getElementById('previousFeatured').hidden = true;
                    document.getElementById('nextFeatured').hidden = true;
                }

                function moveFeatured(index) {
                    featuredIndex = (index + featuredCards.length) % featuredCards.length;
                    const targetLeft = featuredCards[featuredIndex].getBoundingClientRect().left
                        - featuredTrack.getBoundingClientRect().left
                        + featuredTrack.scrollLeft;
                    featuredTrack.scrollTo({
                        left: targetLeft,
                        behavior: reducedMotion.matches ? 'auto' : 'smooth'
                    });
                    updateFeaturedStatus();
                }

                function stopFeaturedTimer() {
                    if (featuredTimer !== null) {
                        window.clearInterval(featuredTimer);
                        featuredTimer = null;
                    }
                }

                function startFeaturedTimer() {
                    stopFeaturedTimer();
                    if (!featuredPaused && !Object.values(featuredPauseReasons).some(Boolean) && featuredCards.length > 1) {
                        featuredTimer = window.setInterval(function () {
                            moveFeatured(featuredIndex + 1);
                        }, 5000);
                    }
                }

                function updatePauseButton() {
                    pauseFeatured.setAttribute('aria-label', featuredPaused ? 'Retomar destaques automáticos' : 'Pausar destaques automáticos');
                    pauseFeatured.innerHTML = featuredPaused
                        ? '<i class="fas fa-play" aria-hidden="true"></i>'
                        : '<i class="fas fa-pause" aria-hidden="true"></i>';
                }

                document.getElementById('previousFeatured').addEventListener('click', function () {
                    moveFeatured(featuredIndex - 1);
                    startFeaturedTimer();
                });
                document.getElementById('nextFeatured').addEventListener('click', function () {
                    moveFeatured(featuredIndex + 1);
                    startFeaturedTimer();
                });
                pauseFeatured.addEventListener('click', function () {
                    featuredPaused = !featuredPaused;
                    updatePauseButton();
                    startFeaturedTimer();
                });
                featuredTrack.addEventListener('scroll', updateFeaturedStatus, { passive: true });
                featuredSection.addEventListener('mouseenter', function () {
                    featuredPauseReasons.pointer = true;
                    stopFeaturedTimer();
                });
                featuredSection.addEventListener('mouseleave', function () {
                    featuredPauseReasons.pointer = false;
                    startFeaturedTimer();
                });
                featuredSection.addEventListener('focusin', function () {
                    featuredPauseReasons.focus = true;
                    stopFeaturedTimer();
                });
                featuredSection.addEventListener('focusout', function (event) {
                    if (!featuredSection.contains(event.relatedTarget)) {
                        featuredPauseReasons.focus = false;
                        startFeaturedTimer();
                    }
                });
                document.addEventListener('visibilitychange', function () {
                    featuredPauseReasons.hidden = document.hidden;
                    startFeaturedTimer();
                });
                reducedMotion.addEventListener('change', function (event) {
                    featuredPaused = event.matches;
                    updatePauseButton();
                    startFeaturedTimer();
                });
                updatePauseButton();
                updateFeaturedStatus();
                startFeaturedTimer();
            }

            const categorySections = document.querySelectorAll('.menu-section[id]');
            const categoryLinks = document.querySelectorAll('.menu-navegacao a[href^="#"]');
            const categoryNavList = document.querySelector('.menu-navegacao ul');
            let activeCategoryLink = null;
            if ('IntersectionObserver' in window) {
                const sectionObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }
                        const nextActiveLink = Array.from(categoryLinks).find(function (link) {
                            return link.getAttribute('href') === '#' + entry.target.id;
                        });
                        if (!nextActiveLink || nextActiveLink === activeCategoryLink) {
                            return;
                        }
                        categoryLinks.forEach(function (link) {
                            if (link === nextActiveLink) {
                                link.setAttribute('aria-current', 'true');
                            } else {
                                link.removeAttribute('aria-current');
                            }
                        });
                        activeCategoryLink = nextActiveLink;
                        const listBounds = categoryNavList.getBoundingClientRect();
                        const linkBounds = nextActiveLink.getBoundingClientRect();
                        const targetLeft = categoryNavList.scrollLeft
                            + linkBounds.left
                            - listBounds.left
                            - (categoryNavList.clientWidth - linkBounds.width) / 2;
                        categoryNavList.scrollTo({
                            left: Math.max(0, targetLeft),
                            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
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