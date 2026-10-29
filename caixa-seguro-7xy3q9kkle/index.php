<?php
// Configurar timezone do PHP para Brasília
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/config/auth.php';

// Se não estiver logado, redireciona para login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="welcome-section">
    <h2>Bom dia, <?= htmlspecialchars(explode(' ', trim($_SESSION['usuario_nome']))[0]) ?>! <span aria-hidden="true">👋</span></h2>
    <p>Selecione um módulo abaixo para gerenciar a operação do restaurante.</p>
</div>

<section class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" aria-label="Módulos do sistema">
    <a href="../admin-seguro-7xy3q9bk/gerenciar_cardapio.php" class="group relative flex min-h-60 flex-col items-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-900 no-underline shadow-sm transition duration-200 hover:-translate-y-1 hover:border-violet-300 hover:shadow-lg">
        <span class="absolute inset-x-0 top-0 h-1 bg-transparent transition-colors group-hover:bg-violet-500"></span>
        <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
            <i data-lucide="book-open" class="h-8 w-8"></i>
        </span>
        <h3 class="mb-2 text-lg font-semibold">Editar Cardápio Público</h3>
        <p class="mb-5 text-sm text-slate-500">Gerenciar os itens e valores exibidos no cardápio</p>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-violet-700">Editar Cardápio <span aria-hidden="true">→</span></span>
    </a>

    <a href="<?= PathConfig::modules('caixa/') ?>" class="group relative flex min-h-60 flex-col items-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-900 no-underline shadow-sm transition duration-200 hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg">
        <span class="absolute inset-x-0 top-0 h-1 bg-transparent transition-colors group-hover:bg-orange-500"></span>
        <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-100 text-orange-600">
            <i data-lucide="calculator" class="h-8 w-8"></i>
        </span>
        <h3 class="mb-2 text-lg font-semibold">Frente de Caixa</h3>
        <p class="mb-5 text-sm text-slate-500">Gerenciar vendas e comandas</p>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-orange-700">Acessar Módulo <span aria-hidden="true">→</span></span>
    </a>

    <a href="<?= PathConfig::modules('estoque/') ?>" class="group relative flex min-h-60 flex-col items-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-900 no-underline shadow-sm transition duration-200 hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg">
        <span class="absolute inset-x-0 top-0 h-1 bg-transparent transition-colors group-hover:bg-orange-500"></span>
        <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
            <i data-lucide="package" class="h-8 w-8"></i>
        </span>
        <h3 class="mb-2 text-lg font-semibold">Controle de Estoque</h3>
        <p class="mb-5 text-sm text-slate-500">Controle de produtos e reposição</p>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-orange-700">Acessar Módulo <span aria-hidden="true">→</span></span>
    </a>

    <a href="<?= PathConfig::modules('relatorios/') ?>" class="group relative flex min-h-60 flex-col items-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-900 no-underline shadow-sm transition duration-200 hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg">
        <span class="absolute inset-x-0 top-0 h-1 bg-transparent transition-colors group-hover:bg-orange-500"></span>
        <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
            <i data-lucide="chart-no-axes-column-increasing" class="h-8 w-8"></i>
        </span>
        <h3 class="mb-2 text-lg font-semibold">Relatórios &amp; Métricas</h3>
        <p class="mb-5 text-sm text-slate-500">Análises e métricas</p>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-orange-700">Acessar Módulo <span aria-hidden="true">→</span></span>
    </a>

    <a href="<?= PathConfig::modules('admin/') ?>" class="group relative flex min-h-60 flex-col items-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-900 no-underline shadow-sm transition duration-200 hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg">
        <span class="absolute inset-x-0 top-0 h-1 bg-transparent transition-colors group-hover:bg-orange-500"></span>
        <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
            <i data-lucide="settings" class="h-8 w-8"></i>
        </span>
        <h3 class="mb-2 text-lg font-semibold">Configurações</h3>
        <p class="mb-5 text-sm text-slate-500">Configurações do sistema</p>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-orange-700">Acessar Módulo <span aria-hidden="true">→</span></span>
    </a>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>