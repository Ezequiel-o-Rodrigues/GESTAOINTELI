<?php
// Verificar se o usuário está logado para mostrar informações adicionais
$usuarioLogado = isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
?>
    </main>

    <footer class="footer">
        <div class="dashboard-footer-inner">
            <p class="mb-0">&copy; <?= date('Y') ?> Sistema Restaurante - Espetinho do Júnior</p>
            <?php if ($usuarioLogado): ?>
            <small class="dashboard-footer-user">
                Usuário: <?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?> | 
                Último acesso: <?= date('d/m/Y H:i:s') ?>
            </small>
            <?php endif; ?>
        </div>
    </footer>
    </div>
    <script src="<?= htmlspecialchars(PathConfig::url('js/react-app.js'), ENT_QUOTES, 'UTF-8') ?>?v=<?= filemtime(__DIR__ . '/../js/react-app.js') ?>"></script>
</body>
</html>