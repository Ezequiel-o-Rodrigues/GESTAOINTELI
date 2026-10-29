<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>centro de cadastro de itens e produtos</title>
<?php
        // Exibir mensagens de status
        if (isset($_GET['msg'])) {
            echo '<div class="msg">' . htmlspecialchars($_GET['msg']) . '</div>';
        }
        if (isset($_GET['erro'])) {
            echo '<div class="erro">' . htmlspecialchars($_GET['erro']) . '</div>';
        }
        ?>
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
    margin: 0 0 20px 0;
    font-size: 2.5rem;
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

.card-title {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 1.5rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    font-size: 1.1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-primary {
    background: linear-gradient(to right, #4a6fa5, #5a86c1);
    color: white;
    box-shadow: 0 4px 10px rgba(74, 111, 165, 0.4);
}

.btn-primary:hover {
    background: linear-gradient(to right, #3a5a8c, #4a6fa5);
    box-shadow: 0 6px 15px rgba(74, 111, 165, 0.6);
    transform: translateY(-2px);
}

.btn-primary:active {
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

.erro {
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
    background: linear-gradient(to right, #4a6fa5, #5a86c1);
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
    background: linear-gradient(to right, #3a5a8c, #4a6fa5);
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
}

.menu-botoes li a:active {
    transform: translateY(1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-container {
    display: grid;
    gap: 20px;
}

.form-group {
    display: grid;
    gap: 8px;
}

.form-group label {
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 1.1rem;
}

input, select, textarea {
    padding: 12px 15px;
    background-color: rgba(40, 40, 60, 0.7);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.3);
}

  .btn-back.highlight {
    color: #ffffff;
    background-color: rgb(35, 25, 92);
    padding: 8px 16px;
    border-radius: 4px;
}

.btn-back.highlight:hover {
        background-color: rgb(35, 25, 92);
    }


    </style>
    <body>
        <div class="container">
        <h1>Centro de Cadastro de Itens e Produtos</h1>
        </div>
          <div class="card"> 
            <h2 class="card-title">➕ Cadastrar Nova Opção de Buffet</h2>
            <form method="POST" action="processamentoform.php" class="form-container">
                <input type="hidden" name="acao" value="adicionar_opcao">
                <div class="form-group">
            <label>Nome:</label>
            <input type="text" name="nome" required>
        </div>
        <div class="form-group">
            <label>Descrição:</label>
            <input type="text" name="descricao">
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>


<div class="card">
    <h2 class="card-title">🍢 Cadastrar Novo Espeto</h2>
    <form method="POST" action="processamentoform.php" class="form-container">
        <input type="hidden" name="acao" value="adicionar_espeto">
        <div class="form-group">
            <label>Tipo de carne:</label>
            <input type="text" name="tipo_carne" required>
        </div>
        <div class="form-group">
            <label>Preço (caso vendido avulso):</label>
            <input type="number" step="0.01" name="preco">
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">🍟 Cadastrar Nova Porção</h2>
    <form method="POST" action="processamentoform.php" class="form-container">
        <input type="hidden" name="acao" value="adicionar_porcao">
        <div class="form-group">
            <label>Nome:</label>
            <input type="text" name="nome" required>
        </div>
        <div class="form-group">
            <label>Tamanho (P/M/G):</label>
            <input type="text" name="tamanho" required maxlength="1">
        </div>
        <div class="form-group">
            <label>Preço:</label>
            <input type="number" step="0.01" name="preco">
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">🧃 Cadastrar Nova Bebida</h2>
    <form method="POST" action="processamentoform.php" class="form-container">
        <input type="hidden" name="acao" value="adicionar_bebida">
        <div class="form-group">
    <label>Categoria:</label>
    <select name="categoria" required>
        <option value="refrigerante">Refrigerante</option>
        <option value="suco">Suco</option>
        <option value="energetico">Energético</option>
        <option value="agua">Água</option>
        <option value="outros">Outros</option>
    </select>
</div>
        <div class="form-group">
            <label>Nome:</label>
            <input type="text" name="nome" required>
        </div>
        <div class="form-group">
            <label>Preço:</label>
            <input type="number" step="0.01" name="preco" required>
        </div>
        <div class="form-group">
            <label>Tamanho (ml):</label>
            <input type="number" name="tamanho_ml" required>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">🍺 Cadastrar Nova Cerveja</h2>
    <form method="POST" action="processamentoform.php" class="form-container">
        <input type="hidden" name="acao" value="adicionar_cerveja">
        <div class="form-group">
            <label>Marca:</label>
            <input type="text" name="marca" required>
        </div>
        <div class="form-group">
            <label>Tamanho (ml):</label>
            <input type="number" name="tamanho_ml" required>
        </div>
        <div class="form-group">
            <label>Preço:</label>
            <input type="number" step="0.01" name="preco" required>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
    <br><br><br>
</div>
<div class="card">
<a href="painel.php" class="btn-action btn-back highlight">Voltar</a>
</div>
</body>
</html>
