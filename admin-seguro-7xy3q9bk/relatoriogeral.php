<?php
require_once __DIR__ . '/../includes/conexao.php';
 require_once __DIR__ . '/includes/auth.php';
?>

<div class="card">
    <h2 class="card-title">📊 Relatório Geral de Itens</h2>

    <div class="report-section">
        <h3>🍽️ Opções de Buffet</h3>
        <div class="item-list">
            <div class="item-row item-header">
                <div>ID</div>
                <div>Nome</div>
                <div>Descrição</div>
                <div>Ativo</div>
                <div>Imagem</div>
            </div>
            <?php
            $res = $conn->query("SELECT * FROM opcoes_buffet");
            while ($row = $res->fetch_assoc()) {
                echo '
                <div class="item-row">
                    <div>'.$row['id_opcao'].'</div>
                    <div>'.htmlspecialchars($row['nome']).'</div>
                    <div>'.htmlspecialchars($row['descricao']).'</div>
                    <div>'.($row['ativo'] ? 'Sim' : 'Não').'</div>
                </div>';
            }
            ?>
        </div>
    </div>
    
    <div class="report-section">
        <h3>🍢 Espetos</h3>
        <div class="item-list">
            <div class="item-row item-header">
                <div>ID</div>
                <div>Tipo de Carne</div>
                <div>Preço</div>
                <div>Imagem</div>
            </div>
            <?php
            $res = $conn->query("SELECT * FROM espetos");
            while ($row = $res->fetch_assoc()) {
                echo '
                <div class="item-row">
                    <div>'.$row['id_espeto'].'</div>
                    <div>'.htmlspecialchars($row['tipo_carne']).'</div>
                    <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                    <div>'.htmlspecialchars($row['imagem'] ?? '').'</div>

                    
                </div>';
            }
            ?>
        </div>
    </div>
    
    <div class="report-section">
        <h3>🍟 Porções</h3>
        <div class="item-list">
            <div class="item-row item-header">
                <div>ID</div>
                <div>Nome</div>
                <div>Tamanho</div>
                <div>Preço</div>
                <div>Imagem</div>
            </div>
            <?php
            $res = $conn->query("SELECT * FROM porcoes");
            while ($row = $res->fetch_assoc()) {
                echo '
                <div class="item-row">
                    <div>'.$row['id_porcao'].'</div>
                    <div>'.htmlspecialchars($row['nome']).'</div>
                    <div>'.htmlspecialchars($row['tamanho']).'</div>
                    <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                </div>';
            }
            ?>
        </div>
    </div>
    
    <div class="report-section">
        <h3>🧃 Bebidas</h3>
        <div class="item-list">
            <div class="item-row item-header">
                <div>ID</div>
                <div>Nome</div>
                <div>Tamanho (ml)</div>
                <div>Preço</div>
                <div>Imagem</div>
            </div>
            <?php
            $res = $conn->query("SELECT * FROM bebidas");
            while ($row = $res->fetch_assoc()) {
                echo '
                <div class="item-row">
                    <div>'.$row['id_bebida'].'</div>
                    <div>'.htmlspecialchars($row['nome']).'</div>
                    <div>'.htmlspecialchars($row['tamanho_ml']).'</div>
                    <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                    
                </div>';
            }
            ?>
        </div>
    </div>
    
    <div class="report-section">
        <h3>🍺 Cervejas</h3>
        <div class="item-list">
            <div class="item-row item-header">
                <div>ID</div>
                <div>Marca</div>
                <div>Tamanho (ml)</div>
                <div>Preço</div>
                <div>Imagem</div>
            </div>
            <?php
            $res = $conn->query("SELECT * FROM cervejas");
            while ($row = $res->fetch_assoc()) {
                echo '
                <div class="item-row">
                    <div>'.$row['id_cerveja'].'</div>
                    <div>'.htmlspecialchars($row['marca']).'</div>
                    <div>'.htmlspecialchars($row['tamanho_ml']).'</div>
                    <div>R$ '.number_format($row['preco'], 2, ',', '.').'</div>
                </div>';
            }
            ?>
        </div>
    </div>
<!-- Destacada -->
<a href="painel.php" class="btn-action btn-back highlight">Voltar</a>
</div>

<style>
.card {
    background: #f2f4f4;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgb(0, 0, 0);
    padding: 20px;
    margin-bottom: 20px;
}

.card-title {
    color: #333;
    margin-bottom: 20px;
    font-size: 1.5rem;
}

.report-section {
    margin-bottom: 30px;
}

.report-section h3 {
    color: #444;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.item-list {
    border: 1px solid #000000;
    border-radius: 5px;
    overflow: hidden;
}

.item-row {
    display: grid;
    grid-template-columns: 60px 1fr 1fr 100px 80px 120px;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.item-row.item-header {
    background-color: #f5f5f5;
    font-weight: bold;
}

.item-row:nth-child(even) {
    background-color: #fafafa;
}

.item-row > div {
    padding: 5px;
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