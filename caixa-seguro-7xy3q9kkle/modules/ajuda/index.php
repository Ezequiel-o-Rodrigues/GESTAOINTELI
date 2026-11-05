<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../includes/permissions.php';
requireModuleAccess('ajuda');
require_once __DIR__ . '/../../includes/functions.php';

$tituloPagina = 'Central de ajuda | Sistema Restaurante';
require_once __DIR__ . '/../../includes/header.php';
$perfil = currentUserRole();
?>

<section class="help-page py-4">
    <header class="help-hero">
        <p class="help-eyebrow">SUPORTE DA EQUIPE</p>
        <h1>Central de ajuda</h1>
        <p>Encontre orientações rápidas para usar o sistema. Consulte as dúvidas e os tutoriais sem precisar abrir um chamado.</p>
        <a class="help-primary-link" href="#tutoriais">
            <i class="bi bi-play-circle" aria-hidden="true"></i> Ver tutoriais
        </a>
    </header>

    <section class="help-section" aria-labelledby="faq-title">
        <div class="help-section-heading">
            <div>
                <p class="help-eyebrow">RESPOSTAS RÁPIDAS</p>
                <h2 id="faq-title">Perguntas frequentes</h2>
            </div>
            <i class="bi bi-chat-left-text" aria-hidden="true"></i>
        </div>
        <div class="help-faq">
            <details>
                <summary>Como acesso as funções do meu perfil?</summary>
                <p>Entre com seu e-mail e senha. O menu mostra as áreas disponíveis para seu perfil. Se uma função necessária não aparecer, peça ao administrador para conferir suas permissões.</p>
            </details>
            <details>
                <summary>Como abrir ou localizar uma comanda?</summary>
                <?php if ($perfil === 'garcom'): ?>
                    <p>No módulo Garçom, use “Abrir comanda” para iniciar um atendimento. Para atender uma mesa que já está aberta, selecione-a na lista de comandas; todos os garçons podem visualizar e atender as comandas abertas.</p>
                <?php elseif ($perfil === 'estoque'): ?>
                    <p>Seu perfil é voltado ao controle de estoque. Para consultar produtos e níveis de estoque, abra o módulo Estoque pelo menu do sistema.</p>
                <?php else: ?>
                    <p>No Caixa, selecione “Nova comanda” para iniciar uma ou abra “Comandas” para localizar um atendimento existente. As comandas abertas podem ser atendidas em cooperação.</p>
                <?php endif; ?>
            </details>
            <details>
                <summary>Como adicionar produtos e conferir o total?</summary>
                <p>Selecione uma comanda aberta e adicione os produtos desejados. O total é atualizado na tela. Quando a cobrança de gorjeta estiver habilitada, o caixa mostra o subtotal, a gorjeta dos itens lançados por garçons e o valor total previsto.</p>
            </details>
            <details>
                <summary>Quem recebe comissão e como funciona a gorjeta?</summary>
                <p>A comissão é registrada nos itens lançados por um garçom autenticado. Itens lançados pelo caixa ou pela administração entram no subtotal, mas não geram comissão. Se o repasse estiver habilitado, a gorjeta cobrada do cliente corresponde à soma das comissões dos itens ativos dos garçons.</p>
            </details>
            <details>
                <summary>Como falar com o suporte?</summary>
                <p>Use o botão verde “Suporte WhatsApp” no canto inferior da tela. Ele abre uma conversa com a equipe de suporte.</p>
            </details>
        </div>
    </section>

    <section class="help-section" id="tutoriais" aria-labelledby="tutorial-title">
        <div class="help-section-heading">
            <div>
                <p class="help-eyebrow">PASSO A PASSO</p>
                <h2 id="tutorial-title">Tutoriais</h2>
            </div>
            <i class="bi bi-journal-check" aria-hidden="true"></i>
        </div>
        <div class="help-tutorial-grid">
            <?php if ($perfil === 'garcom'): ?>
                <article class="help-tutorial">
                    <span class="help-tutorial-number">01</span>
                    <h3>Atender uma mesa</h3>
                    <ol>
                        <li>Abra “Comandas abertas” para ver os atendimentos disponíveis.</li>
                        <li>Selecione a mesa ou comanda que vai atender.</li>
                        <li>Escolha os produtos e toque em “Adicionar”.</li>
                        <li>Confira os itens antes de voltar à lista de comandas.</li>
                    </ol>
                </article>
            <?php elseif (in_array($perfil, ['admin', 'caixa'], true)): ?>
                <article class="help-tutorial">
                    <span class="help-tutorial-number">01</span>
                    <h3>Abrir uma venda no Caixa</h3>
                    <ol>
                        <li>Abra o módulo Caixa pelo menu ou pelo botão abaixo.</li>
                        <li>Selecione “Nova comanda” e escolha abrir pelo Caixa ou vincular um garçom.</li>
                        <li>Adicione produtos; cada item lançado por um garçom autenticado recebe sua autoria.</li>
                        <li>Confira o total previsto antes de finalizar a venda.</li>
                    </ol>
                </article>
                <article class="help-tutorial">
                    <span class="help-tutorial-number">02</span>
                    <h3>Localizar uma comanda aberta</h3>
                    <ol>
                        <li>Na tela do Caixa, abra “Comandas”.</li>
                        <li>Pesquise pelo nome do cliente ou pelo número da mesa.</li>
                        <li>Selecione a comanda para ver os itens e continuar o atendimento.</li>
                    </ol>
                </article>
            <?php else: ?>
                <article class="help-tutorial">
                    <span class="help-tutorial-number">01</span>
                    <h3>Consultar o estoque</h3>
                    <ol>
                        <li>Abra o módulo Estoque pelo menu principal.</li>
                        <li>Use os filtros de categoria e situação para localizar produtos.</li>
                        <li>Confira a quantidade disponível e os alertas de estoque baixo.</li>
                    </ol>
                </article>
            <?php endif; ?>
            <?php if (in_array($perfil, ['admin', 'caixa'], true)): ?>
                <article class="help-tutorial">
                    <span class="help-tutorial-number"><?= $perfil === 'garcom' ? '02' : '03' ?></span>
                    <h3>Finalizar e cobrar</h3>
                    <ol>
                        <li>Selecione a comanda e confira o subtotal e os itens ativos.</li>
                        <li>Se a gorjeta estiver habilitada, confira o valor apresentado junto ao total.</li>
                        <li>Selecione “Finalizar venda” e confirme o valor antes de concluir.</li>
                    </ol>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <nav class="help-actions" aria-label="Atalhos do sistema">
        <?php if ($perfil === 'garcom'): ?>
            <a class="help-secondary-link" href="<?= htmlspecialchars(PathConfig::modules('garcom/'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar ao atendimento
            </a>
        <?php elseif ($perfil === 'estoque'): ?>
            <a class="help-secondary-link" href="<?= htmlspecialchars(PathConfig::modules('estoque/'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-box-seam" aria-hidden="true"></i> Abrir Estoque
            </a>
        <?php else: ?>
            <a class="help-secondary-link" href="<?= htmlspecialchars(PathConfig::url('index.php'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-house" aria-hidden="true"></i> Voltar ao início
            </a>
            <?php if (in_array($perfil, ['admin', 'caixa'], true)): ?>
                <a class="help-primary-link" href="<?= htmlspecialchars(PathConfig::modules('caixa/'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-cash-stack" aria-hidden="true"></i> Abrir Caixa
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</section>

<?php require __DIR__ . '/../../includes/support-widget.php'; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
