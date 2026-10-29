<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$usuario = 'u903648047_ezequiel';
$senha = 'Ezequiel_2014';
$banco = 'u903648047_junior';

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8");

$tabelas_necessarias = [
    'espetos', 
    'porcoes', 
    'bebidas', 
    'cervejas', 
    'opcoes_buffet'
];

foreach($tabelas_necessarias as $tabela) {
    $result = $conn->query("SHOW TABLES LIKE '$tabela'");
    if($result->num_rows == 0) {
        die("Erro: Tabela '$tabela' não existe no banco de dados!");
    }
    
    // Verificação adicional para coluna 'ativo' no buffet
    if($tabela === 'opcoes_buffet') {
        $result = $conn->query("SHOW COLUMNS FROM `$tabela` LIKE 'ativo'");
        if($result->num_rows == 0) {
            die("Erro: Coluna 'ativo' não existe na tabela 'opcoes_buffet'!");
        }
    }
}

function colunaExiste($conn, $tabela, $coluna) {
    $sql = "SHOW COLUMNS FROM `$tabela` LIKE '$coluna'";
    $result = $conn->query($sql);
    return $result && $result->num_rows > 0;
}
?>