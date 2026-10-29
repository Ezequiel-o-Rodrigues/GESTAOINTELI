<?php
echo "=== CORRIGINDO TODOS OS CAMINHOS database.php ===<br><br>";

$files_to_fix = [
    'api/adicionar_item.php',
    'api/comanda_aberta.php',
    'api/registrar_entrada.php',
    'api/remover_item.php',
    'api/produto_info.php',
    'api/produtos_categoria.php',
    'api/finalizar_comanda.php',
    'api/nova_comanda.php',
    'api/itens_comanda.php',
    'includes/functions.php' // se existir
];

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Substitui includes por config
        $new_content = str_replace(
            "require_once PathConfig::includes('database.php');",
            "require_once PathConfig::config('database.php');",
            $content
        );
        
        // Também substitui caminhos antigos
        $new_content = str_replace(
            "require_once __DIR__ . '/../includes/database.php';",
            "require_once PathConfig::config('database.php');",
            $new_content
        );
        
        if ($content !== $new_content) {
            file_put_contents($file, $new_content);
            echo "✅ Corrigido: $file<br>";
        } else {
            echo "⚠️ Já correto: $file<br>";
        }
    } else {
        echo "❌ Não existe: $file<br>";
    }
}

echo "<br>=== CORREÇÃO CONCLUÍDA ===";
?>