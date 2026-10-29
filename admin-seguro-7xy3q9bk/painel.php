<?php 
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/includes/auth.php';
 ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle do Restaurante - Cardápio JNR</title>
    <style>
       :root {
            --bg-dark: #1a1a2e;
            --bg-panel: #16213e;
            --text-primary:rgb(211, 211, 211);
            --text-secondary:rgb(0, 0, 0);
            --accent-color: #4a6fa5;
            --border-color: #2c3e50;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --card-bg: rgba(30, 30, 46, 0.8);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            color: var(--text-primary);
            margin: 0;
            font-size: 2rem;
        }
        
        .card {
            background-color: var(--bg-panel);
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }
        
        .item-header {
            font-weight: bold;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-container {
            display: grid;
            gap: 15px;
        }
        
        .form-group {
            display: grid;
            gap: 5px;
        }
        
        .form-group label {
            color: var(--text-secondary);
        }
        
        input, select, textarea {
            padding: 8px;
            background-color: var(--bg-dark);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-primary);
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a5a8c;
        }
        
        .msg {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--text-primary);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid var(--success-color);
        }
        
        .erro {
            background-color: rgba(244, 67, 54, 0.2);
            color: var(--text-primary);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
        }
        
        .report-section {
            margin-bottom: 30px;
        }
        
        .report-section h3 {
            color: var(--text-secondary);
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
  .menu-botoes {
            list-style-type: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 300px;
            margin: 20px auto;
        }
        
        .menu-botoes li a {
            display: block;
            padding: 15px 20px;
            background-color:rgb(35, 25, 92);
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .menu-botoes li a:hover {
            background-color:rgb(35, 25, 92);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        
        .menu-botoes li a:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        </style>
</head>
<body>
    <div class="container">
</div>
    <div class="container">
        <div class="header">
            <h1 class="page-title">🍽️ Controle do Cardápio JNR</h1>
        </div>
        <header class="header">
            <div class="container header-container">
                <nav class="nav">
                    <ul class="menu-botoes">
                        <li><a href="uploadfotos.php">Cadastrar Imagens</a></li>
                        <li><a href="controlebuffet.php">Controle Buffet Diário</a></li>
                        <li><a href="relatoriogeral.php">Relatório Geral</a></li>
                        <li><a href="formularios.php">Cadastros</a></li>
                        <li><a href="listagens.php">Listagens</a></li>
                        <li><a href="logout.php" class="btn btn-danger">Sair</a></li>
                    </ul>
                </nav>
            </div> 
        </header>
        <?php
        // Exibir mensagens de status
        if (isset($_GET['msg'])) {
            echo '<div class="msg">' . htmlspecialchars($_GET['msg']) . '</div>';
        }
        if (isset($_GET['erro'])) {
            echo '<div class="erro">' . htmlspecialchars($_GET['erro']) . '</div>';
        }
        ?>
    </div>
    <div class="user-info">
    Logado como: <strong><?= htmlspecialchars($_SESSION['admin_nome']) ?></strong>
    <small>Último acesso: <?= date('d/m/Y H:i', $_SESSION['ultimo_acesso']) ?></small>
    <a href="logout.php" class="btn-logout">Sair</a>
</div>
</body>
</html>