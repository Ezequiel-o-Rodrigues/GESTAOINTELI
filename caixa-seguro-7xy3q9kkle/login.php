<?php
require_once __DIR__ . '/config/auth.php';

$destinoCardapio = ($_GET['destino'] ?? $_POST['destino'] ?? '') === 'cardapio';

// Se já estiver logado, redireciona para página principal
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    $perfilSessao = strtolower(trim((string) ($_SESSION['usuario_perfil'] ?? '')));
    if (in_array($perfilSessao, ['admin', 'caixa', 'estoque'], true)) {
        $destinoCardapioPermitido = $destinoCardapio && $perfilSessao === 'admin';
        header('Location: ' . ($destinoCardapioPermitido
            ? '../admin-seguro-7xy3q9bk/gerenciar_cardapio.php'
            : 'index.php'));
        exit;
    }

    session_unset();
    session_destroy();
}

$error = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($login) && !empty($senha)) {
        require_once __DIR__ . '/config/database.php';
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Aceitar email como login
            $stmt = $db->prepare("SELECT id, nome, email, senha, perfil, ativo FROM usuarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$login]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Verificar senha
                if (password_verify($senha, $usuario['senha'])) {
                    $perfil = strtolower(trim((string) $usuario['perfil']));
                    if (!in_array($perfil, ['admin', 'caixa', 'estoque'], true)) {
                        $error = 'Perfil de acesso inválido. Solicite a revisão do seu cadastro.';
                    } elseif ($destinoCardapio && $perfil !== 'admin') {
                        $error = 'Apenas administradores podem acessar o gerenciamento do cardápio.';
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nome'] = $usuario['nome'];
                        $_SESSION['usuario_email'] = $usuario['email'];
                        $_SESSION['usuario_perfil'] = $perfil;
                        $_SESSION['usuario_logado'] = true;
                        $_SESSION['last_activity'] = time();

                        header('Location: ' . ($destinoCardapio
                            ? '../admin-seguro-7xy3q9bk/gerenciar_cardapio.php'
                            : 'index.php'));
                        exit;
                    }
                } else {
                    $error = 'Senha incorreta';
                }
            } else {
                $error = 'Usuário não encontrado ou inativo';
            }
        } catch (Exception $e) {
            error_log('Erro ao processar login: ' . $e->getMessage());
            $error = 'Erro ao processar login: ' . $e->getMessage();
        }
    } else {
        $error = 'Preencha todos os campos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f7fb">
    <title>Entrar | GestãoInteli JNR</title>
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body class="login-body">
    <div
        id="react-app"
        data-screen="login"
        data-error="<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>"
        data-notice="<?= isset($_GET['expired']) ? 'Sessão expirada. Faça login novamente.' : (isset($_GET['logout']) ? 'Você saiu do sistema.' : '') ?>"
        data-email="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        data-cardapio="<?= $destinoCardapio ? 'true' : 'false' ?>"
    ></div>
    <script src="https://cdn.jsdelivr.net/npm/react@18.3.1/umd/react.production.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@18.3.1/umd/react-dom.production.min.js" crossorigin="anonymous"></script>
    <script src="js/react-app.js?v=<?= filemtime(__DIR__ . '/js/react-app.js') ?>"></script>
</body>
</html>