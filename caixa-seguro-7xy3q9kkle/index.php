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
    <h2>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>! 👋</h2>
    <p>Selecione um módulo para começar:</p>
</div>

<div class="modules-grid">
    <div class="module-card" onclick="location.href='<?= PathConfig::modules('caixa/') ?>'">
        <h3>💰 Caixa</h3>
        <p>Gerenciar vendas e comandas</p>
    </div>

    <div class="module-card" onclick="location.href='<?= PathConfig::modules('estoque/') ?>'">
        <h3>📦 Estoque</h3>
        <p>Controle de produtos e reposição</p>
    </div>

    <div class="module-card" onclick="location.href='<?= PathConfig::modules('relatorios/') ?>'">
        <h3>📊 Relatórios</h3>
        <p>Análises e métricas</p>
    </div>

    
    <div class="module-card" onclick="location.href='<?= PathConfig::modules('admin/') ?>'">
        <h3>⚙️ Admin</h3>
        <p>Configurações do sistema</p>
    </div>
</div>

<style>
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin: 3rem 0;
}

.module-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    border-top: 4px solid #F97316;
}

.module-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.module-card h3 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.welcome-section {
    text-align: center;
    padding: 2rem 0;
}

.welcome-section h2 {
    color: #2c3e50;
    margin-bottom: 1rem;
}
</style>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
?>