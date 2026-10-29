<?php 
require_once __DIR__ . '/../includes/conexao.php';
 require_once __DIR__ . '/includes/auth.php';  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['acao']) {
            case 'adicionar_opcao':
                $nome = $_POST['nome'];
                $descricao = $_POST['descricao'];
                
                $stmt = $conn->prepare("INSERT INTO opcoes_buffet (nome, descricao) VALUES (?, ?)");
                $stmt->bind_param("ss", $nome, $descricao);
                $stmt->execute();
                $stmt->close();
            
   
               header("Location: formularios.php?msg=Nova opção de buffet adicionada");
                break;

            case 'adicionar_espeto':
                $carne = $_POST['tipo_carne'];
                $preco = $_POST['preco'];
                
                $stmt = $conn->prepare("INSERT INTO espetos (tipo_carne, preco) VALUES (?, ?)");
                $stmt->bind_param("sd", $carne, $preco);
                $stmt->execute();
                $stmt->close();
                
                header("Location: formularios.php?msg=Espeto cadastrado");
                break;

            case 'adicionar_porcao':
                $nome = $_POST['nome'];
                $tam = $_POST['tamanho'];
                $preco = $_POST['preco'];
                
                $stmt = $conn->prepare("INSERT INTO porcoes (nome, tamanho, preco) VALUES (?, ?, ?)");
                $stmt->bind_param("ssd", $nome, $tam, $preco);
                $stmt->execute();
                $stmt->close();
                
                header("Location: formularios.php?msg=Porção cadastrada");
                break;

           case 'adicionar_bebida':
    $nome = $_POST['nome'];
    $preco = $_POST['preco'];
    $ml = $_POST['tamanho_ml'];
    $categoria = $_POST['categoria'];
    
    $stmt = $conn->prepare("INSERT INTO bebidas (nome, preco, tamanho_ml, categoria) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdis", $nome, $preco, $ml, $categoria);
    $stmt->execute();
    $stmt->close();
    
    header("Location: formularios.php?msg=Bebida cadastrada");
    break;
            case 'adicionar_cerveja':
                $marca = $_POST['marca'];
                $tamanho_ml = $_POST['tamanho_ml'];
                $preco = $_POST['preco'];
                
                $stmt = $conn->prepare("INSERT INTO cervejas (marca, tamanho_ml, preco) VALUES (?, ?, ?)");
                $stmt->bind_param("sid", $marca, $tamanho_ml, $preco);
                $stmt->execute();
                $stmt->close();
                
                header("Location: formularios.php?msg=Cerveja cadastrada");
                break;
        
    }
    } catch (Exception $e) {
        header("Location: index.php?erro=" . urlencode("Ocorreu um erro: " . $e->getMessage()));
    }
    exit;
}
?>