<?php
// Configurações de erro para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexão com banco de dados
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/includes/auth.php';

// Verifica se a conexão foi estabelecida
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opcoes'])) {
    try {
        // Desativa todas as opções primeiro
        $conn->query("UPDATE opcoes_buffet SET ativo = 0");
        
        // Ativa apenas as opções selecionadas
        foreach ($_POST['opcoes'] as $id => $valor) {
            $id = (int)$id;
            $conn->query("UPDATE opcoes_buffet SET ativo = 1 WHERE id_opcao = $id");
        }
        
        // Redireciona com mensagem de sucesso
        header("Location: controlebuffet.php?msg=Configurações+salvas+com+sucesso");
        exit();
    } catch (Exception $e) {
        error_log("Erro ao salvar configurações: " . $e->getMessage());
        $error = "Erro ao salvar configurações";
    }
}

/**
 * Função para buscar itens do menu com tratamento de erros
 */
function getMenuItems($conn, $tabela, $where = '') {
    // Validação do nome da tabela para prevenir SQL injection
    $tabelas_permitidas = ['espetos', 'porcoes', 'bebidas', 'cervejas', 'opcoes_buffet'];
    if (!in_array($tabela, $tabelas_permitidas)) {
        error_log("Tentativa de acesso a tabela não permitida: " . $tabela);
        return [];
    }

    try {
        $sql = "SELECT * FROM $tabela" . ($where ? " WHERE $where" : "");
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Erro na consulta: " . $conn->error);
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Erro em getMenuItems: " . $e->getMessage());
        return [];
    }
}

// Busca todos os itens do menu
$menuData = [
    'espetos' => getMenuItems($conn, 'espetos'),
    'porcoes' => getMenuItems($conn, 'porcoes'),
    'bebidas' => getMenuItems($conn, 'bebidas'),
    'cervejas' => getMenuItems($conn, 'cervejas'),
    'buffet' => getMenuItems($conn, 'opcoes_buffet', 'ativo = 1')
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Opções de Buffet</title>
    <link rel="stylesheet" href="css/estilo.css">
   <style>
    :root {
        --bg-dark: #1a1a2e;
        --bg-panel: #16213e;
        --text-primary: #e6e6e6;
        --text-secondary: #a9a9a9;
        --accent-color: #4a6fa5;
        --border-color: #2c3e50;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --card-bg: rgba(30, 30, 46, 0.8);
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--bg-dark);
        color: var(--text-primary);
        background-image: linear-gradient(to bottom right, #1a1a2e, #16213e, #0f3460);
        min-height: 100vh;
        line-height: 1.6;
        padding: 20px;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    h1 {
        color: var(--text-primary);
        margin-bottom: 25px;
        font-size: 2rem;
        background: linear-gradient(to right, #4a6fa5, #7eb4e2);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .card {
        background-color: var(--card-bg);
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 25px;
        border-left: 5px solid var(--accent-color);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        background-color: var(--card-bg);
    }
    
    th, td {
        padding: 12px 15px;
        border: 1px solid var(--border-color);
        text-align: left;
    }
    
    th {
        background-color: var(--bg-panel);
        color: var(--accent-color);
        font-weight: bold;
    }
    
    tr:nth-child(even) {
        background-color: rgba(40, 40, 60, 0.5);
    }
    
    tr:hover {
        background-color: rgba(74, 111, 165, 0.2);
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
        font-size: 1rem;
        background: linear-gradient(to right, #4a6fa5, #5a86c1);
        color: white;
        box-shadow: 0 4px 10px rgba(74, 111, 165, 0.4);
    }
    
    .btn:hover {
        background: linear-gradient(to right, #3a5a8c, #4a6fa5);
        box-shadow: 0 6px 15px rgba(74, 111, 165, 0.6);
        transform: translateY(-2px);
    }
    
    .btn:active {
        transform: translateY(1px);
    }
    
    .msg {
        background-color: rgba(76, 175, 80, 0.2);
        color: var(--text-primary);
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 25px;
        border-left: 4px solid var(--success-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .error {
        background-color: rgba(244, 67, 54, 0.2);
        color: var(--text-primary);
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 25px;
        border-left: 4px solid #f44336;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--accent-color);
    }
    
    .btn-back {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 20px;
        background-color: rgb(35, 25, 92);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
        
    }
    
    .btn-back:hover {
        background-color: rgb(45, 35, 110);

    }
</style>
</head>
<body>
    <h1>Gerenciar Opções de Buffet</h1>
    
    <?php if (isset($_GET['msg'])): ?>
        <div class="msg"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <table>
            <tr>
                <th>Opção</th>
                <th>Descrição</th>
                <th>Status</th>
            </tr>
            <?php 
            $opcoes = $conn->query("SELECT * FROM opcoes_buffet ORDER BY nome");
            if (!$opcoes) {
                die("Erro ao buscar opções de buffet: " . $conn->error);
            }
            while($opcao = $opcoes->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($opcao['nome']) ?></td>
                <td><?= htmlspecialchars($opcao['descricao'] ?? '') ?></td>
                <td>
                    <label>
                        <input type="checkbox" name="opcoes[<?= $opcao['id_opcao'] ?>]" 
                            <?= ($opcao['ativo'] ? 'checked' : '') ?>>
                        Ativo
                    </label>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <button type="submit" class="btn-action btn-back highlight">Salvar Configurações</button>
        <a href="painel.php" class="btn-action btn-back highlight">Voltar</a>
</div>
    </form>
</body>
</html>
<?php
// Fecha a conexão com o banco de dados
$conn->close();
?>