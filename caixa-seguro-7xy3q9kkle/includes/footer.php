<?php
// Verificar se o usuário está logado para mostrar informações adicionais
$usuarioLogado = isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
?>
    </main>

    <footer class="footer mt-0 border-t border-slate-200 bg-white text-slate-500">
        <div class="dashboard-footer-inner mx-auto flex w-full max-w-screen-2xl flex-col items-center justify-between gap-2 px-6 py-4 text-sm sm:flex-row">
            <p class="mb-0">&copy; <?= date('Y') ?> Sistema Restaurante - Espetinho do Júnior</p>
            <?php if ($usuarioLogado): ?>
            <small class="text-slate-500">
                Usuário: <?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?> | 
                Último acesso: <?= date('d/m/Y H:i:s') ?>
            </small>
            <?php endif; ?>
        </div>
    </footer>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>