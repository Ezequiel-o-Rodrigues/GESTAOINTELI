<?php
// Verificar se o usuário está logado para mostrar informações adicionais
$usuarioLogado = isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
?>
    </main>

    <footer class="footer mt-5">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Sistema Restaurante - Espetinho do Júnior</p>
            <?php if ($usuarioLogado): ?>
            <small class="text-muted">
                Usuário: <?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?> | 
                Último acesso: <?= date('d/m/Y H:i:s') ?>
            </small>
            <?php endif; ?>
        </div>
    </footer>
</body>
</html>