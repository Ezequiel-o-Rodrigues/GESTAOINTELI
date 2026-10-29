<?php
session_start();
require_once __DIR__ . '/../includes/conexao.php';

if (isset($_SESSION['admin_logado']) && isset($_SESSION['ultimo_acesso'])) {
    $inatividade = 1800; // 30 minutos em segundos
    $tempo_decorrido = time() - $_SESSION['ultimo_acesso'];
    
    if ($tempo_decorrido > $inatividade) {
        session_unset();
        session_destroy();
    } else {
        $_SESSION['ultimo_acesso'] = time();
        header('Location: painel.php');
        exit();
    }
}

// Verifica se foi solicitado logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Verificar se já está logado
if (isset($_SESSION['admin_logado'])) {
    header('Location: painel.php');
    exit();
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    
    if (!empty($usuario) && !empty($senha)) {
        try {
            $stmt = $conn->prepare("SELECT id_admin, usuario, senha_hash, nome FROM administradores WHERE usuario = ? LIMIT 1");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                if (password_verify($senha, $admin['senha_hash'])) {
                    // Login bem-sucedido
                    $_SESSION['admin_logado'] = true;
                    $_SESSION['admin_id'] = $admin['id_admin'];
                    $_SESSION['admin_nome'] = $admin['nome'];
                    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
                    
                    // Atualizar último login
                    $update = $conn->prepare("UPDATE administradores SET ultimo_login = NOW() WHERE id_admin = ?");
                    $update->bind_param("i", $admin['id_admin']);
                    $update->execute();
                    $update->close();
                    
                    header('Location: painel.php');
                    exit();
                } else {
                    $erro = 'Senha incorreta';
                }
            } else {
                $erro = 'Usuário não encontrado';
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            $erro = 'Erro no sistema. Por favor, tente novamente.';
        }
    } else {
        $erro = 'Por favor, preencha todos os campos';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Administrativo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #16213e;
            --text-color: #e6e6e6;
            --bg-dark: #1a1a2e;
            --danger-color: #f44336;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-image: linear-gradient(to bottom right, #1a1a2e, #16213e, #0f3460);
        }
        
        .login-container {
            background-color: rgba(30, 30, 46, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            border-left: 5px solid var(--primary-color);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, #4a6fa5, #7eb4e2);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid var(--secondary-color);
            background-color: rgba(22, 33, 62, 0.7);
            color: var(--text-color);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.3);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: none;
            background: linear-gradient(to right, #4a6fa5, #5a86c1);
            color: white;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: linear-gradient(to right, #3a5a8c, #4a6fa5);
            box-shadow: 0 6px 15px rgba(74, 111, 165, 0.6);
            transform: translateY(-2px);
        }
        
        .error-message {
            color: var(--danger-color);
            margin-top: 10px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-color);
            opacity: 0.7;
        }
        
        .password-toggle i:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if (!empty($erro)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" class="form-control" required>
            </div>
            
            <div class="form-group password-toggle">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" required>
                <i class="fas fa-eye" id="togglePassword"></i>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const senhaInput = document.getElementById('senha');
            const icon = this;
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>
</html>